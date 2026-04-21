<?php

declare(strict_types=1);

namespace JOOservices\LaravelChatGateway\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class WebhookRequest extends FormRequest
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
            'provider' => ['sometimes', 'string', Rule::in(['telegram', 'viber', 'whatsapp'])],
        ];
    }
}
