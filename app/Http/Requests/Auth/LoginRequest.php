<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read string $email
 * @property-read string $password
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ];
    }
}
