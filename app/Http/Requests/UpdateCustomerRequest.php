<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Tier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'string', 'regex:/^gid:\/\/shopify\/Customer\/\d+$/'],
            'ghin_number' => ['required', 'integer'],
            'handicap_index' => ['required', 'string', 'regex:/^(NH|\+?\d+\.\d+)$/'],
            'tier' => ['required', Rule::enum(Tier::class)],
        ];
    }
}
