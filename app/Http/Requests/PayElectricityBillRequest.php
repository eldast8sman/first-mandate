<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayElectricityBillRequest extends FormRequest
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
            'biller' => 'required|string',
            'biller_code' => 'required|string',
            'item' => 'required|string',
            'item_code' => 'required|string',
            'identifier' => 'required|string',
            'amount' => 'required|numeric|min:100',
        ];
    }
}
