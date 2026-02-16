<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTraktSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trakttv.sync' => 'boolean',
            'trakttv.sync-downloaded' => 'boolean',
            'trakt-update.period' => 'integer|min:1|max:24',
            'trakttv.username' => 'nullable|string',
            'trakttv.passwordHash' => 'nullable|string', // Or token if we switch to OAuth
        ];
    }
}
