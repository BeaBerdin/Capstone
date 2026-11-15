{{-- Plain-text part: not HTML, so values are printed as written. --}}
{!! $heading !!}

@if (filled($name ?? null))
Hi {!! $name !!},

@endif
@foreach ($paragraphs as $paragraph)
{!! $paragraph !!}

@endforeach
@if (! empty($highlight))
{!! $highlight['label'] !!}:
{!! $highlight['text'] !!}

@endif
@if (filled($buttonText ?? null) && filled($buttonUrl ?? null))
{!! $buttonText !!}:
{!! $buttonUrl !!}

@if (filled($note ?? null))
{!! $note !!}

@endif
@endif
-- PathWise
