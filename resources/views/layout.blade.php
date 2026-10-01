<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hotel TV</title>
    <link rel="stylesheet" href="{{ asset('/css/tv.css') }}">
</head>
<body data-screen="@yield('screen', 'browse')">
<div class="container">
    @yield('content')
</div>
<div class="hint">Arrows move · OK opens · Back returns</div>
<script src="{{ asset('/js/remote.js') }}"></script>
</body>
</html>
