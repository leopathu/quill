Hi {{ $assignee->name }},

A task has been assigned to you in the project "{{ $project->name }}".

----------------------------------------
TASK DETAILS
----------------------------------------
Task    : {{ $task->title }}
Project : {{ $project->name }}
Status  : {{ $task->status }}
@if($task->estimation)
Estimate: {{ $task->estimation }} hour(s)
@endif
Assigned by: {{ $assignedBy->name }}
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
