<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use JOOservices\LaravelChatGateway\Contracts\Providers\ChatProviderContract;
use JOOservices\LaravelChatGateway\Contracts\Providers\ProviderHttpClientFactoryContract;
use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatAttachmentRepositoryContract;
use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatChannelRepositoryContract;
use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatContactRepositoryContract;
use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatConversationRepositoryContract;
use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatMessageRepositoryContract;
use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatMessageStatusLogRepositoryContract;
use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatPollingStateRepositoryContract;
use JOOservices\LaravelChatGateway\Contracts\Repositories\ChatWebhookEventRepositoryContract;
use JOOservices\LaravelChatGateway\Contracts\Services\AuditEventBridgeContract;
use JOOservices\LaravelChatGateway\Contracts\Services\ChannelServiceContract;
use JOOservices\LaravelChatGateway\Contracts\Services\ConversationServiceContract;
use JOOservices\LaravelChatGateway\Contracts\Services\InboundIngestionServiceContract;
use JOOservices\LaravelChatGateway\Contracts\Services\InboundModeResolverContract;
use JOOservices\LaravelChatGateway\Contracts\Services\MessageServiceContract;
use JOOservices\LaravelChatGateway\Contracts\Services\PollingServiceContract;
use JOOservices\LaravelChatGateway\Contracts\Services\ProviderChannelServiceContract;
use JOOservices\LaravelChatGateway\Contracts\Services\ProviderRegistryServiceContract;
use JOOservices\LaravelChatGateway\Contracts\Services\QueueDispatchServiceContract;
use JOOservices\LaravelChatGateway\Contracts\Services\WebhookServiceContract;
use JOOservices\LaravelChatGateway\Console\Commands\GatewayPollCommand;
use JOOservices\LaravelChatGateway\Providers\Telegram\TelegramProvider;
use JOOservices\LaravelChatGateway\Providers\Telegram\TelegramUpdateFetcher;
use JOOservices\LaravelChatGateway\Providers\Viber\ViberProvider;
use JOOservices\LaravelChatGateway\Providers\WhatsApp\WhatsAppProvider;
use JOOservices\LaravelChatGateway\Repositories\ChatAttachmentRepository;
use JOOservices\LaravelChatGateway\Repositories\ChatChannelRepository;
use JOOservices\LaravelChatGateway\Repositories\ChatContactRepository;
use JOOservices\LaravelChatGateway\Repositories\ChatConversationRepository;
use JOOservices\LaravelChatGateway\Repositories\ChatMessageRepository;
use JOOservices\LaravelChatGateway\Repositories\ChatMessageStatusLogRepository;
use JOOservices\LaravelChatGateway\Repositories\ChatPollingStateRepository;
use JOOservices\LaravelChatGateway\Repositories\ChatWebhookEventRepository;
use JOOservices\LaravelChatGateway\Services\AuditEventBridge;
use JOOservices\LaravelChatGateway\Services\ChannelService;
use JOOservices\LaravelChatGateway\Services\ChatGatewayManager;
use JOOservices\LaravelChatGateway\Services\ConversationService;
use JOOservices\LaravelChatGateway\Services\InboundIngestionService;
use JOOservices\LaravelChatGateway\Services\InboundModeResolver;
use JOOservices\LaravelChatGateway\Services\MessageService;
use JOOservices\LaravelChatGateway\Services\PollingService;
use JOOservices\LaravelChatGateway\Services\DeduplicationService;
use JOOservices\LaravelChatGateway\Services\ProviderChannelService;
use JOOservices\LaravelChatGateway\Services\ProviderRegistryService;
use JOOservices\LaravelChatGateway\Services\ProviderHttpClientFactory;
use JOOservices\LaravelChatGateway\Services\QueueDispatchService;
use JOOservices\LaravelChatGateway\Services\WebhookService;
use JOOservices\LaravelChatGateway\Subscribers\ConversationLifecycleSubscriber;
use JOOservices\LaravelChatGateway\Subscribers\MessageLifecycleSubscriber;
use JOOservices\LaravelChatGateway\Subscribers\WebhookLifecycleSubscriber;

final class LaravelChatGatewayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/chat-gateway.php', 'chat-gateway');

        $this->app->singleton(ProviderHttpClientFactoryContract::class, ProviderHttpClientFactory::class);
        $this->app->singleton(ChatChannelRepositoryContract::class, ChatChannelRepository::class);
        $this->app->singleton(ChatContactRepositoryContract::class, ChatContactRepository::class);
        $this->app->singleton(ChatConversationRepositoryContract::class, ChatConversationRepository::class);
        $this->app->singleton(ChatMessageRepositoryContract::class, ChatMessageRepository::class);
        $this->app->singleton(ChatAttachmentRepositoryContract::class, ChatAttachmentRepository::class);
        $this->app->singleton(ChatWebhookEventRepositoryContract::class, ChatWebhookEventRepository::class);
        $this->app->singleton(ChatMessageStatusLogRepositoryContract::class, ChatMessageStatusLogRepository::class);
        $this->app->singleton(ChatPollingStateRepositoryContract::class, ChatPollingStateRepository::class);

        $this->app->singleton(AuditEventBridgeContract::class, AuditEventBridge::class);
        $this->app->singleton(ChannelServiceContract::class, ChannelService::class);
        $this->app->singleton(ConversationServiceContract::class, ConversationService::class);
        $this->app->singleton(MessageServiceContract::class, MessageService::class);
        $this->app->singleton(QueueDispatchService::class, QueueDispatchService::class);
        $this->app->singleton(QueueDispatchServiceContract::class, QueueDispatchService::class);
        $this->app->singleton(InboundIngestionServiceContract::class, InboundIngestionService::class);
        $this->app->singleton(InboundModeResolverContract::class, InboundModeResolver::class);
        $this->app->singleton(PollingServiceContract::class, PollingService::class);
        $this->app->singleton(WebhookServiceContract::class, WebhookService::class);
        $this->app->singleton(ProviderChannelServiceContract::class, ProviderChannelService::class);
        $this->app->singleton(DeduplicationService::class, DeduplicationService::class);

        $this->app->bind(ProviderRegistryServiceContract::class, ProviderRegistryService::class);

        $this->app->bind(TelegramUpdateFetcher::class, TelegramUpdateFetcher::class);
        $this->app->bind(TelegramProvider::class, TelegramProvider::class);
        $this->app->bind(ViberProvider::class, ViberProvider::class);
        $this->app->bind(WhatsAppProvider::class, WhatsAppProvider::class);

        $this->app->bind(GatewayPollCommand::class, GatewayPollCommand::class);

        $this->app->bind(ChatGatewayManager::class, ChatGatewayManager::class);
        $this->app->alias(ChatGatewayManager::class, 'chat-gateway.manager');

        $this->app->bind(ChatProviderContract::class, static function ($app): ChatProviderContract {
            return $app->make(ChatGatewayManager::class)->defaultProvider();
        });
    }

    public function boot(Dispatcher $events): void
    {
        $this->app->resolving(ProviderRegistryServiceContract::class, function (ProviderRegistryServiceContract $registry): void {
            if (! $registry->has('telegram')) {
                $telegram = $this->app->make(TelegramProvider::class);
                $registry->register($telegram->name(), $telegram);
            }

            if (! $registry->has('viber')) {
                $viber = $this->app->make(ViberProvider::class);
                $registry->register($viber->name(), $viber);
            }

            if (! $registry->has('whatsapp')) {
                $whatsApp = $this->app->make(WhatsAppProvider::class);
                $registry->register($whatsApp->name(), $whatsApp);
            }
        });

        $this->publishes([
            __DIR__.'/../config/chat-gateway.php' => config_path('chat-gateway.php'),
        ], 'chat-gateway-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'chat-gateway-migrations');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                GatewayPollCommand::class,
            ]);
        }

        if ((bool) config('chat-gateway.routes.enabled', true)) {
            Route::middleware((array) config('chat-gateway.routes.middleware', ['api']))
                ->prefix((string) config('chat-gateway.routes.prefix', 'chat-gateway'))
                ->group(__DIR__.'/Routing/routes.php');

            Route::middleware((array) config('chat-gateway.routes.middleware', ['api']))
                ->group(__DIR__.'/Routing/api.php');
        }

        $events->subscribe(WebhookLifecycleSubscriber::class);
        $events->subscribe(MessageLifecycleSubscriber::class);
        $events->subscribe(ConversationLifecycleSubscriber::class);
    }
}
