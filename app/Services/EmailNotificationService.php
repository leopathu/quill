<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Task;
use App\Models\Project;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class EmailNotificationService
{
    /**
     * Build a Swift mailer configuration from the organization's SMTP settings.
     * Returns true if org SMTP is configured, false to fall back to .env defaults.
     */
    private function configureSmtp(Organization $org): bool
    {
        $settings = $org->settings ?? [];

        $host = $settings['smtp_host'] ?? '';
        $port = $settings['smtp_port'] ?? '';
        $username = $settings['smtp_username'] ?? '';
        $password = $settings['smtp_password'] ?? '';

        if (empty($host) || empty($port) || empty($username) || empty($password)) {
            return false;
        }

        $encryption = $settings['smtp_encryption'] ?? 'tls';
        $fromAddress = $settings['smtp_from_address'] ?? $username;
        $fromName = $settings['smtp_from_name'] ?? ($org->name ?? config('app.name'));

        config([
            'mail.mailers.smtp.host'       => $host,
            'mail.mailers.smtp.port'       => (int) $port,
            'mail.mailers.smtp.username'   => $username,
            'mail.mailers.smtp.password'   => $password,
            'mail.mailers.smtp.encryption' => $encryption === 'none' ? null : $encryption,
            'mail.from.address'            => $fromAddress,
            'mail.from.name'               => $fromName,
        ]);

        return true;
    }

    /**
     * Core send method. Silently fails if SMTP is not configured.
     */
    public function send(Organization $org, string $to, string $toName, string $subject, string $view, array $data): void
    {
        try {
            $configured = $this->configureSmtp($org);

            if (!$configured) {
                return; // No SMTP configured — skip silently
            }

            Mail::send($view, $data, function (Message $message) use ($to, $toName, $subject) {
                $message->to($to, $toName)->subject($subject);
            });
        } catch (\Throwable $e) {
            // Never break the app because of email failure
            \Log::warning('EmailNotificationService: failed to send email', [
                'to'      => $to,
                'subject' => $subject,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Task Notifications
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Notify assignee when a task is created and assigned to them.
     */
    public function taskAssigned(Task $task, Project $project, Organization $org): void
    {
        if (!$task->assignee_id || $task->assignee_id === $task->owner_id) {
            return;
        }

        $assignee = $task->assignee;
        if (!$assignee) return;

        $this->send(
            $org,
            $assignee->email,
            $assignee->name,
            "[{$project->name}] Task assigned to you: {$task->title}",
            'emails.task.assigned',
            [
                'assignee'   => $assignee,
                'task'       => $task,
                'project'    => $project,
                'assignedBy' => auth()->user(),
                'appName'    => config('app.name'),
                'appUrl'     => config('app.url'),
            ]
        );
    }

    /**
     * Notify old assignee + new assignee when a task is reassigned.
     */
    public function taskReassigned(Task $task, Project $project, Organization $org, ?int $previousAssigneeId): void
    {
        $actor = auth()->user();

        // Notify new assignee (if changed and different from actor)
        if ($task->assignee_id && $task->assignee_id !== $previousAssigneeId) {
            $newAssignee = $task->assignee;
            if ($newAssignee && $newAssignee->id !== $actor->id) {
                $this->send(
                    $org,
                    $newAssignee->email,
                    $newAssignee->name,
                    "[{$project->name}] Task assigned to you: {$task->title}",
                    'emails.task.assigned',
                    [
                        'assignee'   => $newAssignee,
                        'task'       => $task,
                        'project'    => $project,
                        'assignedBy' => $actor,
                        'appName'    => config('app.name'),
                        'appUrl'     => config('app.url'),
                    ]
                );
            }
        }
    }

    /**
     * Notify task owner/creator when a task is marked Completed.
     */
    public function taskCompleted(Task $task, Project $project, Organization $org): void
    {
        $owner = $task->owner;
        if (!$owner) return;

        $completedBy = auth()->user();

        // Don't notify if owner completed their own task
        if ($owner->id === $completedBy->id) return;

        $this->send(
            $org,
            $owner->email,
            $owner->name,
            "[{$project->name}] Task completed: {$task->title}",
            'emails.task.completed',
            [
                'owner'       => $owner,
                'task'        => $task,
                'project'     => $project,
                'completedBy' => $completedBy,
                'appName'     => config('app.name'),
                'appUrl'      => config('app.url'),
            ]
        );
    }

    /**
     * Notify status change to all relevant parties (assignee + owner) except the actor.
     */
    public function taskStatusChanged(Task $task, Project $project, Organization $org, string $oldStatus, string $newStatus): void
    {
        if ($newStatus === 'Completed') {
            $this->taskCompleted($task, $project, $org);
            return;
        }

        $actor = auth()->user();
        $notified = [$actor->id];

        // Notify assignee
        if ($task->assignee_id && !in_array($task->assignee_id, $notified)) {
            $assignee = $task->assignee;
            if ($assignee) {
                $notified[] = $assignee->id;
                $this->send(
                    $org,
                    $assignee->email,
                    $assignee->name,
                    "[{$project->name}] Task status updated: {$task->title}",
                    'emails.task.status_changed',
                    [
                        'recipient' => $assignee,
                        'task'      => $task,
                        'project'   => $project,
                        'actor'     => $actor,
                        'oldStatus' => $oldStatus,
                        'newStatus' => $newStatus,
                        'appName'   => config('app.name'),
                        'appUrl'    => config('app.url'),
                    ]
                );
            }
        }

        // Notify owner
        if ($task->owner_id && !in_array($task->owner_id, $notified)) {
            $owner = $task->owner;
            if ($owner) {
                $this->send(
                    $org,
                    $owner->email,
                    $owner->name,
                    "[{$project->name}] Task status updated: {$task->title}",
                    'emails.task.status_changed',
                    [
                        'recipient' => $owner,
                        'task'      => $task,
                        'project'   => $project,
                        'actor'     => $actor,
                        'oldStatus' => $oldStatus,
                        'newStatus' => $newStatus,
                        'appName'   => config('app.name'),
                        'appUrl'    => config('app.url'),
                    ]
                );
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Comment Notifications
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Notify task owner and assignee (but not the commenter) when a comment is added.
     */
    public function commentAdded(TaskComment $comment, Task $task, Project $project, Organization $org): void
    {
        $commenter = auth()->user();
        $notified  = [$commenter->id];

        $recipients = [];

        // Notify task owner
        if ($task->owner && !in_array($task->owner->id, $notified)) {
            $notified[]   = $task->owner->id;
            $recipients[] = $task->owner;
        }

        // Notify task assignee
        if ($task->assignee && !in_array($task->assignee->id, $notified)) {
            $notified[]   = $task->assignee->id;
            $recipients[] = $task->assignee;
        }

        // If this is a reply, also notify the parent comment author
        if ($comment->parent_id) {
            $parentAuthor = optional($comment->parent)->user;
            if ($parentAuthor && !in_array($parentAuthor->id, $notified)) {
                $notified[]   = $parentAuthor->id;
                $recipients[] = $parentAuthor;
            }
        }

        foreach ($recipients as $recipient) {
            $this->send(
                $org,
                $recipient->email,
                $recipient->name,
                "[{$project->name}] New comment on: {$task->title}",
                'emails.comment.added',
                [
                    'recipient' => $recipient,
                    'comment'   => $comment,
                    'task'      => $task,
                    'project'   => $project,
                    'commenter' => $commenter,
                    'appName'   => config('app.name'),
                    'appUrl'    => config('app.url'),
                ]
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Project Notifications
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Notify admin users when a new project is created (if they didn't create it).
     */
    public function projectCreated(Project $project, Organization $org): void
    {
        $creator = auth()->user();

        // Notify all admin users in the org (except the creator)
        $admins = User::where('organization_id', $org->id)
            ->whereHas('role', fn ($q) => $q->where('name', 'admin'))
            ->get();

        foreach ($admins as $admin) {
            if ($admin->id === $creator->id) continue;

            $this->send(
                $org,
                $admin->email,
                $admin->name,
                "[{$org->name}] New project created: {$project->name}",
                'emails.project.created',
                [
                    'orgOwner'  => $admin,
                    'project'   => $project,
                    'createdBy' => $creator,
                    'appName'   => config('app.name'),
                    'appUrl'    => config('app.url'),
                ]
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Team Notifications
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Notify a user when they are added to a project team.
     */
    public function teamMemberAdded(User $addedUser, Project $project, Organization $org, string $role): void
    {
        $addedBy = auth()->user();

        $this->send(
            $org,
            $addedUser->email,
            $addedUser->name,
            "You've been added to project: {$project->name}",
            'emails.team.member_added',
            [
                'addedUser' => $addedUser,
                'project'   => $project,
                'addedBy'   => $addedBy,
                'role'      => ucfirst($role),
                'appName'   => config('app.name'),
                'appUrl'    => config('app.url'),
            ]
        );
    }

    /**
     * Notify a user when they are removed from a project team.
     */
    public function teamMemberRemoved(User $removedUser, Project $project, Organization $org): void
    {
        $removedBy = auth()->user();

        $this->send(
            $org,
            $removedUser->email,
            $removedUser->name,
            "You've been removed from project: {$project->name}",
            'emails.team.member_removed',
            [
                'removedUser' => $removedUser,
                'project'     => $project,
                'removedBy'   => $removedBy,
                'appName'     => config('app.name'),
                'appUrl'      => config('app.url'),
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Document Notifications
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Notify all project team members when a new document is created.
     */
    public function documentCreated(\App\Models\ProjectDocument $document, Project $project, Organization $org): void
    {
        $author  = auth()->user();
        $members = $project->team()->get();

        // Also include project owner
        $owner = $project->owner;
        if ($owner && !$members->contains('id', $owner->id)) {
            $members->push($owner);
        }

        foreach ($members as $member) {
            if ($member->id === $author->id) continue;

            $this->send(
                $org,
                $member->email,
                $member->name,
                "[{$project->name}] New document: {$document->title}",
                'emails.document.created',
                [
                    'recipient' => $member,
                    'document'  => $document,
                    'project'   => $project,
                    'author'    => $author,
                    'appName'   => config('app.name'),
                    'appUrl'    => config('app.url'),
                ]
            );
        }
    }
}
