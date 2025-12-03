<x-mail::message>
# New Report

A new report has been submitted.

## Report Details

**Report ID:** {{ $report->id }}

**Reportable Type:** {{ $report->reportable_type }}

**Reportable ID:** {{ $report->reportable_id }}

**Reason:** {{ $report->reason }}

**Reported By:** {{ $report->user?->username }} ({{ $report->user?->email }})

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
