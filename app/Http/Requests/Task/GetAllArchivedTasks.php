<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class GetAllArchivedTasks extends FormRequest
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
            'boardId' => 'required|exists:boards,id'
        ];
    }
}
