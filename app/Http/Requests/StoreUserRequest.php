<?php

namespace App\Http\Requests;

use App\Rules\BirthdateRule;
use App\Rules\PasswordRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email|max:255|unique:accounts,email',
            'password' => ['required','confirmed',new PasswordRule()],
            'name' => 'required|string|max:255',
            'birthdate' => ['required','date',new BirthdateRule()],
        ];
    }
}
