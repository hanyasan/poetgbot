<?php

namespace App\Http\Requests\Pricing;

use App\Enums\PoeVersionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property-read string $league_name
 * @property-read string $poe_version
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class PricingLatestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'league_name' => 'required|string',
            'poe_version' => [
                'required',
                'string',
                Rule::enum(PoeVersionEnum::class),
            ],
        ];
    }
}
