{{-- إدخال تقييم بالنجوم — يعمل بلا JavaScript: input:checked ~ label يلوّن النجوم التالية في DOM --}}
<div class="star-rating" dir="ltr">
    @for($i = 5; $i >= 1; $i--)
        <input type="radio" id="star-{{ $i }}" name="rating" value="{{ $i }}" @if($i === 1) required @endif>
        <label for="star-{{ $i }}" title="{{ $i }} من 5"><span aria-hidden="true">★</span><span class="sr-only">{{ $i }} من 5</span></label>
    @endfor
</div>

@once
    <style>
        .star-rating { display: inline-flex; flex-direction: row-reverse; gap: 6px; }
        .star-rating input { position: absolute; opacity: 0; width: 0; height: 0; }
        .star-rating label {
            display: grid;
            place-items: center;
            min-width: 46px;
            min-height: 46px;
            font-size: 30px;
            line-height: 1;
            color: #d7d3e4;
            border: 1px solid #d7d3e4;
            border-radius: 14px;
            cursor: pointer;
            transition: color .15s, border-color .15s;
        }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: var(--color-joy-amber);
            border-color: var(--color-joy-amber);
        }
        .star-rating input:focus-visible + label {
            outline: 2px solid var(--color-brand-400);
            outline-offset: 2px;
        }
    </style>
@endonce
