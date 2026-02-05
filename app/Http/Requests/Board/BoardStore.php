<?php

namespace App\Http\Requests\Board;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BoardStore extends FormRequest
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
            'workspaceId' => 'required|exists:workspaces,id',
            'name' => [
                'required',
                'max:115',
                Rule::unique('boards')->where(function ($query) {
                    return $query->where('workspace_id', $this->input('workspaceId'));
                }),
            ],
            'private' => 'required|boolean'
        ];
    }
}
