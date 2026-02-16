<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates requests for fetching torrent detail pages.
 *
 * Some search engines don't include magnet/torrent links directly in the
 * search results. This request validates the parameters needed to fetch
 * a details page and extract the download links.
 *
 * @see \App\Http\Controllers\TorrentController::details()
 * @see GenericTorrentSearchEngine.js getDetails() in DuckieTV-angular.
 */
class TorrentDetailsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Always true — this is a local-only NativePHP app with no auth.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(\App\Services\TorrentSearchService $service): array
    {
        $engines = array_keys($service->getSearchEngines());

        return [
            'engine' => ['required', 'string', \Illuminate\Validation\Rule::in($engines)],
            'url' => ['required', 'string', 'max:2048'],
            'releasename' => ['required', 'string', 'max:500'],
        ];
    }
}
