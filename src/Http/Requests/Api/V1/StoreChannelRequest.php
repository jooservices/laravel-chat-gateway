<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreChannelRequest extends FormRequest
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
        return [
            'provider' => ['required', 'string'],
            'channel_key' => ['required', 'string'],
            'name' => ['required', 'string'],
            'credentials' => ['required', 'array'],
            'webhook_secret' => ['nullable', 'string'],
            'settings' => ['sometimes', 'array'],
            'meta' => ['sometimes', 'array'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive'])],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}