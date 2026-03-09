Hi {{ $owner->name }},

Great news! A task in your project "{{ $project->name }}" has been marked as Completed.

----------------------------------------
TASK DETAILS
----------------------------------------
Task         : {{ $task->title }}
Project      : {{ $project->name }}
Completed by : {{ $completedBy->name }}
@if($task->estimation)
Estimated    : {{ $task->estimation }} hour(s)
@endif

----------------------------------------

You can view the task by logging into {{ $appName }}:
{{ $appUrl }}

Best regards,
{{ $appName }}

---
This is an automated notification. Please do not reply to this email.
