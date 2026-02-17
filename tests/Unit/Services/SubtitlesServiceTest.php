<?php

namespace Tests\Unit\Services;

use App\Models\Episode;
use App\Models\Serie;
use App\Services\SubtitlesService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SubtitlesServiceTest extends TestCase
{
    protected SubtitlesService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SubtitlesService();
    }

    public function test_search_by_episode_formats_correct_xml_rpc()
    {
        $loginResponse = '<?xml version="1.0"?><methodResponse><params><param><value><struct><member><name>token</name><value><string>test-token</string></value></member></struct></value></param></params></methodResponse>';
        $searchResponse = '<?xml version="1.0"?><methodResponse><params><param><value><struct><member><name>data</name><value><array><data><value><struct><member><name>SubFormat</name><value><string>srt</string></value></member><member><name>SeriesIMDBParent</name><value><string>1234567</string></value></member><member><name>SeriesSeason</name><value><string>1</string></value></member><member><name>SeriesEpisode</name><value><string>1</string></value></member><member><name>MovieReleaseName</name><value><string>The Show S01E01</string></value></member><member><name>SubDownloadLink</name><value><string>http://example.com/sub.srt.gz</string></value></member></struct></value></data></array></value></member></struct></value></param></params></methodResponse>';

        Http::fake([
            'https://api.opensubtitles.org/xml-rpc' => Http::sequence()
                ->push($loginResponse, 200, ['Content-Type' => 'text/xml'])
                ->push($searchResponse, 200, ['Content-Type' => 'text/xml'])
        ]);

        $serie = new Serie(['name' => 'The Show', 'trakt_id' => 111, 'imdb_id' => 'tt1234567']);
        $episode = new Episode([
            'episodename' => 'The Ep',
            'trakt_id' => 222,
            'seasonnumber' => 1,
            'episodenumber' => 1,
        ]);
        $episode->setRelation('serie', $serie);

        $results = $this->service->searchByEpisode($episode, ['eng']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.opensubtitles.org/xml-rpc' &&
                   str_contains($request->body(), '<methodName>LogIn</methodName>');
        });

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.opensubtitles.org/xml-rpc' &&
                   str_contains($request->body(), '<methodName>SearchSubtitles</methodName>') &&
                   str_contains($request->body(), '<name>imdbid</name><value><int>1234567</int></value>');
        });

        $this->assertCount(1, $results);
        $this->assertEquals('The Show S01E01', $results[0]['attributes']['release']);
        $this->assertEquals('http://example.com/sub.srt.srt', $results[0]['attributes']['url']);
    }

    public function test_search_by_query_formats_correct_xml_rpc()
    {
        $loginResponse = '<?xml version="1.0"?><methodResponse><params><param><value><struct><member><name>token</name><value><string>test-token</string></value></member></struct></value></param></params></methodResponse>';
        $searchResponse = '<?xml version="1.0"?><methodResponse><params><param><value><struct><member><name>data</name><value><array><data><value><struct><member><name>SubFormat</name><value><string>srt</string></value></member><member><name>MovieReleaseName</name><value><string>The Show S01E01</string></value></member><member><name>SubDownloadLink</name><value><string>http://example.com/sub.srt.gz</string></value></member></struct></value></data></array></value></member></struct></value></param></params></methodResponse>';

        Http::fake([
            'https://api.opensubtitles.org/xml-rpc' => Http::sequence()
                ->push($loginResponse, 200, ['Content-Type' => 'text/xml'])
                ->push($searchResponse, 200, ['Content-Type' => 'text/xml'])
        ]);

        $results = $this->service->searchByQuery('The Show', ['eng']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.opensubtitles.org/xml-rpc' &&
                   str_contains($request->body(), '<methodName>SearchSubtitles</methodName>') &&
                   str_contains($request->body(), '<value><string>The Show</string></value>');
        });

        $this->assertCount(1, $results);
        $this->assertEquals('The Show S01E01', $results[0]['attributes']['release']);
    }
}
