@extends('layout')
@section('content')
<h1>{{ $country['flag'] }} {{ $country['name'] }}</h1>
<p class="subtitle">{{ $total }} channels · page {{ $page }} of {{ $pages }}</p>
<form method="GET" action="{{ route('country.show', $country['code']) }}" class="search-form">
    <input type="text" name="q" value="{{ $q }}" placeholder="Search channels" class="search-input focusable" tabindex="0">
    <button type="submit" class="search-button focusable" tabindex="0">Search</button>
</form>
<div class="grid channels">
    @forelse ($channels as $offset => $channel)
        @php $index = (($page - 1) * 40) + $offset; @endphp
        <a href="{{ route('player', ['url' => $channel['url'], 'name' => $channel['name'], 'country' => $country['code'], 'i' => $index]) }}" class="tile channel" tabindex="0">
            @if (!empty($channel['logo']))
                <img src="{{ $channel['logo'] }}" alt="" class="channel-logo">
            @endif
            <span>{{ $channel['name'] }}</span>
        </a>
    @empty
        <p class="empty">No channels found.</p>
    @endforelse
</div>
<div class="pager">
    @if ($page > 1)
        <a class="tile pager-btn" tabindex="0" href="{{ route('country.show', ['country_code' => $country['code'], 'q' => $q, 'page' => $page - 1]) }}">Previous</a>
    @endif
    @if ($page < $pages)
        <a class="tile pager-btn" tabindex="0" href="{{ route('country.show', ['country_code' => $country['code'], 'q' => $q, 'page' => $page + 1]) }}">Next</a>
    @endif
</div>
<a href="{{ route('channels') }}" class="tile back" tabindex="0">Back</a>
@endsection
