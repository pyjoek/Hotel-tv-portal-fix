@extends('layout')
@section('content')
<h1>Countries</h1>
@if (!($has_playlist ?? true))
    <p class="empty">Missing public/index.m3u. Copy it from the original Hotel-tv-portal repo.</p>
@elseif (!($health_ready ?? false))
    <p class="empty">Showing all channels until you probe. Run: <code>php artisan channels:probe --country=TZ</code></p>
@else
    <p class="subtitle">Working only · {{ $total_channels }} channels · {{ count($countries) }} countries</p>
@endif
<form method="GET" action="{{ route('channels') }}" class="search-form">
    <input type="text" name="q" value="{{ $q }}" placeholder="Search country" class="search-input focusable" tabindex="0">
    <button type="submit" class="search-button focusable" tabindex="0">Search</button>
</form>
<div class="grid">
    <a href="{{ route('country.show', 'ALL') }}" class="tile preferred" tabindex="0">
        <span class="flag">📺</span>
        <span>All channels</span>
        <small>{{ $total_channels ?? 0 }}</small>
    </a>
    @forelse ($countries as $country)
        <a href="{{ route('country.show', $country['code']) }}" class="tile {{ $country['code'] === 'TZ' ? 'preferred' : '' }}" tabindex="0">
            <span class="flag">{{ $country['flag'] }}</span>
            <span>{{ $country['name'] }}</span>
            <small>{{ $country['count'] }}</small>
        </a>
    @empty
        @if ($has_playlist ?? true)
            <p class="empty">No working countries yet. Probe streams first.</p>
        @endif
    @endforelse
</div>
<a href="/" class="tile back" tabindex="0">Back</a>
@endsection
