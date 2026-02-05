<?php

namespace App\Http\Requests\Workspace;

use App\Models\WorkspaceUser;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMember extends FormRequest
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
            'targetId' => 'required',
            'workspaceId' => 'required',
            'role' => ['required', function ($attribute, $value, $fail) {
                $existingRole = WorkspaceUser::where('user_id', $this->targetId)
                    ->where('workspace_id', $this->workspaceId)
                    ->value('role');

                if ($value === $existingRole) {
                    $fail("The {$attribute} must be different from the current role.");
                }
            }],
            'previousOwnerId' => 'required',
            'targetName' => 'required|string',
            'currentUserId' => 'required'
        ];
    }
}
