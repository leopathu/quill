Hi {{ $removedUser->name }},

You have been removed from the project "{{ $project->name }}".

----------------------------------------
PROJECT MEMBERSHIP UPDATE
----------------------------------------
Project    : {{ $project->name }}
Removed by : {{ $removedBy->name }}

----------------------------------------

If you believe this was done in error, please contact your project manager.

Log in to {{ $appName }}:
{{ $appUrl }}

Best regards,
{{ $appName }}

---
This is an automated notification. Please do not reply to this email.
