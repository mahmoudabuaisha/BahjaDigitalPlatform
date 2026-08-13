{{-- إدخال تقييم بالنجوم — يعمل بلا JavaScript: input:checked ~ label يلوّن النجوم التالية في DOM --}}
<div class="star-rating" dir="ltr">
    @for($i = 5; $i >= 1; $i--)
        <input type="radio" id="star-{{ $i }}" name="rating" value="{{ $i }}" @if($i === 1) required @endif>
        <label for="star-{{ $i }}" title="{{ $i }} من 5">★</label>
    @endfor
</div>

@once
<style>
    .star-rating { display: inline-flex; flex-direction: row-reverse; gap: .25rem; }
    .star-rating input { position: absolute; opacity: 0; width: 0; height: 0; }
    .star-rating label { cursor: pointer; font-size: 2.2rem; line-height: 1; color: #d1d5db; transition: color .15s; }
    .star-rating input:checked ~ label,
    .star-rating label:hover,
    .star-rating label:hover ~ label { color: #ff9d37; }
    .star-rating input:focus-visible + label { outline: 2px solid #f06406; outline-offset: 2px; border-radius: .25rem; }
</style>
@endonce
