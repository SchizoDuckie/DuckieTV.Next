<?php

namespace Tests\Unit\Services;

use App\Models\Episode;
use App\Models\Serie;
use App\Services\AutoDownloadService;
use App\Services\FavoritesService;
use App\Services\SettingsService;
use App\Services\TorrentSearchService;
use App\Services\SceneNameResolverService;
use App\Services\TorrentClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class AutoDownloadServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AutoDownloadService $service;
    protected $settingsMock;
    protected $favoritesMock;
    protected $searchMock;
    protected $sceneNameMock;
    protected $torrentClientMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->settingsMock = Mockery::mock(SettingsService::class);
        $this->favoritesMock = Mockery::mock(FavoritesService::class);
        $this->searchMock = Mockery::mock(TorrentSearchService::class);
        $this->sceneNameMock = Mockery::mock(SceneNameResolverService::class);
        $this->torrentClientMock = Mockery::mock(TorrentClientService::class);

        $this->service = new AutoDownloadService(
            $this->settingsMock,
            $this->favoritesMock,
            $this->searchMock,
            $this->sceneNameMock,
            $this->torrentClientMock
        );
    }

    /**
     * Test the word-by-word scoring logic (filterByScore).
     * Parity: All words in query must exist in release name.
     */
    public function test_filter_by_score()
    {
        $query = "Big Bang Theory s01e01 1080p";
        
        $cases = [
            ['name' => "The.Big.Bang.Theory.S01E01.1080p.Bluray", 'expected' => true],
            ['name' => "Big.Bang.Theory.S01E01.720p", 'expected' => false], // missing 1080p
            ['name' => "The Big Bang Theory S01E01 1080p x265", 'expected' => true],
            ['name' => "Theory.S01E01.1080p", 'expected' => false], // missing Big, Bang
        ];

        foreach ($cases as $case) {
            $result = $this->invokePrivateMethod($this->service, 'filterByScore', [$case['name'], $query]);
            $this->assertEquals($case['expected'], $result, "Failed for: " . $case['name']);
        }
    }

    /**
     * Test size filtering logic.
     * Parity Check: Ensure NO normalization happens (1.5 GB is NOT > 500 MB if 1.5 is compared to 500)
     */
    public function test_filter_by_size_no_normalization_parity()
    {
        $serie = new Serie(['custom_search_size_min' => 100, 'custom_search_size_max' => 500]);
        
        $cases = [
            ['size' => '250 MB', 'expected' => true],
            ['size' => '50 MB',  'expected' => false],
            ['size' => '1.5 GB', 'expected' => false], // 100% Parity: 1.5 is NOT between 100 and 500.
            ['size' => '550 MB', 'expected' => false],
            ['size' => '100 MB', 'expected' => true],
            ['size' => '500 MB', 'expected' => true],
            ['size' => null,     'expected' => true],
        ];

        foreach ($cases as $case) {
            $result = $this->invokePrivateMethod($this->service, 'filterBySize', [$case['size'], $serie, 0, 1000]);
            $this->assertEquals($case['expected'], $result, "Failed for size: " . $case['size']);
        }
    }

    /**
     * Test Keyword filtering (Require/Ignore) with Exclusion override prevention.
     */
    public function test_filter_keywords_with_exclude_override_prevention()
    {
        $q = "The Show S01E01 x264";

        $cases = [
            // Require keywords (OR mode)
            ['name' => 'The.Show.S01E01.x264', 'require' => 'x264 x265', 'ignore' => '', 'expected' => true],
            ['name' => 'The.Show.S01E01.hvc1', 'require' => 'x264 x265', 'ignore' => '', 'expected' => false],
            
            // Ignore keywords
            ['name' => 'The.Show.S01E01.PROPER', 'require' => '', 'ignore' => 'PROPER', 'expected' => false],
            ['name' => 'The.Show.S01E01.HDTV',   'require' => '', 'ignore' => 'PROPER', 'expected' => true],

            // Parity Check: prevent exclude list from overriding primary search string
            // 'x264' is in q, so it should be filtered OUT of the ignore list.
            ['name' => 'The.Show.S01E01.x264', 'require' => '', 'ignore' => 'x264', 'expected' => true], 
        ];

        foreach ($cases as $case) {
            $result = $this->invokePrivateMethod($this->service, 'filterKeywords', [$case['name'], $case['require'], $case['ignore'], true, $q]);
            $this->assertEquals($case['expected'], $result, "Failed for: " . $case['name']);
        }
    }

    /**
     * Helper to invoke private methods for testing intricacies.
     */
    protected function invokePrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
