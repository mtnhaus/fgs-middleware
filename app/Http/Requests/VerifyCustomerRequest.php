<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ghin_number' => ['required', 'integer'],
            'last_name' => ['required', 'string', 'max:255'],
        ];
    }
}
