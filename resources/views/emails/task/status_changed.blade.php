Hi {{ $recipient->name }},

The status of a task in "{{ $project->name }}" has been updated.

----------------------------------------
TASK STATUS UPDATE
----------------------------------------
Task       : {{ $task->title }}
Project    : {{ $project->name }}
Old Status : {{ $oldStatus }}
New Status : {{ $newStatus }}
Updated by : {{ $actor->name }}
@if($task->description)

Description:
{{ strip_tags($task->description) }}
@endif

----------------------------------------

You can view this task by logging into {{ $appName }}:
{{ $appUrl }}

Best regards,
{{ $appName }}

---
This is an automated notification. Please do not reply to this email.
