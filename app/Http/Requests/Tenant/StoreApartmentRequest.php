<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class StoreApartmentRequest extends FormRequest
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
            'property_title' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'lease_start' => 'required|date',
            'lease_end' => 'required|date',
            'rent_payment_status' => 'required|string',
            'rent_due_date' => 'date|nullable',
            'rent_amount' => 'numeric|nullable',
            'payment_type' => 'string|nullable',
            'no_of_installments' => 'integer|nullable'
        ];
    }
}
