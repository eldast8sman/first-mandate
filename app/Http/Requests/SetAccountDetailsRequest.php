<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetAccountDetailsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bank' => 'required|string',
            'bank_code' => 'required|string',
            'account_number' => 'required|string|size:10',
            'account_name' => 'required|string',
            'password' => 'required|string'
        ];
    }
}
