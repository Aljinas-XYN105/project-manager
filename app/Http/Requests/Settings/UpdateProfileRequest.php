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
            'name'                    => ['sometimes', 'required', 'string', 'max:255'],
            'email'                   => ['sometimes', 'required', 'email', "unique:users,email,{$userId}"],
            'job_title'               => ['nullable', 'string', 'max:255'],
            'phone'                   => ['nullable', 'string', 'max:30'],
            'date_of_birth'           => ['nullable', 'date', 'before:today'],
            'emergency_contact_name'  => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'timezone'                => ['nullable', 'string', 'timezone'],
            'theme'                   => ['nullable', 'in:light,dark'],
            'avatar'                  => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
            'current_password'        => ['nullable', 'current_password'],
            'password'                => ['nullable', 'string', 'min:8', 'confirmed'],
            'password_confirmation'   => ['nullable'],
            'section'                 => ['nullable', 'string'],
        ];
    }
}
