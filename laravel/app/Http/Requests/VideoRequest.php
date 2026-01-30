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
            'video' => [
                'required',
                'file',
                'mimes:mp4,mov,avi,mpeg',
                'max:1024000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'video.max' => 'Видео слишком большое (макс. 1ГБ)',
            'video.required' => 'Выберите видеофайл для загрузки',
        ];
    }
}
