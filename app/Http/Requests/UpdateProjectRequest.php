<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->organization_id !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'project_id' => [
                'required',
                'string',
                'max:50',
                Rule::unique('projects', 'project_id')->ignore($this->route('project')),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['Created', 'Progressing', 'On Hold', 'Completed'])],
            'logo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
