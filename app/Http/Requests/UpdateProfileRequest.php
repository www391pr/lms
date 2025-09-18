<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            'first_name' => 'sometimes|string|max:255',
            'last_name'  => 'sometimes|string|max:255',
            'user_name'  => 'sometimes|string|max:255|unique:users,user_name,' . $this->user()->id,
//            'email'      => 'sometimes|email|unique:users,email,' . $this->user()->id,
            'password'   => 'sometimes|min:8|confirmed',
            'avatar'     => 'sometimes|image|max:2048',

            // student fields
            'full_name'  => 'sometimes|string|max:255',

            // instructor fields
            'bio'        => 'sometimes|string|max:500',
        ];
    }
}
