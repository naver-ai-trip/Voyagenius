<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateHotelBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authentication handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'offer_id' => ['required', 'string', 'max:100'],
            'guests' => ['required', 'array', 'min:1'],
            'guests.*.tid' => ['nullable', 'integer'],
            'guests.*.title' => ['nullable', 'string', 'max:54', 'regex:/^[A-Za-z -]*$/'],
            'guests.*.first_name' => ['required', 'string', 'max:56', 'min:1'],
            'guests.*.last_name' => ['required', 'string', 'max:57', 'min:1'],
            'guests.*.phone' => ['required', 'string', 'max:199', 'min:2'],
            'guests.*.email' => ['required', 'email', 'max:90', 'min:3'],
            'guests.*.child_age' => ['nullable', 'integer'],
            'payment' => ['required', 'array'],
            'payment.method' => ['required', 'string', 'in:CREDIT_CARD'],
            'payment.payment_card' => ['required', 'array'],
            'payment.payment_card.vendor_code' => ['required', 'string'],
            'payment.payment_card.card_number' => ['required', 'string'],
            'payment.payment_card.expiry_date' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'payment.payment_card.holder_name' => ['required', 'string'],
            'payment.payment_card.security_code' => ['nullable', 'string'],
            'travel_agent' => ['nullable', 'array'],
            'travel_agent.contact' => ['nullable', 'array'],
            'travel_agent.contact.email' => ['nullable', 'email', 'max:90', 'min:3'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'offer_id.required' => 'Offer ID is required',
            'guests.required' => 'At least one guest is required',
            'guests.*.first_name.required' => 'Guest first name is required',
            'guests.*.last_name.required' => 'Guest last name is required',
            'guests.*.phone.required' => 'Guest phone number is required',
            'guests.*.email.required' => 'Guest email is required',
            'guests.*.email.email' => 'Guest email must be a valid email address',
            'guests.*.title.regex' => 'Guest title must contain only letters, spaces, and hyphens',
            'payment.method.required' => 'Payment method is required',
            'payment.method.in' => 'Payment method must be CREDIT_CARD',
            'payment.payment_card.vendor_code.required' => 'Payment card vendor code is required',
            'payment.payment_card.card_number.required' => 'Payment card number is required',
            'payment.payment_card.expiry_date.required' => 'Payment card expiry date is required',
            'payment.payment_card.expiry_date.regex' => 'Expiry date must be in YYYY-MM format (e.g., 2026-08)',
            'payment.payment_card.holder_name.required' => 'Payment card holder name is required',
        ];
    }
}

