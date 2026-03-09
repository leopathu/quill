Hi {{ $recipient->name }},

A new document has been created in the project "{{ $project->name }}".

----------------------------------------
DOCUMENT DETAILS
----------------------------------------
Document : {{ $document->title }}
Project  : {{ $project->name }}
Created  : {{ $document->created_at->format('d M Y, H:i') }}
Author   : {{ $author->name }}
@if($document->parent_id)
(This document is a sub-page of an existing document.)
@endif

----------------------------------------

Log in to {{ $appName }} to view this document:
{{ $appUrl }}

Best regards,
{{ $appName }}

---
This is an automated notification. Please do not reply to this email.
