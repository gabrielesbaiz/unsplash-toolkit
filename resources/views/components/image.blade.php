@php
    $src = $src();
    $credit = $credit();
@endphp

@if ($src !== '')
    <figure {{ $attributes->merge(['class' => 'unsplash-figure']) }}>
        <img
            src="{{ $src }}"
            srcset="{{ $srcset() }}"
            sizes="{{ $sizesAttribute }}"
            alt="{{ $altText() }}"
            @if ($width) width="{{ $width }}" @endif
            @if ($height) height="{{ $height }}" @endif
            style="background-color: {{ $placeholder() }};"
            @if ($isLazy()) loading="lazy" @endif
            decoding="async"
        >

        @if ($credit)
            <figcaption class="unsplash-attribution">{!! $credit->toHtml() !!}</figcaption>
        @endif
    </figure>
@endif
