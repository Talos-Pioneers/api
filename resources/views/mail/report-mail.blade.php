<x-mail::message>
# New Report

A new report has been submitted.

## Report Details

**Report ID:** {{ $report->id }}

**Reportable Type:** {{ $report->reportable_type }}

**Reportable ID:** {{ $report->reportable_id }}

**Reason:** {{ $report->reason }}

**Reported By:** {{ $report->user?->username }} ({{ $report->user?->email }})

<x-mail::button :url="$url" color="success">
View Report
</x-mail::button>   

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
