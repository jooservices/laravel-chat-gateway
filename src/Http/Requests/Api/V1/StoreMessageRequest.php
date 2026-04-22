<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class StoreMessageRequest extends FormRequest
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
            'conversation_id' => ['required_without:external_chat_id', 'integer'],
            'external_chat_id' => ['required_without:conversation_id', 'string'],
            'type' => ['required', 'string'],
            'content' => ['nullable', 'string'],
            'reply_to_message_id' => ['sometimes', 'string'],
            'attachments' => ['sometimes', 'array'],
            'attachments.*' => ['array'],
            'meta' => ['sometimes', 'array'],
        ];
    }
}
