<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ChannelCatalog
{
    public function channels(): array
    {
        $path = public_path('index.m3u');
        $mtime = is_file($path) ? filemtime($path) : 0;

        return Cache::remember('iptv.channels.v3.'.$mtime, now()->addHour(), function () use ($path) {
            if (! is_file($path)) {
                return [];
            }

            return $this->parse((string) file_get_contents($path));
        });
    }

    public function countries(): array
    {
        $meta = $this->metadata();
        $counts = [];

        foreach ($this->channels() as $channel) {
            $code = strtoupper((string) ($channel['country_code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $counts[$code] = ($counts[$code] ?? 0) + 1;
        }

        $countries = [];
        foreach ($counts as $code => $count) {
            $info = $meta[$code] ?? [];
            $countries[] = [
                'code' => $code,
                'name' => $info['country'] ?? $code,
                'flag' => $info['flag'] ?? '🏳️',
                'count' => $count,
            ];
        }

        usort($countries, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $countries;
    }

    public function forCountry(?string $code, ?string $query = null): array
    {
        $code = strtoupper(trim((string) $code));
        $query = strtolower(trim((string) $query));

        return array_values(array_filter($this->channels(), function ($channel) use ($code, $query) {
            if ($code !== '' && $code !== 'ALL') {
                if (strtoupper((string) ($channel['country_code'] ?? '')) !== $code) {
                    return false;
                }
            }
            if ($query !== '' && ! str_contains(strtolower($channel['name']), $query)) {
                return false;
            }

            return true;
        }));
    }

    public function page(array $channels, int $page, int $perPage = 40): array
    {
        $page = max(1, $page);
        $total = count($channels);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $slice = array_slice($channels, ($page - 1) * $perPage, $perPage);

        return [
            'data' => array_values($slice),
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'per_page' => $perPage,
        ];
    }

    public function hasPlaylist(): bool
    {
        return is_file(public_path('index.m3u')) && filesize(public_path('index.m3u')) > 0;
    }

    public function parse(string $m3uText): array
    {
        $meta = $this->metadata();
        $lines = preg_split("/\r\n|\n|\r/", $m3uText);
        $channels = [];

        for ($i = 0; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (! str_starts_with($line, '#EXTINF:')) {
                continue;
            }

            $url = $this->nextUrl($lines, $i + 1);
            if ($url === null) {
                continue;
            }

            $name = trim(str_contains($line, ',') ? substr($line, strrpos($line, ',') + 1) : 'Channel');
            if ($this->blocked($name)) {
                continue;
            }

            $countryCode = $this->countryFromLine($line);
            $info = $countryCode ? ($meta[strtoupper($countryCode)] ?? []) : [];

            $channels[] = [
                'name' => $name,
                'url' => $url,
                'logo' => $this->attr($line, 'tvg-logo'),
                'group' => $this->attr($line, 'group-title'),
                'country_code' => $countryCode,
                'country_name' => $info['country'] ?? ($countryCode ? strtoupper($countryCode) : 'Unknown'),
                'flag' => $info['flag'] ?? '🏳️',
                'kind' => str_contains(strtolower($url), '.m3u8') ? 'hls' : (preg_match('/\.ts(\?|$)/i', $url) ? 'ts' : 'file'),
            ];
        }

        return $channels;
    }

    private function nextUrl(array $lines, int $start): ?string
    {
        for ($j = $start; $j < count($lines); $j++) {
            $candidate = trim($lines[$j]);
            if ($candidate === '') {
                continue;
            }
            if (str_starts_with($candidate, '#EXTINF:')) {
                return null;
            }
            if (str_starts_with($candidate, '#')) {
                continue;
            }
            if (preg_match('#^https?://#i', $candidate)) {
                return $candidate;
            }

            return null;
        }

        return null;
    }

    private function countryFromLine(string $line): ?string
    {
        $fromAttr = $this->attr($line, 'tvg-country');
        if ($fromAttr) {
            $code = strtolower(explode(';', $fromAttr)[0]);
            if (preg_match('/^[a-z]{2}$/', $code)) {
                return $code;
            }
        }

        // iptv-org style: tvg-id="Something.tz@SD"
        $id = (string) $this->attr($line, 'tvg-id');
        if (preg_match('/\.([a-z]{2})(?:@|$)/i', $id, $match)) {
            return strtolower($match[1]);
        }

        return null;
    }

    private function metadata(): array
    {
        $path = public_path('countries_metadata.json');
        if (! is_file($path)) {
            return [];
        }

        return json_decode((string) file_get_contents($path), true) ?: [];
    }

    private function blocked(string $name): bool
    {
        $lower = strtolower($name);

        return str_contains($lower, 'pluto')
            || str_contains($lower, 'gecko')
            || str_contains($lower, 'geo-blocked')
            || str_contains($lower, 'not 24/7');
    }

    private function attr(string $line, string $name): ?string
    {
        if (preg_match('/'.preg_quote($name, '/').'="([^"]*)"/i', $line, $match)) {
            return $match[1] !== '' ? $match[1] : null;
        }

        return null;
    }
}
