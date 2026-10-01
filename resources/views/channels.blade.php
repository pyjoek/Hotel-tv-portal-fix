@extends('layout')
@section('content')
<h1>Countries</h1>
<form method="GET" action="{{ route('channels') }}" class="search-form">
    <input type="text" name="q" value="{{ $q }}" placeholder="Search country" class="search-input focusable" tabindex="0">
    <button type="submit" class="search-button focusable" tabindex="0">Search</button>
</form>
<div class="grid">
    @forelse ($countries as $country)
        <a href="{{ route('country.show', $country['code']) }}" class="tile {{ $country['code'] === 'TZ' ? 'preferred' : '' }}" tabindex="0">
            <span class="flag">{{ $country['flag'] }}</span>
            <span>{{ $country['name'] }}</span>
            <small>{{ $country['count'] }}</small>
        </a>
    @empty
        <p class="empty">No countries match that search.</p>
    @endforelse
</div>
<a href="/" class="tile back" tabindex="0">Back</a>
@endsection
