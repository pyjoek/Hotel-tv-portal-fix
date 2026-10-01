<?php

namespace App\Console\Commands;

use App\Services\ChannelCatalog;
use App\Services\ChannelHealth;
use Illuminate\Console\Command;

class ProbeChannels extends Command
{
    protected $signature = 'channels:probe
                            {--country= : Only probe this country code, e.g. TZ}
                            {--limit=0 : Max channels to probe (0 = all matching)}
                            {--concurrency=10 : Parallel requests per batch}';

    protected $description = 'Probe channel stream URLs and keep only working ones in the TV UI';

    public function handle(ChannelCatalog $catalog, ChannelHealth $health): int
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        ini_set('max_execution_time', '0');

        if (! $catalog->hasPlaylist()) {
            $this->error('public/index.m3u is missing.');

            return self::FAILURE;
        }

        $country = strtoupper((string) $this->option('country'));
        $limit = (int) $this->option('limit');
        $concurrency = max(1, (int) $this->option('concurrency'));

        $channels = $country !== ''
            ? $catalog->forCountry($country, null, false)
            : $catalog->channels(false);

        $unique = [];
        foreach ($channels as $channel) {
            $unique[$channel['url']] = $channel;
        }
        $channels = array_values($unique);

        if ($limit > 0) {
            $channels = array_slice($channels, 0, $limit);
        }

        $total = count($channels);
        if ($total === 0) {
            $this->warn('No channels to probe.');

            return self::SUCCESS;
        }

        $this->info("Probing {$total} channels".($country !== '' ? " for {$country}" : '')." (concurrency {$concurrency})...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $ok = 0;
        $fail = 0;

        foreach (array_chunk($channels, $concurrency) as $chunk) {
            $result = $health->probeMany($chunk, count($chunk));
            foreach ($chunk as $channel) {
                $row = $result['urls'][$channel['url']] ?? null;
                if ($row && ($row['ok'] ?? false)) {
                    $ok++;
                } else {
                    $fail++;
                }
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        $working = count(array_filter($health->all(), fn ($row) => ($row['ok'] ?? false) === true));
        $this->info("This run: {$ok} working, {$fail} dead.");
        $this->info("Health file total working URLs: {$working}");
        $this->line('TV UI lists working channels only. Re-run to refresh.');

        return self::SUCCESS;
    }
}
