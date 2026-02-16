<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubtitlesSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subtitles.languages' => 'array',
            'subtitles.languages.*' => 'string|size:3', // ISO 639-2 codes usually 3 chars? locale keys are like 'en_US'
        ];
    }
}
