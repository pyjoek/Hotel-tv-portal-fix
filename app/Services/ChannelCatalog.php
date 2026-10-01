<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ChannelCatalog
{
    public function channels(): array
    {
        return Cache::remember('iptv.channels.v2', now()->addHour(), function () {
            $path = public_path('index.m3u');
            if (! is_file($path)) {
                return [];
            }

            return $this->parse(file_get_contents($path));
        });
    }

    public function countries(): array
    {
        $meta = json_decode(file_get_contents(public_path('countries_metadata.json')), true) ?: [];
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
        $code = strtoupper((string) $code);
        $query = strtolower(trim((string) $query));

        return array_values(array_filter($this->channels(), function ($channel) use ($code, $query) {
            if ($code !== '' && strtoupper((string) $channel['country_code']) !== $code) {
                return false;
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

    public function parse(string $m3uText): array
    {
        $meta = json_decode(@file_get_contents(public_path('countries_metadata.json')), true) ?: [];
        $lines = preg_split("/\r\n|\n|\r/", $m3uText);
        $channels = [];

        for ($i = 0; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (! str_starts_with($line, '#EXTINF:')) {
                continue;
            }

            $url = trim($lines[$i + 1] ?? '');
            if (! preg_match('#^https?://#i', $url)) {
                continue;
            }

            $name = trim(str_contains($line, ',') ? substr($line, strrpos($line, ',') + 1) : 'Channel');
            if ($this->blocked($name)) {
                continue;
            }

            $countryCode = $this->attr($line, 'tvg-country');
            $countryCode = $countryCode ? strtolower(explode(';', $countryCode)[0]) : null;
            $info = $countryCode ? ($meta[strtoupper($countryCode)] ?? []) : [];

            $channels[] = [
                'name' => $name,
                'url' => $url,
                'logo' => $this->attr($line, 'tvg-logo'),
                'group' => $this->attr($line, 'group-title'),
                'country_code' => $countryCode,
                'country_name' => $info['country'] ?? 'Unknown',
                'flag' => $info['flag'] ?? '🏳️',
                'kind' => str_contains(strtolower($url), '.m3u8') ? 'hls' : (preg_match('/\.ts(\?|$)/i', $url) ? 'ts' : 'file'),
            ];
        }

        return $channels;
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
