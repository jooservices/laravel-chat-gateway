<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Console\Commands;

use Illuminate\Console\Command;
use JOOservices\LaravelChatGateway\Contracts\Services\PollingServiceContract;
use JOOservices\LaravelChatGateway\DTOs\PollingBatchOptionsDto;

final class GatewayPollCommand extends Command
{
    protected $signature = 'gateway:poll
        {provider : The provider name, for example telegram}
        {--channel= : Channel key override}
        {--once : Poll once and exit}
        {--timeout= : Long-poll timeout in seconds}
        {--limit= : Maximum updates per batch}
        {--sleep=1 : Sleep seconds between batches when not using --once}
        {--reset : Reset the stored polling offset before polling}';

    protected $description = 'Poll inbound updates for a provider channel.';

    public function __construct(
        private readonly PollingServiceContract $pollingService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $provider = (string) $this->argument('provider');
        $options = new PollingBatchOptionsDto(
            channelKey: $this->option('channel') !== null ? (string) $this->option('channel') : null,
            timeout: $this->option('timeout') !== null ? (int) $this->option('timeout') : null,
            limit: $this->option('limit') !== null ? (int) $this->option('limit') : null,
            resetOffset: (bool) $this->option('reset'),
        );

        do {
            $result = $this->pollingService->poll($provider, $options);

            $this->line(sprintf(
                'provider=%s channel=%s fetched=%d processed=%d deduplicated=%d failed=%d offset=%d',
                $result->provider,
                $result->channelKey,
                $result->fetchedCount,
                $result->processedCount,
                $result->deduplicatedCount,
                $result->failedCount,
                $result->offset,
            ));

            if ((bool) $this->option('once')) {
                return self::SUCCESS;
            }

            $options = new PollingBatchOptionsDto(
                channelKey: $options->channelKey,
                timeout: $options->timeout,
                limit: $options->limit,
                allowedUpdates: $options->allowedUpdates,
                resetOffset: false,
            );

            sleep(max(1, (int) $this->option('sleep')));
        } while (true);
    }
}