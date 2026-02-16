<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCalendarSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'calendar.start_sunday' => 'boolean',
            'calendar.mode' => 'in:date,week',
            'calendar.show_specials' => 'boolean',
            'calendar.show_downloaded' => 'boolean',
            'calendar.show_episode_numbers' => 'boolean',
        ];
    }
}
