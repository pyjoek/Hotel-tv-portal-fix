@extends('layout')
@section('content')
<h1>Contacts</h1>
<div class="hotel">
    @foreach ($hotel['contacts'] as $contact)
        <p class="contact-row">
            <img src="{{ asset('/images/phone.png') }}" alt="">
            <span>{{ $contact['name'] }}: {{ $contact['ext'] }}</span>
        </p>
    @endforeach
</div>
<a href="/" class="tile back" tabindex="0">Back</a>
@endsection
