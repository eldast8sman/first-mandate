<?php

namespace App\Http\Requests\Landlord;

use Illuminate\Foundation\Http\FormRequest;

class SendNoticeRequest extends FormRequest
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
            'tenant_uuid' => 'required|string|exists:property_tenants,uuid',
            'type' => 'required|string',
            'description' => 'required|string',
            'notice_date' => 'date|nullable',
            'notice_time' => 'string|nullable'
        ];
    }
}
