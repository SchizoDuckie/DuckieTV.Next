<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates incoming torrent search requests.
 *
 * Ensures the search query is present and non-empty, and that optional
 * engine and sorting parameters are valid strings when provided.
 *
 * @see \App\Http\Controllers\TorrentController::search()
 * @see TorrentSearchEngines.js in DuckieTV-angular for original implementation.
 */
class TorrentSearchRequest extends FormRequest
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
            'query' => ['required', 'string', 'min:1', 'max:500'],
            'engine' => ['nullable', 'string', \Illuminate\Validation\Rule::in($engines)],
            'sortBy' => ['nullable', 'string', 'regex:/^[a-z]+\.[ad]$/'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'query.required' => 'A search query is required.',
            'query.min' => 'Search query must be at least 1 character.',
            'sortBy.regex' => 'Sort format must be "field.d" or "field.a" (e.g., "seeders.d").',
        ];
    }
}
