<?php

namespace App\Http\Requests\Calendar;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\PermissionRegistrar;

class StoreAssignmentRequest extends FormRequest
{
    /** Authorization is the AssignmentPolicy's job - it needs the resolved date. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $teamId = app(PermissionRegistrar::class)->getPermissionsTeamId();

        return [
            'date' => ['required', 'date'],

            // Only admins may sign somebody else up; the controller ignores this otherwise.
            // Scoped to this cinema's approved members: `users` is shared across cinemas, so a
            // bare exists: rule would let a manager write somebody from another cinema into
            // their own week.
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('team_user', 'user_id')->where('team_id', $teamId)->whereNotNull('approved_at'),
            ],

            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.required' => 'Chýba dátum.',
        ];
    }
}
