<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class ShowSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge(['section' => $this->route('section')]);
    }

    public function rules(): array
    {
        return [
            'section' => 'required|string|in:display,language,backup,calendar,torrent-search,jackett-search,subtitles,miscellaneous,torrent,auto-download,trakttv,utorrent,transmission,qbittorrent41plus,deluge,rtorrent,vuze,tixati,biglybt,ktorrent,aria2,utorrentwebui,ttorrent',
        ];
    }
}
