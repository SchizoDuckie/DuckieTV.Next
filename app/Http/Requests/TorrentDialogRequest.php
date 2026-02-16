<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates requests for opening the torrent search dialog.
 *
 * Used by both the "Find Torrent" button on episodes (which provides an episode_id)
 * and the global "Torrent Client" button (which opens the dialog without a query).
 */
class TorrentDialogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
            'query' => ['nullable', 'string', 'max:500'],
            'episode_id' => ['nullable', 'integer'],
        ];
    }
}
