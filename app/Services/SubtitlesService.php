<?php

namespace App\Services;

use App\Models\Episode;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SubtitlesService
{
    protected string $baseUrl = 'https://api.opensubtitles.org/xml-rpc';
    protected ?string $token = null;

    protected array $languages = [
        'alb' => 'Albanian', 'ara' => 'Arabic', 'baq' => 'Basque', 'pob' => 'Brazilian',
        'bul' => 'Bulgarian', 'cat' => 'Catalan', 'chi' => 'Chinese (simplified)',
        'zht' => 'Chinese (traditional)', 'hrv' => 'Croatian', 'cze' => 'Czech',
        'dan' => 'Danish', 'dut' => 'Dutch', 'eng' => 'English', 'est' => 'Estonian',
        'fin' => 'Finnish', 'fre' => 'French', 'glg' => 'Galician', 'geo' => 'Georgian',
        'ger' => 'German', 'ell' => 'Greek', 'heb' => 'Hebrew', 'hin' => 'Hindi',
        'hun' => 'Hungarian', 'ice' => 'Icelandic', 'ind' => 'Indonesian', 'ita' => 'Italian',
        'jpn' => 'Japanese', 'khm' => 'Khmer', 'kor' => 'Korean', 'mac' => 'Macedonian',
        'may' => 'Malay', 'nor' => 'Norwegian', 'per' => 'Persian', 'pol' => 'Polish',
        'por' => 'Portuguese', 'rum' => 'Romanian', 'rus' => 'Russian', 'scc' => 'Serbian',
        'sin' => 'Sinhalese', 'slo' => 'Slovak', 'slv' => 'Slovenian', 'spa' => 'Spanish',
        'swe' => 'Swedish', 'tgl' => 'Tagalog', 'tha' => 'Thai', 'tur' => 'Turkish',
        'ukr' => 'Ukrainian', 'vie' => 'Vietnamese'
    ];

    /**
     * Search for subtitles using OpenSubtitles XML-RPC API.
     */
    public function searchByEpisode(Episode $episode, array $languages = ['eng']): array
    {
        $serie = $episode->serie;
        if (!$serie || !$serie->imdb_id) {
            return [];
        }

        $imdbId = (int) str_replace('tt', '', $serie->imdb_id);
        $options = [
            'imdbid' => $imdbId,
            'season' => (int) $episode->seasonnumber,
            'episode' => (int) $episode->episodenumber,
            'sublanguageid' => implode(',', $languages),
        ];

        return $this->search($options);
    }

    /**
     * Search for subtitles by filename.
     * This method is not directly supported by the XML-RPC API in the same way as REST.
     * It will fall back to a query search.
     */
    public function searchByFilename(string $filename, array $languages = ['eng']): array
    {
        return $this->searchByQuery($filename, $languages);
    }

    /**
     * Search for subtitles using a text query.
     */
    public function searchByQuery(string $query, array $languages = ['eng']): array
    {
        $options = [
            'query' => $query,
            'sublanguageid' => implode(',', $languages),
        ];

        return $this->search($options);
    }

    /**
     * Internal search method handling login and XML-RPC call.
     */
    protected function search(array $options): array
    {
        if (!$this->token) {
            $this->token = $this->login();
        }

        if (!$this->token) {
            return [];
        }

        $response = $this->xmlRpcCall('SearchSubtitles', [$this->token, [$options]]);
        return $this->transformAndFilter($response['data'] ?? [], $options);
    }

    /**
     * Login to OpenSubtitles to get a token.
     */
    protected function login(): ?string
    {
        $response = $this->xmlRpcCall('LogIn', ['', '', 'en', 'DuckieTV v1.00']);
        return $response['token'] ?? null;
    }

    /**
     * Execute an XML-RPC call.
     */
    protected function xmlRpcCall(string $method, array $params): array
    {
        try {
            $xmlRequest = $this->encodeXmlRpc($method, $params);
            
            $response = Http::withHeaders(['Content-Type' => 'text/xml'])
                ->withBody($xmlRequest, 'text/xml')
                ->post($this->baseUrl);

            if ($response->successful()) {
                return $this->decodeXmlRpc($response->body());
            }

            Log::error('OpenSubtitles XML-RPC error: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('SubtitlesService XML-RPC Exception: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Very simple XML-RPC encoder for the specific needs of OpenSubtitles.
     */
    protected function encodeXmlRpc(string $method, array $params): string
    {
        $xml = '<?xml version="1.0"?><methodCall><methodName>' . $method . '</methodName><params>';
        foreach ($params as $param) {
            $xml .= '<param>' . $this->encodeValue($param) . '</param>';
        }
        $xml .= '</params></methodCall>';
        return $xml;
    }

    protected function encodeValue($value): string
    {
        if (is_array($value)) {
            if (array_keys($value) === range(0, count($value) - 1)) {
                // Array
                $xml = '<value><array><data>';
                foreach ($value as $v) {
                    $xml .= $this->encodeValue($v);
                }
                $xml .= '</data></array></value>';
                return $xml;
            } else {
                // Struct
                $xml = '<value><struct>';
                foreach ($value as $k => $v) {
                    $xml .= '<member><name>' . $k . '</name>' . $this->encodeValue($v) . '</member>';
                }
                $xml .= '</struct></value>';
                return $xml;
            }
        } elseif (is_int($value)) {
            return '<value><int>' . $value . '</int></value>';
        } elseif (is_bool($value)) {
            return '<value><boolean>' . ($value ? '1' : '0') . '</boolean></value>';
        } else {
            return '<value><string>' . htmlspecialchars((string)$value) . '</string></value>';
        }
    }

    /**
     * Simple XML-RPC decoder.
     */
    protected function decodeXmlRpc(string $xml): array
    {
        $doc = new \DOMDocument();
        $doc->loadXML($xml);
        $xpath = new \DOMXPath($doc);
        $valueNode = $xpath->query('//params/param/value')->item(0);
        return $valueNode ? $this->parseValue($valueNode, $xpath) : [];
    }

    protected function parseValue(\DOMNode $node, \DOMXPath $xpath): mixed
    {
        $child = $node->firstChild;
        while ($child && $child->nodeType !== XML_ELEMENT_NODE) {
            $child = $child->nextSibling;
        }
        if (!$child) return $node->textContent;

        switch ($child->nodeName) {
            case 'struct':
                $struct = [];
                foreach ($xpath->query('member', $child) ?? [] as $member) {
                    $name = $xpath->query('name', $member)->item(0)->textContent;
                    $value = $this->parseValue($xpath->query('value', $member)->item(0), $xpath);
                    $struct[$name] = $value;
                }
                return $struct;
            case 'array':
                $array = [];
                foreach ($xpath->query('data/value', $child) ?? [] as $valueNode) {
                    $array[] = $this->parseValue($valueNode, $xpath);
                }
                return $array;
            case 'int':
            case 'i4':
                return (int) $child->textContent;
            case 'double':
                return (float) $child->textContent;
            case 'boolean':
                return $child->textContent === '1' || $child->textContent === 'true';
            case 'string':
            default:
                return $child->textContent;
        }
    }

    /**
     * Transform and filter results (Parity with original logic).
     */
    protected function transformAndFilter(array $results, array $query): array
    {
        
        $filtered = array_filter($results, function ($sub) use ($query) {
            // Replicating original parseSubtitles logic
            if (($sub['SubFormat'] ?? '') !== 'srt') {
                return false;
            }

            if (isset($query['imdbid']) && isset($query['season']) && isset($query['episode'])) {
                if ((int)($sub['SeriesIMDBParent'] ?? 0) !== (int)$query['imdbid'] ||
                    (int)($sub['SeriesSeason'] ?? -1) !== (int)$query['season'] ||
                    (int)($sub['SeriesEpisode'] ?? -1) !== (int)$query['episode']) {
                    return false;
                }
            }

            return true;
        });


        // Add language names and return values, mapping to REST-like structure for frontend compatibility
        return array_values(array_map(function ($sub) {
            $langCode = $sub['SubLanguageID'] ?? 'unknown';
            return [
                'attributes' => [
                    'language' => $langCode,
                    'language_name' => $this->languages[$langCode] ?? $langCode,
                    'release' => $sub['MovieReleaseName'] ?? ($sub['SubFileName'] ?? 'Unknown'),
                    'download_count' => $sub['SubDownloadsCnt'] ?? 0,
                    'hearing_impaired' => $sub['SubHearingImpaired'] ?? '0',
                    'url' => str_replace('.gz', '.srt', $sub['SubDownloadLink'] ?? '#'),
                    'feature_details' => [
                        'season_number' => $sub['SeriesSeason'] ?? null,
                        'episode_number' => $sub['SeriesEpisode'] ?? null,
                    ]
                ]
            ];
        }, $filtered));
    }
}
