@props(['content'])

{{-- نص من إعدادات اللوحة: سطر فارغ بين الفقرتين = فقرة جديدة --}}
<div class="mt-6 flex flex-col gap-4">
    @foreach(preg_split('/\R{2,}/u', trim($content)) as $paragraph)
        <p class="whitespace-pre-line leading-relaxed text-ink-soft">{{ trim($paragraph) }}</p>
    @endforeach
</div>
