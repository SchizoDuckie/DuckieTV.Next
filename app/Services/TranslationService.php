<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class TranslationService
{
    /**
     * Get a list of available locales from the lang directory.
     *
     * @return array<string, string> Array of locale code => locale name/code
     */
    public function getAvailableLocales(): array
    {
        $langPath = base_path('lang');
        
        if (!File::exists($langPath)) {
            return ['en_US' => 'English (US)'];
        }

        $files = File::files($langPath);
        $locales = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'json') {
                $code = $file->getFilenameWithoutExtension();
                // Use the code as the name, or try to read a "LOCALE" key if it exists in the JSON.
                
                $content = json_decode(File::get($file->getPathname()), true);
                $name = $content['LOCALE'] ?? $code;
                
                $locales[$code] = $name;
            }
        }

        // Sort by name
        asort($locales);

        return $locales;
    }
}
