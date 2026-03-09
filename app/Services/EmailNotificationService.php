<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Task;
use App\Models\Project;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Mail\Message;
use Illuminate\Mail\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

class EmailNotificationService
{
    /**
     * Build a fresh Illuminate Mailer using the org's SMTP settings.
     * Returns null if settings are incomplete.
     */
    private function buildMailer(Organization $org): ?Mailer
    {
        $settings = $org->settings ?? [];

        $host     = $settings['smtp_host']     ?? '';
        $port     = $settings['smtp_port']     ?? '';
        $username = $settings['smtp_username'] ?? '';
        $password = $settings['smtp_password'] ?? '';

        if (empty($host) || empty($port) || empty($username) || empty($password)) {
            return null;
        }

        $encryption  = $settings['smtp_encryption']   ?? 'tls';
        $fromAddress = $settings['smtp_from_address'] ?? $username;
        $fromName    = $settings['smtp_from_name']    ?? ($org->name ?? config('app.name'));

        $transport = new EsmtpTransport(
            $host,
            (int) $port,
            $encryption === 'ssl'
        );
        $transport->setUsername($username);
        $transport->setPassword($password);

        $mailer = new Mailer('smtp', app('view'), $transport, app('events'));
        $mailer->alwaysFrom($fromAddress, $fromName);

        return $mailer;
    }

    /**
     * Core send method. Silently fails if SMTP is not configured or send fails.
     */
    public function send(Organization $org, string $to, string $toName, string $subject, string $view, array $data): void
    {
        try {
            $mailer = $this->buildMailer($org);

            if (!$mailer) {
                \Log::info('EmailNotificationService: SMTP not configured for org ' . $org->id . ', skipping.');
                return;
            }

            $mailer->send($view, $data, function (Message $message) use ($to, $toName, $subject) {
                $message->to($to, $toName)->subject($subject);
            });
        } catch (\Throwable $e) {
            \Log::warning('EmailNotificationService: failed to send email', [
                'to'      => $to,
                'subject' => $subject,
                'error'   => $e->getMessage(),
            ]);
        }
    }

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

    public function taskReassigned(Task $task, Project $project, Organization $org, ?int $previousAssigneeId): void
    {
        $actor = auth()->user();

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

    public function taskCompleted(Task $task, Project $project, Organization $org): void
    {
        $owner = $task->owner;
        if (!$owner) return;

        $completedBy = auth()->user();
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

    public function taskStatusChanged(Task $task, Project $project, Organization $org, string $oldStatus, string $newStatus): void
    {
        if ($newStatus === 'Completed') {
            $this->taskCompleted($task, $project, $org);
            return;
        }

        $actor    = auth()->user();
        $notified = [$actor->id];

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

        // Notify parent comment author (for replies)
        if ($comment->parent_id) {
            $parentAuthor = optional($comment->parent)->user;
            if ($parentAuthor && !in_array($parentAuthor->id, $notified)) {
                $notified[]   = $parentAuthor->id;
                $recipients[] = $parentAuthor;
            }
        }

        // Notify all other users who have previously commented on this task
        $previousCommenters = TaskComment::where('task_id', $task->id)
            ->where('id', '!=', $comment->id)
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter()
            ->unique('id');

        foreach ($previousCommenters as $prevCommenter) {
            if (!in_array($prevCommenter->id, $notified)) {
                $notified[]   = $prevCommenter->id;
                $recipients[] = $prevCommenter;
            }
        }

        \Log::info('EmailNotificationService::commentAdded', [
            'task_id'        => $task->id,
            'commenter_id'   => $commenter->id,
            'owner_id'       => $task->owner_id,
            'assignee_id'    => $task->assignee_id,
            'recipient_count' => count($recipients),
            'recipients'     => collect($recipients)->pluck('email')->toArray(),
        ]);

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

    public function projectCreated(Project $project, Organization $org): void
    {
        $creator = auth()->user();

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

    public function teamMemberAdded(User $addedUser, Project $project, Organization $org, string $role): void
    {
        $this->send(
            $org,
            $addedUser->email,
            $addedUser->name,
            "You've been added to project: {$project->name}",
            'emails.team.member_added',
            [
                'addedUser' => $addedUser,
                'project'   => $project,
                'addedBy'   => auth()->user(),
                'role'      => ucfirst($role),
                'appName'   => config('app.name'),
                'appUrl'    => config('app.url'),
            ]
        );
    }

    public function teamMemberRemoved(User $removedUser, Project $project, Organization $org): void
    {
        $this->send(
            $org,
            $removedUser->email,
            $removedUser->name,
            "You've been removed from project: {$project->name}",
            'emails.team.member_removed',
            [
                'removedUser' => $removedUser,
                'project'     => $project,
                'removedBy'   => auth()->user(),
                'appName'     => config('app.name'),
                'appUrl'      => config('app.url'),
            ]
        );
    }

    public function documentCreated(\App\Models\ProjectDocument $document, Project $project, Organization $org): void
    {
        $author  = auth()->user();
        $members = $project->team()->get();

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
