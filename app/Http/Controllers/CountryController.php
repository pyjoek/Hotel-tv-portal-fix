<?php

namespace App\Http\Controllers;

use App\Services\ChannelCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CountryController extends Controller
{
    public function __construct(private ChannelCatalog $catalog)
    {
    }

    public function countries(Request $request)
    {
        $onlyWorking = ! $request->boolean('all');
        $countries = $this->catalog->countries($onlyWorking);
        $q = trim((string) $request->query('q', ''));

        if ($q !== '') {
            $countries = array_values(array_filter($countries, function ($country) use ($q) {
                return str_contains(strtolower($country['name'].' '.$country['code']), strtolower($q));
            }));
        }

        return view('channels', [
            'countries' => $countries,
            'q' => $q,
            'has_playlist' => $this->catalog->hasPlaylist(),
            'total_channels' => count($this->catalog->channels($onlyWorking)),
            'health_ready' => $this->catalog->healthReady(),
            'only_working' => $onlyWorking,
        ]);
    }

    public function showChannels(Request $request, string $country_code)
    {
        $onlyWorking = ! $request->boolean('all');
        $q = trim((string) $request->query('q', ''));
        $page = (int) $request->query('page', 1);
        $filtered = $this->catalog->forCountry($country_code, $q, $onlyWorking);
        $result = $this->catalog->page($filtered, $page);
        $meta = collect($this->catalog->countries($onlyWorking))->firstWhere('code', strtoupper($country_code));

        return view('show', [
            'channels' => $result['data'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'total' => $result['total'],
            'country' => $meta ?? [
                'code' => strtoupper($country_code),
                'name' => strtoupper($country_code) === 'ALL' ? 'All channels' : strtoupper($country_code),
                'flag' => '🏳️',
            ],
            'q' => $q,
            'has_playlist' => $this->catalog->hasPlaylist(),
            'health_ready' => $this->catalog->healthReady(),
            'only_working' => $onlyWorking,
        ]);
    }

    public function player(Request $request)
    {
        $url = (string) $request->query('url', '');
        $name = (string) $request->query('name', 'Channel');
        $country = strtoupper((string) $request->query('country', ''));
        $index = (int) $request->query('i', 0);

        if (! preg_match('#^https?://#i', $url)) {
            abort(400, 'Missing stream url');
        }

        $list = $country !== '' ? $this->catalog->forCountry($country) : [];
        $prev = $index > 0 && isset($list[$index - 1]) ? $list[$index - 1] : null;
        $next = isset($list[$index + 1]) ? $list[$index + 1] : null;

        return view('watch', [
            'url' => $url,
            'name' => $name,
            'kind' => str_contains(strtolower($url), '.m3u8') ? 'hls' : (preg_match('/\.ts(\?|$)/i', $url) ? 'ts' : 'file'),
            'country' => $country,
            'index' => $index,
            'prev' => $prev,
            'next' => $next,
            'back' => $country !== '' ? route('country.show', $country) : route('channels'),
        ]);
    }

    public function proxy(Request $request)
    {
        $url = (string) $request->query('url', '');
        $this->assertPublicHttp($url);

        try {
            $upstream = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (SMART-TV; Linux; Tizen 6.5) AppleWebKit/537.36 HotelTV/1.0',
                'Accept' => '*/*',
            ])->withOptions([
                'allow_redirects' => true,
                'timeout' => 20,
            ])->get($url);
        } catch (\Throwable $e) {
            abort(502, 'Stream provider did not respond');
        }

        if (! $upstream->successful()) {
            abort($upstream->status() ?: 502, 'Stream provider rejected the request');
        }

        $body = $upstream->body();
        $type = strtolower($upstream->header('Content-Type') ?? '');
        $path = strtolower(parse_url($url, PHP_URL_PATH) ?? '');
        $isPlaylist = str_contains($type, 'mpegurl')
            || str_contains($type, 'm3u')
            || str_ends_with($path, '.m3u8')
            || str_starts_with(ltrim($body), '#EXTM3U');

        if ($isPlaylist) {
            return response($this->rewritePlaylist($body, $url), 200, [
                'Content-Type' => 'application/vnd.apple.mpegurl',
                'Cache-Control' => 'no-cache',
                'Access-Control-Allow-Origin' => '*',
            ]);
        }

        $contentType = $type !== '' ? $upstream->header('Content-Type') : 'video/mp2t';

        return response($body, 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'no-cache',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    private function rewritePlaylist(string $body, string $base): string
    {
        $lines = preg_split("/\r\n|\n|\r/", $body);
        $out = [];

        foreach ($lines as $line) {
            if ($line === '') {
                $out[] = $line;
                continue;
            }

            if (str_starts_with($line, '#')) {
                $out[] = preg_replace_callback('/URI="([^"]+)"/', function ($match) use ($base) {
                    return 'URI="'.$this->proxyUrl($this->absoluteUrl($match[1], $base)).'"';
                }, $line);
                continue;
            }

            $out[] = $this->proxyUrl($this->absoluteUrl(trim($line), $base));
        }

        return implode("\n", $out);
    }

    private function proxyUrl(string $url): string
    {
        return route('stream.proxy', ['url' => $url]);
    }

    private function absoluteUrl(string $ref, string $base): string
    {
        if (preg_match('#^https?://#i', $ref)) {
            return $ref;
        }

        $parts = parse_url($base);
        $origin = ($parts['scheme'] ?? 'http').'://'.($parts['host'] ?? '');
        if (! empty($parts['port'])) {
            $origin .= ':'.$parts['port'];
        }

        if (str_starts_with($ref, '/')) {
            return $origin.$ref;
        }

        $dir = isset($parts['path']) ? preg_replace('#/[^/]*$#', '/', $parts['path']) : '/';

        return $origin.$dir.$ref;
    }

    private function assertPublicHttp(string $url): void
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            abort(400, 'Invalid stream url');
        }

        $parts = parse_url($url);
        if (! in_array($parts['scheme'] ?? '', ['http', 'https'], true)) {
            abort(400, 'Only http streams can be proxied');
        }

        $host = $parts['host'] ?? '';
        $ip = gethostbyname($host);
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            abort(403, 'Private stream hosts are blocked');
        }
    }
}
