<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUserEditRequest extends FormRequest
{
    public function rules(): array
    {
        $userId = $this->route('user')->id ?? null;
        return [
            'name' => ['required'],
            'lastname' => ['required'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'id_role' => ['required', 'exists:roles,id'],
        ];
    }

    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function messages(): array {
        return config('constants.messages');
    }
}
