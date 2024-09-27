<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Tier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc,dns'],
            'password' => ['required', 'required', 'string', 'min:5', 'max:40'],
            'ghin_number' => ['required', 'integer'],
            'handicap_index' => ['required', 'regex:/^\+?\d+\.\d+$/'],
            'tier' => ['required', Rule::enum(Tier::class)],
        ];
    }
}
