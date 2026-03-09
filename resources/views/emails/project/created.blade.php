Hi {{ $orgOwner->name }},

A new project has been created in your organisation.

----------------------------------------
PROJECT DETAILS
----------------------------------------
Project    : {{ $project->name }}
Created by : {{ $createdBy->name }}
@if($project->description)
Description: {{ $project->description }}
@endif
Created at : {{ $project->created_at->format('d M Y, H:i') }}

----------------------------------------

Log in to {{ $appName }} to manage this project:
{{ $appUrl }}

Best regards,
{{ $appName }}

---
This is an automated notification. Please do not reply to this email.
