<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class TelegramWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}