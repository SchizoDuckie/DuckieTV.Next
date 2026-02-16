<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDisplaySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'display.top_sites' => 'boolean',
            'display.top_sites_mode' => 'in:onhover,onclick',
            'display.show_ratings' => 'boolean',
            'display.not_watched_eps_btn' => 'boolean',
            'display.transitions' => 'boolean',
            'display.bg_opacity' => 'numeric|min:0|max:1',
            'notifications.enabled' => 'boolean',
            'display.mixed_case' => 'boolean',
            'kc.always' => 'boolean',
        ];
    }
}
