@extends('layout')
@section('screen', 'player')
@section('content')
<div class="player"
     data-prev="{{ $prev ? route('player', ['url' => $prev['url'], 'name' => $prev['name'], 'country' => $country, 'i' => $index - 1]) : '' }}"
     data-next="{{ $next ? route('player', ['url' => $next['url'], 'name' => $next['name'], 'country' => $country, 'i' => $index + 1]) : '' }}">
    <div class="osd">
        <h1>{{ $name }}</h1>
        <p>Up / down changes channel · OK pauses · Back leaves</p>
    </div>
    <video id="video" autoplay playsinline></video>
    <p id="player-error" class="empty" hidden>This channel is offline or blocked by the provider. Press down for the next channel.</p>
</div>
<a href="{{ $back }}" class="tile back" tabindex="0">Back</a>
<script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.17"></script>
<script src="https://cdn.jsdelivr.net/npm/mpegts.js@1.7.3/dist/mpegts.min.js"></script>
<script>
    const video = document.getElementById('video');
    const errorBox = document.getElementById('player-error');
    const raw = @json($url);
    const kind = @json($kind);
    const proxied = @json(route('stream.proxy')) + '?url=' + encodeURIComponent(raw);

    function fail() {
        errorBox.hidden = false;
    }

    if (kind === 'hls' && window.Hls && Hls.isSupported()) {
        const hls = new Hls({ enableWorker: true, lowLatencyMode: true });
        hls.loadSource(proxied);
        hls.attachMedia(video);
        hls.on(Hls.Events.MANIFEST_PARSED, function () { video.play().catch(function () {}); });
        hls.on(Hls.Events.ERROR, function (event, data) {
            if (data && data.fatal) fail();
        });
    } else if (kind === 'ts' && window.mpegts && mpegts.getFeatureList().mseLivePlayback) {
        const player = mpegts.createPlayer({ type: 'mse', isLive: true, url: proxied });
        player.attachMediaElement(video);
        player.on(mpegts.Events.ERROR, fail);
        player.load();
        player.play();
    } else if (video.canPlayType('application/vnd.apple.mpegurl') && kind === 'hls') {
        video.src = proxied;
        video.play().catch(fail);
    } else {
        video.src = proxied;
        video.addEventListener('error', fail);
        video.play().catch(fail);
    }
</script>
@endsection
