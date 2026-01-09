<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only authenticated users can send messages
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The request must contain either:
     * - conversation_id (for replying to existing conversation)
     * - user_id (for starting new conversation)
     * And a valid message.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:1', 'max:5000'],
            'conversation_id' => ['nullable', 'exists:conversations,id'],
            'user_id' => ['nullable', 'exists:users,id'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * Add custom validation logic to ensure either conversation_id or user_id is provided.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Ensure at least one of conversation_id or user_id is provided
            if (!$this->has('conversation_id') && !$this->has('user_id')) {
                $validator->errors()->add('conversation_id', 'Either conversation_id or user_id must be provided.');
            }
            
            // Ensure not both are provided (should be one or the other)
            if ($this->has('conversation_id') && $this->has('user_id')) {
                $validator->errors()->add('conversation_id', 'Provide either conversation_id or user_id, not both.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'message.required' => 'Please enter a message.',
            'message.min' => 'Message must be at least 1 character long.',
            'message.max' => 'Message cannot exceed 5000 characters.',
            'user_id.exists' => 'The selected user does not exist.',
            'conversation_id.exists' => 'The selected conversation does not exist.',
        ];
    }
}