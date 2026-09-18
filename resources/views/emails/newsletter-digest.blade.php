<x-mail::message>
# فعاليات هذا الأسبوع{{ $subscriber->area ? ' في '.$subscriber->area->name : ' في غزة' }}

هذه فعاليات الأيام السبعة القادمة كما نشرتها الفرق التطوعية. الحضور مجاني، ويكفي معرفة الموعد والمكان.

@foreach($days as $date => $dayEvents)
## {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('l j F') }}

@foreach($dayEvents as $event)
- **[{{ $event->title }}]({{ route('events.show', $event) }})** — الساعة {{ substr($event->start_time, 0, 5) }} · {{ $event->publicPlaceName() }}{{ $event->ageLabel() ? ' · '.$event->ageLabel() : '' }}
@endforeach

@endforeach
<x-mail::button :url="$browseUrl">
كل الفعاليات على الموقع
</x-mail::button>

نراكم في الفعاليات 💙

فريق {{ config('app.name') }}

<x-mail::subcopy>
تصلكم هذه النشرة لأنكم اشتركتم بها من موقع بَهْجَة. [إلغاء الاشتراك]({{ $unsubscribeUrl }}) بضغطة واحدة، أو [اقرؤوا نشرة الأسبوع على الموقع]({{ route('newsletter') }}).
</x-mail::subcopy>
</x-mail::message>
