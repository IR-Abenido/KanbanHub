<?php

namespace App\Http\Requests\Shared;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddUserToBlacklist extends FormRequest
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
            'user_id' => [
                'required',
                'exists:users,id',
                Rule::unique('blacklist_members', 'user_id')->where(function ($query) {
                    return $query->where('blacklistable_type', $this->blacklistable_type)
                        ->where('blacklistable_id', $this->blacklistable_id);
                }),
            ],
            'blacklistable_type' => 'required|string',
            'blacklistable_id' => 'required|uuid',
        ];
    }
}
