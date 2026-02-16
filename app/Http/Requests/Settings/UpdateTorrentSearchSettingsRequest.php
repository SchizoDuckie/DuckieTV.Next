<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTorrentSearchSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'torrenting.searchprovider' => 'string|in:ThePirateBay,KickassTorrents,Strike,RarBG,IsoHunt,1337x,LimeTorrents,Zooqle,TorrentDownload,EZTV,YTS',
            'torrenting.gui_search_providers' => 'array', // For toggling providers in search
            'torrenting.global_size_min' => 'nullable|numeric|min:0',
            'torrenting.global_size_max' => 'nullable|numeric|min:0',
            'torrenting.ignore_keywords' => 'nullable|string',
            'torrenting.require_keywords' => 'nullable|string',
            'torrenting.require_keywords_mode_or' => 'boolean',
            'torrenting.searchquality' => 'nullable|string',
            'torrenting.min_seeders' => 'integer|min:0',
        ];
    }
}
