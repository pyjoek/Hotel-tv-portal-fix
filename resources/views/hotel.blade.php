@extends('layout')
@section('content')
<h1>Hotel Information</h1>
<div class="hotel">
    <p>{{ $hotel['location'] }}</p>
    <p>Reception: {{ $hotel['reception'] }}</p>
    <p>Breakfast: {{ $hotel['breakfast'] }}</p>
    <p>{{ $hotel['note'] }}</p>
</div>
<a href="/" class="tile back" tabindex="0">Back</a>
@endsection
