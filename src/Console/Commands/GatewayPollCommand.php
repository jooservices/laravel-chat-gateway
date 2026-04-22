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
        $providerArgument = $this->argument('provider');

        if (! is_scalar($providerArgument) || $providerArgument === '') {
            $this->error('Provider argument is required.');

            return self::FAILURE;
        }

        $provider = (string) $providerArgument;
        $channel = $this->option('channel');
        $timeout = $this->option('timeout');
        $limit = $this->option('limit');
        $sleep = $this->option('sleep');

        $options = new PollingBatchOptionsDto(
            channelKey: is_scalar($channel) && $channel !== '' ? (string) $channel : null,
            timeout: is_scalar($timeout) && $timeout !== '' ? (int) $timeout : null,
            limit: is_scalar($limit) && $limit !== '' ? (int) $limit : null,
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

            sleep(max(1, is_scalar($sleep) ? (int) $sleep : 1));
        } while (true);
    }
}
