<?php

namespace App\Modules\Customers\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required', 'string', 'max:32',
                Rule::unique('customers', 'phone')->ignore($this->route('customer')),
            ],
            'email' => ['nullable', 'string', 'email', 'max:255'],
        ];
    }
}
