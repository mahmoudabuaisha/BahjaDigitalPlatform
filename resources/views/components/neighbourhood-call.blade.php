@props(['user' => null, 'standing' => null, 'companions' => 0])

{{-- نداء الحيّ: عائلة لا تجد فعالية قريبة ترفع يدها، فتعرف الفرق أين
     ينتظر الأطفال. لا نطلب موقعاً — المرساة مأخوذة من حساب العائلة. --}}

@php
    $isFamily = $user?->isFamily() ?? false;
    $anchored = $isFamily && $user->hasLocationAnchor();
@endphp

<div {{ $attributes->merge(['class' => 'card overflow-hidden p-0']) }}>

    <div class="relative flex items-center gap-4 bg-gradient-to-l from-amber-700 to-amber-600 px-6 py-5">
        <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-white/25 text-white">
            <x-ui.icon name="hand-raised" class="size-6"/>
        </span>
        <div class="min-w-0">
            <h3 class="text-xl font-bold text-white">
                {{ $standing ? 'نداؤكم مسموع' : 'لا تجدون فعالية قريبة؟ ارفعوا أيديكم' }}
            </h3>
            <p class="mt-0.5 text-sm text-amber-50">
                {{ $standing
                    ? 'الفرق العاملة قرب مكانكم ترى أن هنا أطفالاً ينتظرون'
                    : 'نداء واحد يخبر الفرق أين ينتظر الأطفال — ومكانكم لا يظهر لأحد' }}
            </p>
        </div>
    </div>

    <div class="flex flex-col gap-4 p-6">

        @if(session('call_sent'))
            <p class="flex items-start gap-2 rounded-2xl bg-emerald-50 px-4 py-3 font-medium text-emerald-800">
                <x-ui.icon name="check" class="mt-0.5 size-5 shrink-0"/>
                {{ session('call_sent') }}
            </p>
        @endif

        @if(! $isFamily)
            {{-- زائر: النداء يحتاج حساباً كي نعرف أين يصل الجواب --}}
            <p class="text-ink-soft">
                أنشئوا حساباً وحدّدوا مكانكم مرّة واحدة، فيصل نداؤكم إلى الفرق العاملة قربكم
                ويصلكم الجواب فور إعلان فعالية.
            </p>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('register') }}" class="btn btn-primary">أنشئوا حساباً</a>
                <a href="{{ route('login') }}" class="btn btn-outline">لديكم حساب؟ ادخلوا</a>
            </div>

        @elseif(! $anchored)
            {{-- عائلة بلا مرساة: النداء بلا مكان لا يدلّ الفرق على شيء --}}
            <p class="text-ink-soft">
                حدّدوا محافظتكم وأقرب معلم إليكم، فيعرف الفريق أين يصل نداؤكم.
                لا نطلب موقعاً دقيقاً ولا يظهر مكانكم لأحد.
            </p>
            <a href="{{ route('account.profile') }}" class="btn btn-primary self-start">
                <x-ui.icon name="map-pin" class="size-5"/> حدّدوا مكانكم
            </a>

        @elseif($standing)
            {{-- نداء قائم: طمأنة ثم إمكانية السحب --}}
            <div class="flex flex-wrap items-center gap-3 rounded-2xl bg-amber-50 px-4 py-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-amber-100 text-amber-700">
                    <x-ui.icon name="hand-raised" class="size-5"/>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="font-bold text-amber-900">
                        @if($companions > 1)
                            {{ \App\Models\NeighbourhoodCall::familiesLabel($companions) }} من مكانكم تنتظر مثلكم
                        @else
                            رفعتم أيديكم {{ $standing->created_at->diffForHumans() }}
                        @endif
                    </p>
                    <p class="text-sm text-amber-800/80">
                        {{ $companions > 1
                            ? 'الفرق ترى هذا العدد — لا أسماءكم.'
                            : 'سنُعلمكم فور إعلان فعالية قريبة منكم.' }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <a href="{{ route('events.index') }}" class="btn btn-outline btn-sm">
                    تصفّحوا فعاليات المحافظات الأخرى
                </a>
                <form method="POST" action="{{ route('calls.destroy') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm font-semibold text-ink-soft underline-offset-4 hover:text-rose-600 hover:underline">
                        سحب النداء
                    </button>
                </form>
            </div>

        @else
            {{-- النموذج: ثلاثة حقول قصيرة لا أكثر، وكلها اختيارية عدا الضغط --}}
            <p class="text-ink-soft">
                لا فعاليات قريبة من <span class="font-bold text-ink">{{ $user->locationLabel() }}</span> بعد.
                أخبرونا كم طفلاً ينتظر وفي أي عمر، فيصل ذلك إلى الفرق العاملة في محيطكم.
            </p>

            <form method="POST" action="{{ route('calls.store') }}" class="flex flex-col gap-4">
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="field">
                        <span>أعمار أطفالكم</span>
                        <select name="age_band" class="input">
                            <option value="">كل الأعمار</option>
                            @foreach(\App\Http\Controllers\NeighbourhoodCallController::AGE_BANDS as $value => $label)
                                <option value="{{ $value }}" @selected(old('age_band') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="field">
                        <span>كم طفلاً ينتظر؟</span>
                        <select name="children_count" class="input">
                            @foreach(range(1, 8) as $count)
                                <option value="{{ $count }}" @selected((int) old('children_count', 1) === $count)>
                                    {{ $count === 1 ? 'طفل واحد' : ($count === 2 ? 'طفلان' : $count.' أطفال') }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <label class="field">
                    <span>شيء تودّون قوله للفرق <span class="font-normal text-ink-soft">(اختياري)</span></span>
                    <input type="text" name="note" maxlength="200" value="{{ old('note') }}"
                           class="input" placeholder="مثال: أغلب الأطفال هنا دون السادسة، ونحتاج نشاطاً قصيراً بعد الظهر">
                </label>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="btn btn-primary">
                        <x-ui.icon name="hand-raised" class="size-5"/> أرسلوا نداءكم
                    </button>
                    <p class="text-sm text-ink-soft">
                        يصل مجمَّعاً مع نداءات جيرانكم — بلا اسم ولا موقع دقيق.
                    </p>
                </div>
            </form>
        @endif
    </div>
</div>
