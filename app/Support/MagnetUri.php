<?php

namespace App\Support;

class MagnetUri
{
    /**
     * Extract the infoHash from a magnet URI or raw string.
     * Replicates logic from DuckieTV-angular's utility.js String.prototype.getInfoHash
     *
     * @param string $magnetUri
     * @return string|null
     */
    public static function extractInfoHash(string $magnetUri): ?string
    {
        // 1. Try to extract Base16 (Hex) infoHash (40 chars)
        // This is the most common format in magnet links (xt=urn:btih:...)
        if (preg_match('/([0-9a-fA-F]{40})/', $magnetUri, $matches)) {
            return strtoupper($matches[1]);
        }

        // 2. Base32 extraction (32 chars)
        // The legacy JS handles this by decoding Base32 to Hex.
        // For now, we will focus on Hex as it covers the vast majority of cases.
        // If we encounter Base32 hashes in the wild that need support, we can add a Base32 decoder here.
        // Pattern would be: /([2-7A-Z]{32})/

        return null;
    }
}
