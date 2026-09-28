@php($credit = $credit())
@if ($credit)
    <span {{ $attributes->merge(['class' => 'unsplash-attribution']) }}>{!! $credit->toHtml() !!}</span>
@endif
