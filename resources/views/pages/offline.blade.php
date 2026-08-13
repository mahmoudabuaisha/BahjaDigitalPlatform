@extends('layouts.app')

@section('title', 'دون اتصال — '.\App\Support\Settings::get('site_name'))

@section('content')
<div class="mx-auto max-w-md py-10 text-center">
    <p class="text-5xl">📡</p>
    <h1 class="mt-4 text-2xl font-bold text-gray-900">لا يوجد اتصال بالإنترنت</h1>
    <p class="mt-3 text-sm leading-relaxed text-gray-500">
        هذه الصفحة لم تُحفظ في جهازكم بعد.<br>
        الصفحات التي زرتموها سابقاً — مثل الروزنامة الرئيسية — تعمل دون إنترنت.
    </p>
    <a href="{{ route('home') }}"
       class="mt-6 inline-block rounded-xl bg-joy-500 px-6 py-3 font-bold text-white shadow-sm">
        العودة للروزنامة المحفوظة
    </a>
</div>
@endsection
