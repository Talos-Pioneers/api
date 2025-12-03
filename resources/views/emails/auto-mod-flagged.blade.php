<x-mail::message>
# AutoMod Alert: Flagged {{ ucfirst($contentType) }}

Content has been automatically flagged for review by the moderation system.

## Content Details

**Type:** {{ ucfirst($contentType) }}

**Title:** {{ $contentTitle }}

@if($author)
**Author:** {{ $author->username }} ({{ $author->email }})
@else
**Author:** Anonymous / Guest
@endif

---

@if(count($flaggedTexts) > 0)
## Flagged Text Content

@foreach($flaggedTexts as $flaggedText)
**Field:** {{ $flaggedText['text']['label'] ?? 'Unknown' }}

**Content:**
> {{ Str::limit($flaggedText['text']['text'], 500) }}

**Violated Categories:**

<x-mail::table>
| Category | Score |
|:---------|------:|
@foreach($flaggedText['categories'] as $category => $violated)
@if($violated)
| {{ str_replace(['/', '-'], [' / ', ' '], ucfirst($category)) }} | {{ number_format($flaggedText['category_scores'][$category] ?? 0, 2) }} |
@endif
@endforeach
</x-mail::table>

@endforeach
@endif
---

@if (count($flaggedImages) > 0)
## Flagged Images

@foreach($flaggedImages as $flaggedImage)
**Image:** {{ $flaggedImage['image'] }}

**Violated Categories:**

<x-mail::table>
| Category | Score |
|:---------|------:|
@foreach($flaggedImage['categories'] as $category => $violated)
@if($violated)
| {{ str_replace(['/', '-'], [' / ', ' '], ucfirst($category)) }} | {{ number_format($flaggedImage['category_scores'][$category] ?? 0, 2) }} |
@endif
@endforeach
</x-mail::table>

@endforeach
@endif

This content has been placed in review status and requires manual approval.

@if($reviewUrl)
<x-mail::button :url="$reviewUrl">
Review Content
</x-mail::button>
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
