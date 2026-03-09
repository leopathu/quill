Hi {{ $recipient->name }},

{{ $commenter->name }} left a comment on a task in "{{ $project->name }}".

----------------------------------------
COMMENT
----------------------------------------
Task    : {{ $task->title }}
Project : {{ $project->name }}
By      : {{ $commenter->name }}

Comment:
{{ $comment->body }}

----------------------------------------

You can view and reply to this comment by logging into {{ $appName }}:
{{ $appUrl }}

Best regards,
{{ $appName }}

---
This is an automated notification. Please do not reply to this email.
