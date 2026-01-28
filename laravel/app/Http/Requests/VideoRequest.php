<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VideoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->id() === $this->user?->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'video' => [
                'required',
                'file',
                'mimetypes:video/mp4,video/mpeg,video/quicktime,video/x-msvideo',
                'max:1024000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'video.max' => 'Фотография слишком большая (макс. 1ГБ)',
            'video.required' => 'Выберите видеофайл для загрузки',
        ];
    }
}
