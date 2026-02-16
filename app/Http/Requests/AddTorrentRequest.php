<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates requests for adding a torrent to the active client.
 *
 * Supports adding via magnet URI, remote .torrent URL, or (eventually) binary upload.
 */
class AddTorrentRequest extends FormRequest
{
    /**
     * Always true for this local-only app.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'magnet' => 'required_without:url|nullable|string|startsWith:magnet:',
            'url' => 'required_without:magnet|nullable|url|max:2048',
            'infoHash' => 'nullable|string',
            'releaseName' => 'required_with:url|nullable|string|max:500',
            'dlPath' => 'nullable|string|max:2048',
            'label' => 'nullable|string|max:255',
            'episode_id' => 'nullable|integer|exists:episodes,id',
        ];
    }
}
