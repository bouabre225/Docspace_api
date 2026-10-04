<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class loginRequest extends FormRequest
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
            'email' => 'required|string|email|max:255',
            'mot_de_passe' => 'required|string|min:6',
            'device_name' => 'nullable|string|max:100'
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Identifiants invalides.',
            'mot_de_passe.required' => 'Identifiants invalides.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('mot_de_passe')) {
            $this->merge([
                'password' => $this->mot_de_passe,
            ]);
        }
    }
}
