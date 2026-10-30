<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTimeBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'purchase_order_id' => ['required', 'exists:purchase_orders,id'],
            'date' => ['required', 'date'],
            'duration_hours' => ['required', 'numeric', 'min:0.25', 'max:10.0'],
            'description' => ['required', 'string', 'min:5', 'max:1000'],
            'activity' => ['nullable', 'string', 'max:255'],
            'accounting_object' => ['nullable', 'string', 'max:255'],
            'start_time' => ['nullable', 'date_format:H:i,H:i:s'],
            'end_time' => ['nullable', 'date_format:H:i,H:i:s'],
        ];
    }

    /**
     * Custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'duration_hours.max' => 'German Working Time Act (ArbZG § 3) prohibits single bookings exceeding 10 hours.',
            'duration_hours.min' => 'Booking duration must be at least 15 minutes (0.25 hours).',
            'description.min' => 'Please provide a descriptive explanation of what work was completed.',
        ];
    }
}
