<?php

namespace App\Http\Requests\Auth\Profile;

use Illuminate\Foundation\Http\FormRequest;

class DeactivateOrDeleteAccount extends FormRequest
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
        $user = $this->user();

        return [
            'password' => [
                'required',
                'current_password',
                function ($attribute, $value, $fail) use ($user) {
                    $ownsWorkspace = $user->workspaces()
                        ->wherePivot('role', 'owner')
                        ->exists();

                    $ownsBoard = $user->boards()
                        ->wherePivot('role', 'owner')
                        ->exists();

                    if ($ownsWorkspace || $ownsBoard) {
                        $fail('You cannot deactivate your account while you own workspaces or boards. Please transfer ownership first.');
                    }
                }
            ],
        ];
    }
}
