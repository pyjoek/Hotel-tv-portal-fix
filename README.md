# Hotel TV Portal fix

Copy these files onto https://github.com/Joel-In-Action/Hotel-tv-portal. The GitHub connection could not push to the Joel-In-Action org.

Keep the existing public/index.m3u, public/countries_metadata.json, and public/images.

Phone and Android TV client: https://github.com/pyjoek/Hotel-tv-apps

## What changed

- Directional remote navigation, including TV key codes, OK, and Back.
- Channels are grouped by country and paged, instead of rendering the whole playlist.
- Player proxies streams through /stream, uses hls.js for HLS and mpegts.js for MPEG-TS.
- JSON API under /api for the Flutter apps.
