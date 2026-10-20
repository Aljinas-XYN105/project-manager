<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name'                  => ['sometimes', 'required', 'string', 'max:255'],
            'email'                 => ['sometimes', 'required', 'email', "unique:users,email,{$userId}"],
            'job_title'             => ['nullable', 'string', 'max:255'],
            'timezone'              => ['nullable', 'string', 'timezone'],
            'theme'                 => ['nullable', 'in:light,dark'],
            'avatar'                => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif', 'max:2048'],
            'current_password'      => ['nullable', 'current_password'],
            'password'              => ['nullable', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['nullable'],
            'section'               => ['nullable', 'string'],
        ];
    }
}
