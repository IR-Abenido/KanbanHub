<?php

namespace App\Http\Requests\Board;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BoardUpdate extends FormRequest
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
            'id' => 'required|exists:boards,id',
            'name' => ['required', 'string', 'max:115', Rule::unique('boards', 'name')->ignore($this->input('id'))],
            'private' => 'required|boolean'
        ];
    }
}
