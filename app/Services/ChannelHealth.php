<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class ChannelHealth
{
    private ?array $urls = null;

    public function path(): string
    {
        return storage_path('app/channel_health.json');
    }

    public function all(): array
    {
        if ($this->urls !== null) {
            return $this->urls;
        }

        $path = $this->path();
        if (! is_file($path)) {
            return $this->urls = [];
        }

        $data = json_decode((string) file_get_contents($path), true);
        $this->urls = is_array($data['urls'] ?? null) ? $data['urls'] : [];

        return $this->urls;
    }

    public function hasData(): bool
    {
        return count($this->all()) > 0;
    }

    public function isWorking(string $url): bool
    {
        $row = $this->all()[$url] ?? null;

        return is_array($row) && ($row['ok'] ?? false) === true;
    }

    public function save(array $urls): void
    {
        $dir = dirname($this->path());
        if (! is_dir($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        file_put_contents($this->path(), json_encode([
            'updated_at' => now()->toIso8601String(),
            'urls' => $urls,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->urls = $urls;
    }

    public function probeOne(string $url): bool
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (SMART-TV; Linux; Tizen 6.5) AppleWebKit/537.36 HotelTV/1.0',
                'Accept' => '*/*',
            ])->withOptions([
                'allow_redirects' => true,
                'timeout' => 6,
                'connect_timeout' => 4,
            ])->get($url);

            if (! $response->successful()) {
                return false;
            }

            $type = strtolower($response->header('Content-Type') ?? '');
            $body = substr((string) $response->body(), 0, 2048);

            if (str_starts_with(ltrim($body), '#EXTM3U') || str_contains($body, '#EXTINF')) {
                return true;
            }

            if (str_contains($type, 'mpegurl') || str_contains($type, 'm3u') || str_contains($type, 'video') || str_contains($type, 'mpeg') || str_contains($type, 'octet-stream')) {
                return strlen($body) > 0;
            }

            return strlen($body) > 32;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @param  array<int, array{url:string,name?:string}>  $channels
     * @return array{ok:int,fail:int,urls:array<string,array{ok:bool,checked_at:string,name?:string}>}
     */
    public function probeMany(array $channels, int $concurrency = 15): array
    {
        $existing = $this->all();
        $ok = 0;
        $fail = 0;

        foreach (array_chunk($channels, max(1, $concurrency)) as $chunk) {
            $responses = Http::pool(function ($pool) use ($chunk) {
                foreach ($chunk as $i => $channel) {
                    $pool->as((string) $i)->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (SMART-TV; Linux; Tizen 6.5) AppleWebKit/537.36 HotelTV/1.0',
                        'Accept' => '*/*',
                    ])->withOptions([
                        'allow_redirects' => true,
                        'timeout' => 6,
                        'connect_timeout' => 4,
                    ])->get($channel['url']);
                }
            });

            foreach ($chunk as $i => $channel) {
                $url = $channel['url'];
                $response = $responses[(string) $i] ?? null;
                $good = false;

                try {
                    if ($response && ! $response instanceof \Throwable && method_exists($response, 'successful') && $response->successful()) {
                        $type = strtolower($response->header('Content-Type') ?? '');
                        $body = substr((string) $response->body(), 0, 2048);
                        $good = str_starts_with(ltrim($body), '#EXTM3U')
                            || str_contains($body, '#EXTINF')
                            || str_contains($type, 'mpegurl')
                            || str_contains($type, 'm3u')
                            || str_contains($type, 'video')
                            || str_contains($type, 'mpeg')
                            || str_contains($type, 'octet-stream')
                            || strlen($body) > 32;
                    }
                } catch (\Throwable $e) {
                    $good = false;
                }

                $existing[$url] = [
                    'ok' => $good,
                    'checked_at' => now()->toIso8601String(),
                    'name' => $channel['name'] ?? null,
                ];
                $good ? $ok++ : $fail++;
            }
        }

        $this->save($existing);

        return ['ok' => $ok, 'fail' => $fail, 'urls' => $existing];
    }
}
