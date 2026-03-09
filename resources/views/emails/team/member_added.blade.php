Hi {{ $addedUser->name }},

You have been added to the project "{{ $project->name }}".

----------------------------------------
PROJECT MEMBERSHIP
----------------------------------------
Project  : {{ $project->name }}
Your role: {{ $role }}
Added by : {{ $addedBy->name }}
@if($project->description)

About this project:
{{ $project->description }}
@endif

----------------------------------------

Log in to {{ $appName }} to start working on this project:
{{ $appUrl }}

Best regards,
{{ $appName }}

---
This is an automated notification. Please do not reply to this email.
