<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->id() === $this->route('user')?->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => [
                'nullable',
                'string',
            ],
            'image' => [
                'file',
                'image',
                'max:4096',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'image.max' => 'Фотография слишком большая (макс. 4МБ)',
            'image.image' => 'Файл должен быть изображением',
        ];
    }
}
