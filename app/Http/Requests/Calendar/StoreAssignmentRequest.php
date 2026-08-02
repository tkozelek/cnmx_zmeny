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

            // Optional: signup is one button per day and picks no position. Still scoped to the
            // current team when given, so a position id borrowed from another cinema is
            // rejected here rather than by the composite foreign key.
            'position_id' => [
                'nullable',
                Rule::exists('positions', 'id')->where('team_id', $teamId)->where('is_active', true),
            ],

            // Only admins may sign somebody else up; the controller ignores this otherwise.
            'user_id' => ['nullable', 'integer', 'exists:users,id'],

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
            'position_id.exists' => 'Táto pozícia neexistuje.',
        ];
    }
}
