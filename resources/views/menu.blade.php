@extends('layout')
@section('content')
<h1>Food &amp; Bar</h1>
<div class="grid">
    @foreach ($hotel['menu'] as $section)
        <div class="tile static" tabindex="0">
            <span>{{ $section['name'] }}</span>
            <small>{{ $section['hours'] }}</small>
            <small>{{ implode(' · ', $section['items']) }}</small>
        </div>
    @endforeach
</div>
<a href="/" class="tile back" tabindex="0">Back</a>
@endsection
