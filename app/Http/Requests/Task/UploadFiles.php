<?php

namespace App\Http\Requests\Task;

use App\Models\Attachment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadFiles extends FormRequest
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
            'taskId' => 'required|exists:tasks,id',
            'file' => 'required|file|max:25600',
            function ($attribute, $value, $fail) {
                $filename = $value->getClientOriginalName();

                if (!$filename) {
                    return $fail('Invalid file name.');
                }

                $exists = Attachment::where('task_id', $this->input('taskId'))
                    ->where('attachment_attributes->name', $filename)
                    ->exists();

                if ($exists) {
                    $fail('A file with this name already exists for this task.');
                }
            },
        ];
    }
}
