@props(['user' => auth()->user(), 'datetime', 'format' => 'Y-m-d H:i'])

@php
    use Carbon\Carbon;
    
    if (is_string($datetime)) {
        $datetime = Carbon::parse($datetime);
    }
    
    $userTimezone = $user->timezone ?? 'Asia/Tokyo';
    $localTime = $datetime->timezone($userTimezone);
@endphp

<time datetime="{{ $datetime->toIso8601String() }}" title="{{ $localTime->format('Y-m-d H:i:s T') }}">
    {{ $localTime->format($format) }}
</time>
