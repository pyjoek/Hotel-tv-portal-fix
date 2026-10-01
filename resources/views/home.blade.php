@extends('layout')
@section('screen', 'home')
@section('content')
<main>
    <img src="{{ asset('/images/logo.png') }}" alt="Hotel" class="logo">
    <p class="subtitle">{{ $hotel['name'] }} · {{ $hotel['location'] }}</p>
    <div class="grid">
        <a href="{{ route('country.show', 'TZ') }}" class="tile preferred" tabindex="0">
            <img src="{{ asset('/images/channel.png') }}" alt="">
            <span>Live TV</span>
        </a>
        <a href="{{ route('channels') }}" class="tile" tabindex="0">
            <img src="{{ asset('/images/channel.jpg') }}" alt="">
            <span>Countries</span>
        </a>
        <a href="/hotel" class="tile" tabindex="0">
            <span>Hotel Info</span>
        </a>
        <a href="/menu" class="tile" tabindex="0">
            <span>Food &amp; Bar</span>
        </a>
        <a href="/contacts" class="tile" tabindex="0">
            <img src="{{ asset('/images/telephone.png') }}" alt="">
            <span>Contacts</span>
        </a>
        @foreach ($hotel['links'] as $link)
            <a href="{{ $link['url'] }}" class="tile" tabindex="0">
                <span>{{ $link['name'] }}</span>
            </a>
        @endforeach
    </div>
</main>
@endsection
