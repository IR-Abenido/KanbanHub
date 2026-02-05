<?php

namespace App\Http\Requests\Board;

use Illuminate\Foundation\Http\FormRequest;

class BoardUsersRemoveUser extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'boardId' => $this->route('boardId'),
            'targetId' => $this->route('targetId')
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'boardId' => 'required|exists:boards,id',
            'targetId' => 'required|exists:users,id'
        ];
    }
}
