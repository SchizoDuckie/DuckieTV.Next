# DuckieTV.Next Migration Plan
With this project we are going to port DuckieTV, the Angular.js TV Show Tracker to Laravel with NativePHP to bring it into 2026
The codebase was throroughly analyzed. the plan of the project is to create a faithful port that works exactly the same as the current DuckieTV

This is not a Rewrite of  DuckieTV.
We are:
 - Preserving the data model
 - Preserving the feature surface
 - Preserving the mental model
 - Replacing the runtime + distribution layer
 - Adding a modern auth/API shell

 You have access to the full duckietv sourcecode in the folder DuckieTV-angular/

## Codebase Summary

| Category | Count | Total Lines |
|---|---|---|
| Database Entities | 1 file (6 entities) | 710 |
| Services | 38 files | ~8,500 |
| Controllers | 33 files | ~3,500 |
| Directives | 27 files | ~3,200 |
| Templates | 70 HTML files | ~2,000 |
| Torrent Search Engines | 18 providers | ~1,800 |
| Torrent Clients | 14 clients | ~4,200 |
| **Total application JS** | **~130 files** | **~24,000** |

---

## PHASE 1: Database Entities & Settings (Foundation)

### 1.1 CRUD Entities → Eloquent Models

The app defines 6 CRUD entities in `js/CRUD.entities.js`. These are the core of everything.

#### Serie (table: `Series`) — 52 fields

```
ID_Serie (PK), name, banner, overview, TVDB_ID, IMDB_ID, TVRage_ID, actors,
airs_dayofweek, airs_time, timezone, contentrating, firstaired, genre, country,
language, network, rating, ratingcount, runtime, status, added, addedby, fanart,
poster, lastupdated, lastfetched, nextupdate, displaycalendar, autoDownload,
customSearchString, watched, notWatchedCount, ignoreGlobalQuality,
ignoreGlobalIncludes, ignoreGlobalExcludes, searchProvider, ignoreHideSpecials,
customSearchSizeMin, customSearchSizeMax, TRAKT_ID (UNIQUE), dlPath, customDelay,
alias, customFormat, TMDB_ID, customIncludes, customExcludes, customSeeders
```

Relations: `hasMany(Episode)`, `hasMany(Season)`
Indexes: `fanart`, `TRAKT_ID`
Has 21 schema migrations (versions 5-25)

**Prototype methods to port:**
- `getEpisodeCount()` / `getNotWatchedCount()` — computed properties
- `getSortName()` — strips "The", "A" prefix for sorting

#### Season (table: `Seasons`) — 8 fields

```
ID_Season (PK), ID_Serie (FK), seasonnumber, overview, poster, TRAKT_ID, TMDB_ID, watched
```

Relations: `belongsTo(Serie)`, `hasMany(Episode)` via `ID_Serie + seasonnumber`
1 migration (version 2, adds TMDB_ID)

**Prototype methods to port:**
- `markSeasonAsWatched()` / `markSeasonAsNotWatched()` — bulk episode updates
- `getEpisodeCount()` / `getNotWatchedCount()`

#### Episode (table: `Episodes`) — 23 fields

```
ID_Episode (PK), ID_Serie (FK), ID_Season, TVDB_ID, TRAKT_ID, TMDB_ID,
IMDB_ID, episodename, episodenumber, seasonnumber, firstaired, firstaired_iso,
overview, filename, rating, ratingcount, watched, watchedAt, downloaded,
magnetHash, absolute, leaked, IMDB_ID
```

Relations: `belongsTo(Serie)`, `belongsTo(Season)`
Indexes: `ID_Serie`, `TVDB_ID`
Has 6 schema migrations (versions 4-9)

**Prototype methods to port (critical — these contain business logic):**
- `getFormattedEpisode()` → formats "s01e05"
- `hasAired()` → `firstaired <= now()`
- `isWatched()` / `markWatched()` / `markNotWatched()`
- `isDownloaded()` / `markDownloaded()` / `markNotDownloaded()`
- These methods fire `$rootScope.$broadcast` events → needs Laravel Event equivalents

#### Fanart (table: `Fanart`) — 4 fields
```
ID_Fanart (PK), TVDB_ID, poster, json (autoSerialized)
```

#### TMDBFanart (table: `TMDBFanart`) — 7 fields
```
id (PK), entity_type, TMDB_ID, poster, fanart, screenshot, added
```

#### Jackett (table: `Jackett`) — 7 fields
```
ID_Jackett (PK), name, enabled, torznab, torznabEnabled, apiKey, json
```
Prototype methods: `isEnabled()`, `setEnabled()`, `setDisabled()`

### 1.2 Laravel Migration Strategy

```php
// One clean migration per entity, representing the FINAL schema state
// No need to replay the 21 historical migrations

// database/migrations/001_create_series_table.php
Schema::create('series', function (Blueprint $table) {
    $table->id();
    $table->string('name', 250)->nullable();
    $table->string('banner', 1024)->nullable();
    $table->text('overview')->nullable();
    $table->integer('tvdb_id')->nullable();
    $table->string('imdb_id', 20)->nullable();
    $table->integer('tvrage_id')->nullable();
    $table->text('actors')->nullable();         // pipe-separated
    $table->string('airs_dayofweek', 10)->nullable();
    $table->string('airs_time', 15)->nullable();
    $table->string('timezone', 30)->nullable();
    $table->string('contentrating', 20)->nullable();
    $table->date('firstaired')->nullable();
    $table->string('genre', 50)->nullable();    // pipe-separated
    $table->string('country', 50)->nullable();
    $table->string('language', 50)->nullable();
    $table->string('network', 50)->nullable();
    $table->integer('rating')->nullable();
    $table->integer('ratingcount')->nullable();
    $table->integer('runtime')->nullable();
    $table->string('status', 50)->nullable();
    $table->date('added')->nullable();
    $table->string('addedby', 50)->nullable();
    $table->string('fanart', 150)->nullable();
    $table->string('poster', 150)->nullable();
    $table->timestamp('lastupdated')->nullable();
    $table->timestamp('lastfetched')->nullable();
    $table->timestamp('nextupdate')->nullable();
    $table->boolean('displaycalendar')->default(true);
    $table->boolean('auto_download')->default(true);
    $table->string('custom_search_string', 150)->nullable();
    $table->boolean('watched')->default(false);
    $table->integer('not_watched_count')->default(0);
    $table->boolean('ignore_global_quality')->default(false);
    $table->boolean('ignore_global_includes')->default(false);
    $table->boolean('ignore_global_excludes')->default(false);
    $table->string('search_provider', 20)->nullable();
    $table->boolean('ignore_hide_specials')->default(false);
    $table->integer('custom_search_size_min')->nullable();
    $table->integer('custom_search_size_max')->nullable();
    $table->integer('trakt_id')->unique()->nullable();
    $table->text('dl_path')->nullable();
    $table->integer('custom_delay')->nullable();
    $table->string('alias', 250)->nullable();
    $table->string('custom_format', 20)->nullable();
    $table->integer('tmdb_id')->nullable();
    $table->string('custom_includes', 150)->nullable();
    $table->string('custom_excludes', 150)->nullable();
    $table->integer('custom_seeders')->nullable();
    $table->timestamps();

    $table->index('trakt_id');
    $table->index('fanart');
});
```

### 1.3 Settings System (SettingsService.js — 375 lines)

Current: `localStorage.setItem('userPreferences', JSON.stringify(settings))`
With 100+ settings keys covering: torrent clients, search engines, mirrors, calendar, display, language, autodownload, etc.

**Laravel equivalent:**

```php
// App\Models\Setting.php — key-value store in SQLite
Schema::create('settings', function (Blueprint $table) {
    $table->string('key')->primary();
    $table->text('value')->nullable();
});

// App\Services\SettingsService.php
class SettingsService {
    private array $cache = [];
    private array $defaults;  // Port the entire defaults block from JS

    public function get(string $key, $default = null): mixed;
    public function set(string $key, $value): void;
    public function restore(): void;       // Load from DB into cache
    public function persist(): void;       // Write cache to DB
}
```

**Complete defaults block** (from lines 84-263 of SettingsService.js) — all ~100 keys need to be ported to the Laravel SettingsService `$defaults` array. Key categories:

| Category | Example Keys |
|---|---|
| Torrent search mirrors (16) | `mirror.ThePirateBay`, `mirror.1337x`, `mirror.Knaben`, etc. |
| Torrent client configs (11 clients × ~5 keys each) | `qbittorrent32plus.server`, `.port`, `.username`, `.password`, `.use_auth` |
| Calendar settings | `calendar.mode`, `calendar.startSunday`, `calendar.show-specials`, `calendar.show-downloaded`, `calendar.show-episode-numbers` |
| Auto-download settings | `autodownload.period`, `autodownload.delay`, `autodownload.multiSE`, `autodownload.multiSE.enabled` |
| Torrenting global | `torrenting.enabled`, `torrenting.client`, `torrenting.searchprovider`, `torrenting.searchquality`, `torrenting.min_seeders`, `torrenting.global_size_min/max` |
| Display | `series.displaymode`, `library.seriesgrid`, `library.smallposters`, `background-rotator.opacity`, `font.bebas.enabled` |
| TraktTV | `trakttv.sync`, `trakttv.username`, `trakttv.passwordHash` |
| Language | `application.language`, `application.locale` |

---

## PHASE 2: Core Services → Laravel Services

### Priority Order

#### 2.1 FavoritesService (455 lines) → `App\Services\FavoritesService`

The central manager for all user shows. Handles:
- Adding/removing shows from favorites
- Mapping Trakt data onto Serie entities (`fillSerie()`)
- Adding seasons and episodes
- Coordinating with FanartService and SceneNameResolver
- Download ratings toggle

**Key methods to port:**
- `addFavorite(data)` — creates Serie + Seasons + Episodes from Trakt data
- `removeFavorite(serie)` — cascading delete
- `getById(id)` / `getByTraktId(id)` — lookups
- `waitForInitialization()` — loads all favorites from DB on startup

**Dependency chain:** FanartService, SceneNameResolver, $rootScope events

#### 2.2 TraktTVv2 (655 lines) → `App\Services\TraktService`

Pure API wrapper. Makes HTTP calls to `api.trakt.tv`. Contains:
- OAuth token management (client ID/secret, PIN auth flow)
- Search, trending, seasons, episodes, people endpoints
- Sync: watched shows, collection, episode seen/unseen
- Parsers that transform Trakt JSON into internal format

**Key constants:**
```
TRAKT_CLIENT_ID = 'e65088ee83478f54ffd9d5775dc63d0c64312eabd72b6b2e5623194675959bac'
TRAKT_CLIENT_SECRET = '3e97816f32ac913e51a96d2b0296b8f2172e7dee4b01e62df381ad7f62560c96'
```

**Laravel port:** Nearly 1:1 using `Http::withHeaders([...])`. The parser functions become private methods. OAuth token stored in settings table.

#### 2.3 CalendarEvents (286 lines) → `App\Services\CalendarService`

Manages the calendar data structure — a hash of `{date: [episodes]}`. Currently entirely in-memory on the client. Key operations:
- `setVisibleDays(start, end)` — queries Episodes with `firstaired BETWEEN`
- `addEvent(date, event)` — add episode to date slot
- `getEvents(date)` — returns episodes for a calendar cell
- Sorting: by air time, then episode number, then show title

**In Laravel:** This becomes a controller + service that queries episodes by date range and returns structured data for the frontend. The grouping/sorting logic stays the same.

#### 2.4 AutoDownloadService (418 lines) → `App\Jobs\AutoDownloadJob`

Scheduled background task that:
1. Finds episodes that have aired but aren't downloaded
2. For each, constructs a search query (show name + SxxExx + quality)
3. Searches configured torrent engines
4. Filters results by seeders, size, include/exclude keywords
5. Sends best result to configured torrent client

**In Laravel:** Perfect fit for `php artisan schedule:run` + Queued Job. The entire 418-line service becomes a Job class that runs on a cron schedule.

#### 2.5 TraktTVUpdateService (125 lines) → `App\Jobs\TraktUpdateJob`

Periodic job that checks Trakt for updated show info. Very simple — iterates favorites, checks `lastupdated` timestamps, fetches new data.

**In Laravel:** Another scheduled Job. Short and clean port.

---

## PHASE 3: Torrent Layer

### 3.1 TorrentSearchEngines Registry (369 lines) → `App\Services\TorrentSearchService`

Registry pattern — stores named search engine instances, provides `search(query)` dispatching. Also handles Jackett integration.

**Key methods:**
- `registerSearchEngine(name, engine)` — register provider
- `getSearchEngine(name)` — get by name
- `getDefaultEngine()` — returns the user's configured default
- `search(query, engine)` — execute search, return results

### 3.2 GenericTorrentSearchEngine (468 lines) → `App\Services\TorrentSearchEngines\GenericEngine`

**This is the big win for your migration.** The current implementation:
1. `$http.get(url)` — fetch search page HTML
2. Parse response as DOM (`DOMParser`)
3. Use CSS selectors to extract: release name, magnet URL, torrent URL, size, seeders, leechers, detail URL
4. If details page needed, fetch that too
5. Return structured JSON results

**Laravel equivalent:**
```php
class GenericEngine {
    public function search(string $query, string $sort = null): array
    {
        $url = $this->buildUrl($query, $sort);
        $html = Http::get($url)->body();
        $crawler = new Crawler($html);  // symfony/dom-crawler

        return $crawler->filter($this->config['selectors']['resultContainer'])
            ->each(function (Crawler $node) {
                return [
                    'releasename' => $this->extractProperty($node, 'releasename'),
                    'magnetUrl'   => $this->extractProperty($node, 'magnetUrl'),
                    'size'        => $this->extractProperty($node, 'size'),
                    'seeders'     => $this->extractProperty($node, 'seeders'),
                    'leechers'    => $this->extractProperty($node, 'leechers'),
                    'detailUrl'   => $this->extractProperty($node, 'detailUrl'),
                ];
            });
    }
}
```

The config-driven approach with CSS selectors maps beautifully. Each search engine is just a config array — same as now.

### 3.3 Individual Search Engines (18 providers)

Each is just a config registration, typically 30-60 lines. These become config files or database entries:

| Engine | Mirror | Config Size |
|---|---|---|
| ThePirateBay | thepiratebay0.org | 35 lines |
| 1337x | 1337x.to | 55 lines |
| Knaben | knaben.org | 40 lines |
| LimeTorrents | limetorrents.fun | 45 lines |
| Nyaa | nyaa.si | 40 lines |
| theRARBG | therarbg.to | 45 lines |
| ShowRSS | showrss.info | 35 lines |
| ETag, FileMood, Idope, IsoHunt2, KATws, PiratesParadise, TorrentDownloads, Uindex | various | ~30-50 lines each |

### 3.4 Torrent Clients (14 implementations)

All extend `BaseTorrentClient` (378 lines) which provides:
- Connection management, authentication
- `connect()`, `addMagnet()`, `addTorrentByUrl()`, `addTorrentByUpload()`
- `getTorrents()`, `getFiles()`, `execute()`

Individual clients override with API-specific implementations:

| Client | File | Lines | API Type |
|---|---|---|---|
| µTorrent | uTorrent.js | 801 | Custom HTTP API |
| µTorrent WebUI | uTorrentWebUI.js | 303 | Custom HTTP API |
| qBittorrent 4.1+ | qBittorrent41plus.js | 308 | REST API |
| Transmission | Transmission.js | 245 | JSON-RPC |
| Deluge | Deluge.js | 226 | JSON-RPC |
| rTorrent | rTorrent.js | 301 | XML-RPC |
| Aria2 | Aria2.js | 241 | JSON-RPC |
| Tixati | Tixati.js | 328 | HTML scraping |
| BiglyBT | BiglyBT.js | 100 | Transmission-compatible |
| Ktorrent | Ktorrent.js | 284 | Custom API |
| tTorrent | tTorrent.js | 354 | Custom API |
| Vuze | Vuze.js | 120 | Transmission-compatible |
| None (no client) | None.js | 165 | Magnet link only |

**Laravel port:** `App\Services\TorrentClients\` namespace. Each becomes a class implementing a `TorrentClientInterface`. The `BaseTorrentClient` becomes an abstract class. All HTTP calls use Laravel's Http facade. The XML-RPC clients (rTorrent) can use a PHP XML-RPC library.

---

## PHASE 4: Calendar & Frontend

### 4.1 Routes → Laravel Routes

The Angular `$stateProvider` defines these states (from `app.routes.js`, 476 lines):

| Angular State | URL | Laravel Route | View |
|---|---|---|---|
| `calendar` | `/` | `GET /` | Main calendar view |
| `favorites` | `/favorites` | `GET /favorites` | Series grid/list |
| `favorites.search` | `/favorites/search` | `GET /favorites/search` | Local filter |
| `add_favorites` | `/add` | `GET /add` | Trakt trending |
| `add_favorites.search` | `/add/search/:query` | `GET /add/search/{query}` | Trakt search results |
| `episode` | `/episode/:id` | `GET /episode/{id}` | Episode details |
| `serie` | `/series/:id` | `GET /series/{id}` | Serie overview |
| `serie.details` | `/series/:id/details` | `GET /series/{id}/details` | Serie full details |
| `serie.seasons` | `/series/:id/seasons` | `GET /series/{id}/seasons` | Season list |
| `serie.season` | `/series/:id/season/:sid` | `GET /series/{id}/season/{sid}` | Episode list |
| `serie.season.episode` | `.../episode/:eid` | `GET /series/{id}/season/{sid}/episode/{eid}` | Episode detail |
| `settings` | `/settings` | `GET /settings` | Settings hub |
| `settings.tab` | `/settings/:tab` | `GET /settings/{tab}` | Settings sub-page |
| `torrent` | `/torrent` | `GET /torrent` | Torrent client UI |
| `about` | `/about` | `GET /about` | About page |
| `autodlstatus` | `/autodlstatus` | `GET /autodlstatus` | Auto-download status |

### 4.2 Templates → Blade Views (70 templates)

The templates already use Bootstrap classes. Angular syntax conversion:

| Angular | Blade / jQuery |
|---|---|
| `ng-repeat="item in items"` | `@foreach($items as $item)` |
| `ng-if="condition"` | `@if($condition)` |
| `ng-show/ng-hide` | CSS class toggle or `@if` |
| `ng-click="fn()"` | `onclick` / jQuery handler / form submit |
| `ng-model="x"` | `<input name="x" value="{{ $x }}">` |
| `{{expression}}` | `{{ $expression }}` |
| `translate="KEY"` | `{{ __('KEY') }}` |
| `ng-class="{...}"` | `class="{{ $condition ? 'x' : 'y' }}"` |

### 4.3 Directives → Blade Components or JS

| Directive | Lines | Suggested Approach |
|---|---|---|
| `calendar` | 75 | Blade component + JS calendar library |
| `datePicker` | 373 | **Biggest frontend challenge.** Blade partial + JS |
| `calendarEvent` | ~40 | Blade partial `@include('partials.event')` |
| `seriesList` / `seriesGrid` | ~80 | Blade partials |
| `torrentDialog` | 567 | Blade + AJAX (jQuery modal + fetch) |
| `torrentDialog2` | 477 | Same approach |
| `torrentRemoteControl` | ~60 | Blade + AJAX polling |
| `fastSearch` | 389 | jQuery + AJAX autocomplete |
| `backgroundRotator` | ~60 | Simple JS/CSS |
| `actionBar` | ~80 | Blade layout component |
| `sidePanel` | ~50 | Blade + CSS transitions |
| `episodeWatched/Downloaded` | ~40 each | Blade + AJAX POST |
| `subtitleDialog` | 137 | Blade modal + AJAX |

### 4.4 Frontend Architecture: ES6 Modules + Vite

Most routes are simple server-rendered Blade views. But the interactive components (calendar, torrent UI, search) need proper client-side JS. Use **Vite** (ships with Laravel) to bundle ES6 modules.

#### Directory Structure

```
resources/
├── js/
│   ├── app.js                          # Entry point: boots Echo, registers components
│   ├── bootstrap.js                    # Laravel Echo + Reverb setup
│   │
│   ├── constants/
│   │   └── DuckieEvents.js             # Event constants (mirrors PHP DuckieEvents)
│   │
│   ├── services/                       # Shared client-side services (singletons)
│   │   ├── SettingsService.js          # Read-through cache, fetches from /api/settings
│   │   ├── EchoService.js             # Wraps Laravel Echo, exposes .on(EVENT, cb)
│   │   ├── TorrentRemoteService.js    # WebSocket-driven torrent progress tracking
│   │   └── NotificationService.js     # Desktop notification wrapper
│   │
│   ├── components/                     # Interactive UI components (ES6 classes)
│   │   ├── CalendarEvents.js          # Thin layer: Echo listeners for progress + watched toggles
│   │   ├── TorrentDialog.js           # Modal: multi-engine search, result filtering, launch
│   │   ├── TorrentRemoteControl.js    # Progress bars, file list, pause/resume/remove
│   │   ├── FastSearch.js              # Autocomplete overlay: keyboard nav, AJAX search
│   │   ├── SidePanel.js              # Panel show/hide/expand, CSS transitions
│   │   ├── BackgroundRotator.js       # Fanart cycling with crossfade
│   │   └── SeriesGrid.js             # Grid/list toggle, sorting, context menu
│   │
│   └── lib/                           # Pure utility, no DOM
│       ├── api.js                     # fetch() wrapper: GET/POST/DELETE to Laravel routes
│       ├── formatEpisode.js           # "s01e05" formatting (ported from Episode entity)
│       └── dateUtils.js              # Week boundaries, date arithmetic
│
├── css/
│   └── app.css                        # Bootstrap 5 + DuckieTV custom styles
│
└── views/                             # Blade templates (server-rendered)
    ├── layouts/
    │   └── app.blade.php              # Shell: nav, sidepanel container, calendar container
    ├── calendar/
    │   ├── index.blade.php            # Calendar page (mounts Calendar.js)
    │   └── _event.blade.php           # Single event partial (initial render)
    ├── series/
    │   ├── index.blade.php            # Favorites grid/list
    │   ├── show.blade.php             # Serie overview (sidepanel)
    │   ├── details.blade.php          # Serie full details
    │   ├── seasons.blade.php          # Season list
    │   └── episodes.blade.php         # Episode list for a season
    ├── episodes/
    │   └── show.blade.php             # Episode detail (sidepanel)
    ├── torrents/
    │   ├── index.blade.php            # Torrent client dashboard
    │   └── details.blade.php          # Single torrent detail
    ├── settings/
    │   ├── index.blade.php            # Settings hub
    │   ├── calendar.blade.php
    │   ├── torrent.blade.php
    │   ├── trakt.blade.php
    │   ├── display.blade.php
    │   ├── language.blade.php
    │   ├── backup.blade.php
    │   └── ... (one per tab)
    ├── add/
    │   ├── index.blade.php            # Trakt trending
    │   └── search.blade.php           # Trakt search results
    ├── partials/
    │   ├── _action_bar.blade.php
    │   ├── _side_panel.blade.php
    │   └── _serie_header.blade.php
    └── about.blade.php
```

#### Core Pattern: ES6 Class Components

Each interactive component is a self-contained ES6 class. Server renders the initial HTML via Blade, then the JS class hydrates it and owns the interactivity. No framework — just classes, events, and `fetch()`.

```js
// resources/js/components/CalendarEvents.js
// Thin layer — the calendar is server-rendered Blade. This just handles:
// 1. Echo events for real-time progress bars and watched state
// 2. Mark-watched clicks (AJAX POST + optimistic UI)
// 3. Hover-to-change-background (setTimeout)
import { DuckieEvents } from '../constants/DuckieEvents.js';
import { EchoService } from '../services/EchoService.js';
import { api } from '../lib/api.js';

export class CalendarEvents {
    constructor(containerEl) {
        this.el = containerEl;
        this.bindClicks();
        this.listenToEcho();
    }

    bindClicks() {
        this.el.addEventListener('click', (e) => {
            // Mark watched toggle
            const watchBtn = e.target.closest('[data-mark-watched]');
            if (watchBtn) {
                e.preventDefault();
                e.stopPropagation();
                this.toggleWatched(watchBtn);
            }
        });

        // Hover-to-change-background
        this.el.addEventListener('mouseenter', (e) => {
            const event = e.target.closest('[data-fanart]');
            if (event) {
                clearTimeout(this._hoverTimer);
                this._hoverTimer = setTimeout(() => {
                    document.body.style.backgroundImage = `url(${event.dataset.fanart})`;
                }, 1500);
            }
        }, true);

        this.el.addEventListener('mouseleave', (e) => {
            if (e.target.closest('[data-fanart]')) clearTimeout(this._hoverTimer);
        }, true);
    }

    async toggleWatched(btn) {
        const episodeId = btn.dataset.markWatched;
        const isWatched = btn.classList.contains('watched');
        // Optimistic UI
        btn.classList.toggle('watched');
        if (isWatched) {
            await api.delete(`/api/episodes/${episodeId}/watched`);
        } else {
            await api.post(`/api/episodes/${episodeId}/watched`);
        }
        // Echo event will confirm and handle side-effects
    }

    listenToEcho() {
        EchoService.on(DuckieEvents.EPISODE_MARKED_WATCHED, (e) => {
            this.updateEvent(e.episode.id, { watched: true });
        });
        EchoService.on(DuckieEvents.EPISODE_MARKED_NOT_WATCHED, (e) => {
            this.updateEvent(e.episode.id, { watched: false });
        });
        EchoService.on(DuckieEvents.EPISODE_MARKED_DOWNLOADED, (e) => {
            this.updateEvent(e.episode.id, { downloaded: true });
        });
        EchoService.on(DuckieEvents.TORRENT_UPDATE, (e) => {
            this.updateProgress(e.infoHash, e.progress);
        });
    }

    updateEvent(episodeId, state) {
        const el = this.el.querySelector(`[data-episode-id="${episodeId}"]`);
        if (!el) return;
        if ('watched' in state) el.classList.toggle('watched', state.watched);
        if ('downloaded' in state) el.classList.toggle('downloaded', state.downloaded);
    }

    updateProgress(infoHash, progress) {
        const bar = this.el.querySelector(`[data-infohash="${infoHash}"] .progress-bar`);
        if (bar) bar.style.width = progress + '%';
    }
}
```

#### Calendar: Fully Server-Rendered

The calendar is **not** a client-side component. It's a Blade template rendered by `CalendarController`. The datePicker.js (373 lines) was mostly date arithmetic + view switching — all of that moves to PHP.

**Why this works:** It's a local NativePHP app. Page loads are instant (~0ms network). Prev/next week is just a link to `GET /?date=2026-02-07&view=week`. No need for client-side date calculation or AJAX-swap of calendar HTML.

```php
// app/Http/Controllers/CalendarController.php
class CalendarController extends Controller
{
    public function index(Request $request, CalendarService $calendar)
    {
        $date = Carbon::parse($request->get('date', today()));
        $view = $request->get('view', 'week'); // week | month

        if ($view === 'week') {
            $weeks = [$calendar->getVisibleWeek($date)];
        } else {
            $weeks = $calendar->getVisibleWeeks($date);
        }

        // Flatten to get date range, query episodes once
        $allDays = collect($weeks)->flatten();
        $events = $calendar->getEventsForRange($allDays->first(), $allDays->last());

        return view('calendar.index', [
            'weeks' => $weeks,
            'events' => $events,       // grouped: date string => episodes[]
            'currentDate' => $date,
            'view' => $view,
            'weekdays' => $calendar->getWeekdayHeaders(),
            'showSpecials' => settings('calendar.show-specials'),
            'showEpisodeNumbers' => settings('calendar.show-episode-numbers'),
            'showDownloaded' => settings('calendar.show-downloaded'),
        ]);
    }
}
```

```blade
{{-- resources/views/calendar/index.blade.php --}}
<table class="calendar calendar-{{ $view }}">
  <thead>
    <tr>
      <th><a href="?date={{ $currentDate->copy()->subWeek()->toDateString() }}&view={{ $view }}">
        <i class="bi bi-chevron-left"></i>
      </a></th>
      <th colspan="5" class="switch">
        <h2>{{ $currentDate->format('F Y') }}</h2>
      </th>
      <th><a href="?date={{ $currentDate->copy()->addWeek()->toDateString() }}&view={{ $view }}">
        <i class="bi bi-chevron-right"></i>
      </a></th>
    </tr>
    <tr>
      @foreach($weekdays as $day)
        <th>{{ $day }}</th>
      @endforeach
    </tr>
  </thead>
  <tbody>
    @foreach($weeks as $week)
      <tr>
        @foreach($week as $day)
          <td class="day">
            @if(isset($events[$day->toDateString()]))
              @foreach($events[$day->toDateString()] as $event)
                @include('calendar._event', [
                    'serie' => $event->serie,
                    'episode' => $event,
                ])
              @endforeach
            @endif
            <span class="{{ $day->isToday() ? 'now' : '' }} {{ $day->month !== $currentDate->month ? 'disabled' : '' }}">
              <em class="dayofweek">{{ $day->format('D') }}</em>
              {{ $day->day }}
            </span>
          </td>
        @endforeach
      </tr>
    @endforeach
  </tbody>
</table>
```

```blade
{{-- resources/views/calendar/_event.blade.php --}}
<div class="event {{ $episode->watched ? 'watched' : '' }} {{ $episode->downloaded ? 'downloaded' : '' }}"
     data-episode-id="{{ $episode->id }}"
     data-fanart="{{ $serie->fanart }}"
     @if($episode->magnetHash) data-infohash="{{ $episode->magnetHash }}" @endif>
  <a href="/episode/{{ $episode->id }}">
    <span class="eventName">
      @if($episode->episodenumber == 1)<i class="bi bi-star-fill" style="color:#FFEB3B"></i>@endif
      @if($episode->seasonnumber == 0)<i class="bi bi-award" style="color:#3bb1ff"></i>@endif
      {{ $serie->name }}
      @if($showEpisodeNumbers) - {{ $episode->formatted_episode }}@endif
    </span>

    @if($showDownloaded && $episode->downloaded)
      <div class="progress progress-striped"><span class="progress-bar bg-success" style="width:100%"></span></div>
    @elseif($episode->magnetHash)
      <div class="progress"><span class="progress-bar" data-infohash="{{ $episode->magnetHash }}" style="width:0%"></span></div>
    @endif

    @if($episode->watched)
      <span class="bi bi-check-lg watchedpos"></span>
    @elseif($episode->hasAired() || $episode->leaked)
      <span class="bi bi-square markpos"
            data-mark-watched="{{ $episode->id }}"
            title="{{ __('COMMON/not-marked/lbl') }}"></span>
    @endif
  </a>
</div>
```

**Client-side JS for the calendar is ~80 lines** (the `CalendarEvents` class above): just Echo listeners for progress bars + watched toggles, and the hover-background timer. Everything else is links and server-rendered HTML.

#### Services: Singletons via ES6 Modules

```js
// resources/js/services/EchoService.js
import Echo from 'laravel-echo';
import { DuckieEvents } from '../constants/DuckieEvents.js';

let echoInstance = null;

function getEcho() {
    if (!echoInstance) {
        echoInstance = new Echo({
            broadcaster: 'reverb',
            key: 'duckietv-local-key',
            wsHost: '127.0.0.1',
            wsPort: 6001,
            forceTLS: false,
            disableStats: true,
        });
    }
    return echoInstance;
}

// Pre-bind channels
const channels = {
    episodes:     getEcho().channel('episodes'),
    series:       getEcho().channel('series'),
    favorites:    getEcho().channel('favorites'),
    torrents:     getEcho().channel('torrents'),
    autodownload: getEcho().channel('autodownload'),
    settings:     getEcho().channel('settings'),
};

export const EchoService = {
    /**
     * Listen for a DuckieEvents constant on the appropriate channel.
     * Infers channel from event name prefix.
     */
    on(eventName, callback) {
        const channel = this.channelFor(eventName);
        channel.listen('.' + eventName, callback);
        return this;
    },

    off(eventName, callback) {
        const channel = this.channelFor(eventName);
        channel.stopListening('.' + eventName, callback);
        return this;
    },

    /**
     * For dynamic channels like torrents.{infoHash}
     */
    onChannel(channelName, eventName, callback) {
        getEcho().channel(channelName).listen('.' + eventName, callback);
    },

    channelFor(eventName) {
        const prefix = eventName.split('.')[0];
        const map = {
            episode: channels.episodes,
            episodes: channels.episodes,
            serie: channels.series,
            series: channels.series,
            storage: channels.favorites,
            serieslist: channels.favorites,
            torrent: channels.torrents,
            torrentclient: channels.torrents,
            autodownload: channels.autodownload,
            tpbmirrorresolver: channels.settings,
            language: channels.settings,
            watchlist: channels.favorites,
        };
        return map[prefix] || channels.episodes;
    }
};
```

```js
// resources/js/lib/api.js
const BASE = '';  // same origin in NativePHP

export const api = {
    async get(url, params = {}) {
        const qs = new URLSearchParams(params).toString();
        const resp = await fetch(`${BASE}${url}${qs ? '?' + qs : ''}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        return resp.json();
    },

    async post(url, data = {}) {
        const resp = await fetch(`${BASE}${url}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(data),
        });
        return resp.json();
    },

    async delete(url) {
        return fetch(`${BASE}${url}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
    }
};
```

#### Entry Point: Boot and Mount

```js
// resources/js/app.js
import './bootstrap.js';
import { Calendar } from './components/Calendar.js';
import { SidePanel } from './components/SidePanel.js';
import { FastSearch } from './components/FastSearch.js';
import { BackgroundRotator } from './components/BackgroundRotator.js';
import { TorrentDialog } from './components/TorrentDialog.js';
import { SeriesGrid } from './components/SeriesGrid.js';

// Auto-mount components based on data attributes in the Blade HTML
document.addEventListener('DOMContentLoaded', () => {
    // Always present in the app shell
    const calendarEl = document.querySelector('[data-component="calendar"]');
    if (calendarEl) window.duckieTV.calendar = new Calendar(calendarEl, {
        view: calendarEl.dataset.view || 'week',
        showSpecials: calendarEl.dataset.showSpecials !== 'false',
    });

    const sidePanelEl = document.querySelector('[data-component="side-panel"]');
    if (sidePanelEl) window.duckieTV.sidePanel = new SidePanel(sidePanelEl);

    const fastSearchEl = document.querySelector('[data-component="fast-search"]');
    if (fastSearchEl) window.duckieTV.fastSearch = new FastSearch(fastSearchEl);

    const bgEl = document.querySelector('[data-component="background-rotator"]');
    if (bgEl) window.duckieTV.backgroundRotator = new BackgroundRotator(bgEl);

    // Page-specific components
    const seriesGridEl = document.querySelector('[data-component="series-grid"]');
    if (seriesGridEl) window.duckieTV.seriesGrid = new SeriesGrid(seriesGridEl);
});

// Global namespace for cross-component access and Blade onclick handlers
window.duckieTV = window.duckieTV || {};

// Torrent dialog is opened programmatically, not auto-mounted
window.duckieTV.openTorrentDialog = (options) => {
    new TorrentDialog(options);  // creates its own modal DOM
};
```

#### Blade Integration: data-attributes for Mounting

```html
{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @include('partials._action_bar')

    <div data-component="background-rotator"></div>

    <div data-component="calendar"
         data-view="{{ $calendarView ?? 'week' }}"
         data-show-specials="{{ $showSpecials ? 'true' : 'false' }}">
        {{-- Server-rendered initial calendar HTML --}}
        @yield('calendar')
    </div>

    <div data-component="side-panel" class="sidepanel">
        @yield('sidepanel')
    </div>

    <div data-component="fast-search"></div>
</body>
</html>
```

#### Component Lifecycle: What Stays Server vs Client

| Concern | Server (Blade + Controller) | Client (ES6 Class) |
|---|---|---|
| **Initial HTML** | ✅ Renders the page | Mounts onto rendered HTML |
| **Data fetching** | ✅ First load via Controller | Subsequent loads via `api.get()` |
| **Episode toggle** | AJAX POST → Laravel | ✅ Optimistic UI update, listen for Echo confirmation |
| **Calendar navigation** | — | ✅ Prev/next, date arithmetic, fetch new range |
| **Torrent search** | AJAX to `/api/torrents/search` | ✅ Display results, filtering, sorting |
| **Download progress** | — | ✅ Echo WebSocket, update progress bars |
| **Side panel state** | — | ✅ Show/hide/expand CSS classes |
| **Settings forms** | ✅ Standard Blade forms, POST | Minimal (toggle switches maybe) |
| **Add series flow** | ✅ Trakt search via Controller | ✅ Inline preview, add button AJAX |
| **Series grid sort/filter** | — | ✅ Client-side sort/filter on loaded data |
| **Background rotation** | — | ✅ Image preload + crossfade |

#### API Routes for Client-Side Fetching

These supplement the Blade view routes — used by the ES6 components for AJAX:

```php
// routes/api.php
Route::prefix('api')->group(function () {
    // Calendar
    Route::get('/calendar', [CalendarController::class, 'events']);     // ?start=&end= → {date: episodes[]}

    // Episodes
    Route::post('/episodes/{episode}/watched', [EpisodeController::class, 'markWatched']);
    Route::delete('/episodes/{episode}/watched', [EpisodeController::class, 'markNotWatched']);
    Route::post('/episodes/{episode}/downloaded', [EpisodeController::class, 'markDownloaded']);
    Route::delete('/episodes/{episode}/downloaded', [EpisodeController::class, 'markNotDownloaded']);

    // Series
    Route::get('/series', [SerieController::class, 'list']);            // All favorites as JSON
    Route::post('/series/{serie}/refresh', [SerieController::class, 'refresh']);
    Route::delete('/series/{serie}', [SerieController::class, 'remove']);
    Route::put('/series/{serie}/settings', [SerieController::class, 'updateSettings']);
    Route::post('/series/{serie}/recount', [SerieController::class, 'recountWatched']);

    // Trakt
    Route::get('/trakt/search', [TraktController::class, 'search']);   // ?q=
    Route::get('/trakt/trending', [TraktController::class, 'trending']);
    Route::post('/trakt/add/{traktId}', [TraktController::class, 'addToFavorites']);

    // Torrents
    Route::get('/torrents/search', [TorrentController::class, 'search']); // ?q=&engine=&sort=
    Route::post('/torrents/launch', [TorrentController::class, 'launch']); // { magnetUrl, episodeId }
    Route::get('/torrents/active', [TorrentController::class, 'active']); // Current downloads from client
    Route::get('/torrents/engines', [TorrentController::class, 'engines']); // Available search engines

    // Settings
    Route::get('/settings', [SettingsController::class, 'index']);      // All settings as JSON
    Route::put('/settings', [SettingsController::class, 'update']);     // { key: value, ... }

    // Autodownload
    Route::get('/autodownload/status', [AutoDownloadController::class, 'status']);
});
```

#### Angular Controller → Where It Goes

Most Angular controllers were just gluing data to templates. In this architecture they dissolve into:

| Angular Controller | Server Side | Client Side |
|---|---|---|
| SidepanelSerieCtrl (146 lines) | `SerieController@show` → Blade | Minimal: refresh button, remove button (AJAX) |
| SidepanelSeasonCtrl (147 lines) | `SeasonController@show` → Blade | Mark season watched (AJAX POST loop) |
| SidepanelEpisodeCtrl (~80 lines) | `EpisodeController@show` → Blade | Toggle watched/downloaded (AJAX), open torrent dialog |
| SeriesListCtrl (206 lines) | `FavoritesController@index` → Blade | `SeriesGrid.js`: sort, filter, context menu |
| TorrentCtrl (~80 lines) | `TorrentController@index` → Blade | `TorrentRemoteControl.js`: live progress via Echo |
| torrentDialogCtrl (567 lines) | `TorrentController@search` API | `TorrentDialog.js`: full client-side modal |
| FastSearchCtrl (389 lines) | `TraktController@search` API | `FastSearch.js`: full client-side overlay |
| BackupCtrl (263 lines) | `BackupController` → Blade form | Minimal: file upload, download link |
| SettingsTorrentCtrl (323 lines) | `SettingsController@torrent` → Blade | Minimal: form toggles, test connection (AJAX) |
| TraktTVCtrl (242 lines) | `TraktController@settings` → Blade | OAuth flow, import progress (AJAX + Echo) |
| CalendarCtrl (~40 lines) | `SettingsController@calendar` → Blade | Just a settings form |
| AboutCtrl (236 lines) | `AboutController` → Blade | Changelog rendering (server-side is fine) |
| AutodlstatusCtrl (156 lines) | `AutoDownloadController@status` → Blade | `EchoService.on(AUTODOWNLOAD_ACTIVITY)` live feed |

---

## PHASE 5: Event System — Laravel Reverb + Broadcasting

### Architecture Decision: Laravel Reverb WebSockets

Instead of AJAX polling or SSE, use **Laravel Reverb** — the first-party WebSocket server that ships with Laravel 11. Since DuckieTV runs entirely on the user's machine via NativePHP, Reverb is the perfect fit: self-hosted, no third-party dependency (no Pusher subscription), runs as a local process alongside the app.

**NativePHP startup consideration:** Reverb needs to run as a separate process (`php artisan reverb:start`). This must boot automatically when the NativePHP app launches. Investigate NativePHP's background process management early in the prototype phase.

### Event Constants Class

All events get a central constant definition so that publishers and listeners reference the same constant. No magic strings anywhere in the codebase.

```php
<?php
// app/Events/DuckieEvents.php

namespace App\Events;

/**
 * Central registry of all DuckieTV event constants.
 * Every broadcast/listener MUST reference these constants — no magic strings.
 *
 * Naming convention: CATEGORY_ACTION
 * Channel convention: category-specific (e.g., 'episodes', 'torrents', 'series')
 */
final class DuckieEvents
{
    // ─── Episode Events ──────────────────────────────────────────
    const EPISODE_MARKED_WATCHED        = 'episode.marked.watched';
    const EPISODE_MARKED_NOT_WATCHED    = 'episode.marked.notwatched';
    const EPISODE_MARKED_DOWNLOADED     = 'episode.marked.downloaded';
    const EPISODE_MARKED_NOT_DOWNLOADED = 'episode.marked.notdownloaded';
    const EPISODES_UPDATED              = 'episodes.updated';

    // ─── Serie Events ────────────────────────────────────────────
    const SERIE_RECOUNT_WATCHED         = 'serie.recount.watched';
    const SERIES_RECOUNT_WATCHED        = 'series.recount.watched';

    // ─── Favorites / Library Events ──────────────────────────────
    const STORAGE_UPDATE                = 'storage.update';
    const SERIESLIST_EMPTY              = 'serieslist.empty';
    const SERIESLIST_FILTER             = 'serieslist.filter';
    const SERIESLIST_GENRE_FILTER       = 'serieslist.genreFilter';
    const SERIESLIST_STATUS_FILTER      = 'serieslist.statusFilter';
    const SERIESLIST_STATE_CHANGE       = 'serieslist.stateChange';

    // ─── Calendar Events ─────────────────────────────────────────
    const CALENDAR_SET_DATE             = 'calendar.setdate';
    const EXPAND_SERIE                  = 'expand.serie';
    const SET_DATE                      = 'setDate';

    // ─── Torrent Client Events ───────────────────────────────────
    const TORRENT_CLIENT_CONNECTED      = 'torrentclient.connected';
    const TORRENT_UPDATE                = 'torrent.update';       // + .{infoHash}
    const TORRENT_SELECT                = 'torrent.select';       // + .{traktId}

    // ─── Auto-Download Events ────────────────────────────────────
    const AUTODOWNLOAD_ACTIVITY         = 'autodownload.activity';

    // ─── Background / UI Events ──────────────────────────────────
    const BACKGROUND_LOAD               = 'background.load';
    const SIDEPANEL_SIZE_CHANGE         = 'sidepanel.sizeChange';
    const SIDEPANEL_STATE_CHANGE        = 'sidepanel.stateChange';
    const QUERY_MONITOR_UPDATE          = 'queryMonitor.update';

    // ─── Mirror Resolver Events ──────────────────────────────────
    const TPB_MIRROR_RESOLVER_STATUS    = 'tpbmirrorresolver.status';

    // ─── Sync Events ────────────────────────────────────────────
    const WATCHLIST_UPDATED             = 'watchlist.updated';

    // ─── i18n Events ────────────────────────────────────────────
    const LANGUAGE_CHANGED              = 'language.changed';
}
```

### Event → Laravel Event Class Mapping

Each constant that needs server-side logic gets a proper Laravel Event class that implements `ShouldBroadcast`. Events that are purely frontend (UI state) stay as JS-only.

#### Server-Side Broadcastable Events

| Constant | Laravel Event Class | Channel | Listened By |
|---|---|---|---|
| `EPISODE_MARKED_WATCHED` | `App\Events\EpisodeMarkedWatched` | `episodes` | TraktService (sync to Trakt), Frontend (update calendar cell, toggle button) |
| `EPISODE_MARKED_NOT_WATCHED` | `App\Events\EpisodeMarkedNotWatched` | `episodes` | TraktService (unsync from Trakt), Frontend |
| `EPISODE_MARKED_DOWNLOADED` | `App\Events\EpisodeMarkedDownloaded` | `episodes` | TraktService (add to collection), Frontend (update calendar cell) |
| `EPISODE_MARKED_NOT_DOWNLOADED` | `App\Events\EpisodeMarkedNotDownloaded` | `episodes` | TraktService (remove from collection), Frontend |
| `EPISODES_UPDATED` | `App\Events\EpisodesUpdated` | `episodes` | Frontend (refresh calendar data) |
| `SERIE_RECOUNT_WATCHED` | `App\Events\SerieRecountWatched` | `series` | FavoritesService (recount), Frontend (update badges) |
| `SERIES_RECOUNT_WATCHED` | `App\Events\SeriesRecountWatched` | `series` | Same as above but for all series |
| `STORAGE_UPDATE` | `App\Events\StorageUpdated` | `favorites` | Frontend (refresh favorites list, calendar, search) |
| `SERIESLIST_EMPTY` | `App\Events\SeriesListEmpty` | `favorites` | Frontend (show add-favorites UI) |
| `TORRENT_CLIENT_CONNECTED` | `App\Events\TorrentClientConnected` | `torrents` | Frontend (enable torrent UI), AutoDownloadJob |
| `TORRENT_UPDATE` | `App\Events\TorrentUpdated` | `torrents.{infoHash}` | Frontend (update progress bar on calendar, torrent detail view) |
| `TORRENT_SELECT` | `App\Events\TorrentSelected` | `torrents.select.{traktId}` | Frontend (update episode magnetHash, start monitoring) |
| `AUTODOWNLOAD_ACTIVITY` | `App\Events\AutoDownloadActivity` | `autodownload` | Frontend (autodlstatus page live updates) |
| `TPB_MIRROR_RESOLVER_STATUS` | `App\Events\MirrorResolverStatus` | `settings` | Frontend (settings torrent page progress) |
| `WATCHLIST_UPDATED` | `App\Events\WatchlistUpdated` | `watchlist` | Frontend (refresh watchlist view) |
| `LANGUAGE_CHANGED` | `App\Events\LanguageChanged` | `settings` | Frontend (reload translations) |

#### Frontend-Only Events (JS, no server broadcast needed)

| Constant | Handling | Notes |
|---|---|---|
| `CALENDAR_SET_DATE` | JS event / URL navigation | Calendar date picker navigation |
| `SET_DATE` | JS event | Internal calendar date sync |
| `EXPAND_SERIE` | JS event | Expand condensed calendar events |
| `BACKGROUND_LOAD` | JS event | Trigger background image rotation |
| `SIDEPANEL_SIZE_CHANGE` | JS event | Panel resize animation |
| `SIDEPANEL_STATE_CHANGE` | JS event | Panel show/hide/expand |
| `QUERY_MONITOR_UPDATE` | JS event | Debug query monitor |
| `SERIESLIST_FILTER` | JS event or AJAX | Filter favorites list |
| `SERIESLIST_GENRE_FILTER` | JS event or AJAX | Genre filter |
| `SERIESLIST_STATUS_FILTER` | JS event or AJAX | Status filter |
| `SERIESLIST_STATE_CHANGE` | JS event | UI state toggle |

### Example Implementation

#### Publishing (server-side)

```php
// app/Models/Episode.php
use App\Events\DuckieEvents;
use App\Events\EpisodeMarkedWatched;
use App\Events\EpisodeMarkedDownloaded;

class Episode extends Model
{
    public function markWatched(bool $watchedDownloadedPaired = true): self
    {
        $this->update([
            'watched' => 1,
            'watched_at' => now()->getTimestampMs(),
        ]);

        if ($watchedDownloadedPaired) {
            $this->update(['downloaded' => 1]);
        }

        // Fire named event using constant
        EpisodeMarkedWatched::dispatch($this);

        if ($watchedDownloadedPaired) {
            EpisodeMarkedDownloaded::dispatch($this);
        }

        return $this;
    }
}
```

#### Event class

```php
// app/Events/EpisodeMarkedWatched.php
namespace App\Events;

use App\Models\Episode;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class EpisodeMarkedWatched implements ShouldBroadcast
{
    public const NAME = DuckieEvents::EPISODE_MARKED_WATCHED;

    public function __construct(public Episode $episode) {}

    public function broadcastOn(): Channel
    {
        return new Channel('episodes');
    }

    public function broadcastAs(): string
    {
        return self::NAME;
    }
}
```

#### Laravel Listeners (server-side reactions)

```php
// app/Providers/EventServiceProvider.php
use App\Events\DuckieEvents;
use App\Events\EpisodeMarkedWatched;
use App\Listeners\SyncEpisodeToTrakt;
use App\Listeners\RecountSerieWatched;

protected $listen = [
    EpisodeMarkedWatched::class => [
        SyncEpisodeToTrakt::class,       // Push to Trakt.tv if sync enabled
        RecountSerieWatched::class,      // Update Serie.notWatchedCount
    ],
    EpisodeMarkedNotWatched::class => [
        UnsyncEpisodeFromTrakt::class,
        RecountSerieWatched::class,
    ],
    EpisodeMarkedDownloaded::class => [
        AddToTraktCollection::class,
    ],
    EpisodeMarkedNotDownloaded::class => [
        RemoveFromTraktCollection::class,
    ],
    TorrentClientConnected::class => [
        StartTorrentMonitoring::class,
    ],
];
```

#### Frontend Listening (Echo + JS)

```js
// resources/js/echo-listeners.js
// Using Laravel Echo with Reverb driver

// Episode events — update calendar cells and toggle buttons
Echo.channel('episodes')
    .listen(DuckieEvents.EPISODE_MARKED_WATCHED, (e) => {
        updateCalendarCell(e.episode.id, { watched: true });
        updateEpisodeButton(e.episode.id, 'watched', true);
    })
    .listen(DuckieEvents.EPISODE_MARKED_DOWNLOADED, (e) => {
        updateCalendarCell(e.episode.id, { downloaded: true });
    });

// Torrent progress — live download bars on calendar
Echo.channel('torrents.' + infoHash)
    .listen(DuckieEvents.TORRENT_UPDATE, (e) => {
        updateProgressBar(e.infoHash, e.progress);
    });

// Auto-download status — live feed on autodlstatus page
Echo.channel('autodownload')
    .listen(DuckieEvents.AUTODOWNLOAD_ACTIVITY, (e) => {
        appendStatusLine(e.serie, e.episode, e.status, e.result);
    });

// Favorites list changes
Echo.channel('favorites')
    .listen(DuckieEvents.STORAGE_UPDATE, () => {
        refreshFavoritesList();
        refreshCalendar();
    });
```

#### JS Constants Mirror

```js
// resources/js/duckie-events.js
// Mirror of app/Events/DuckieEvents.php — keep in sync!

const DuckieEvents = Object.freeze({
    // Episode
    EPISODE_MARKED_WATCHED:        'episode.marked.watched',
    EPISODE_MARKED_NOT_WATCHED:    'episode.marked.notwatched',
    EPISODE_MARKED_DOWNLOADED:     'episode.marked.downloaded',
    EPISODE_MARKED_NOT_DOWNLOADED: 'episode.marked.notdownloaded',
    EPISODES_UPDATED:              'episodes.updated',

    // Serie
    SERIE_RECOUNT_WATCHED:         'serie.recount.watched',
    SERIES_RECOUNT_WATCHED:        'series.recount.watched',

    // Favorites
    STORAGE_UPDATE:                'storage.update',
    SERIESLIST_EMPTY:              'serieslist.empty',

    // Calendar
    CALENDAR_SET_DATE:             'calendar.setdate',
    EXPAND_SERIE:                  'expand.serie',

    // Torrents
    TORRENT_CLIENT_CONNECTED:      'torrentclient.connected',
    TORRENT_UPDATE:                'torrent.update',
    TORRENT_SELECT:                'torrent.select',

    // Auto-download
    AUTODOWNLOAD_ACTIVITY:         'autodownload.activity',

    // UI
    BACKGROUND_LOAD:               'background.load',
    SIDEPANEL_SIZE_CHANGE:         'sidepanel.sizeChange',
    SIDEPANEL_STATE_CHANGE:        'sidepanel.stateChange',

    // Settings
    TPB_MIRROR_RESOLVER_STATUS:    'tpbmirrorresolver.status',
    LANGUAGE_CHANGED:              'language.changed',

    // Sync
    WATCHLIST_UPDATED:             'watchlist.updated',
});
```

### Complete Event Inventory (from source grep)

Cross-referenced from `$broadcast()` and `$on()` calls across all JS files:

| Angular Event String | Publishers | Listeners | Broadcast via Reverb? |
|---|---|---|---|
| `autodownload:activity` | AutoDownloadService | AutodlstatusCtrl | ✅ Yes |
| `background:load` | calendarEvent, FavoritesService | backgroundRotator | ❌ Frontend JS only |
| `calendar:setdate` | ActionBarCtrl | datePicker | ❌ Frontend JS only |
| `episode:marked:watched` | CRUD.entities (Episode) | TraktTVv2, EpisodeWatchedMonitor | ✅ Yes |
| `episode:marked:notwatched` | CRUD.entities (Episode) | TraktTVv2, EpisodeWatchedMonitor | ✅ Yes |
| `episode:marked:downloaded` | CRUD.entities (Episode) | TraktTVv2 | ✅ Yes |
| `episode:marked:notdownloaded` | CRUD.entities (Episode) | TraktTVv2 | ✅ Yes |
| `episodes:updated` | SyncManager | (WIP) | ✅ Yes |
| `expand:serie` | calendar | calendar internals | ❌ Frontend JS only |
| `fileProgress` | (internal) | (internal) | ❌ Skip |
| `queryMonitor:update` | (debug) | queryMonitor directive | ❌ Debug only |
| `serie:recount:watched` | FavoritesService, SeriesListCtrl, SidepanelSeasonCtrl, SidepanelSeasonsCtrl, SidepanelSerieCtrl | FavoritesService | ✅ Yes |
| `series:recount:watched` | TraktTVCtrl | FavoritesService | ✅ Yes |
| `serieslist:empty` | FavoritesService | seriesList directive | ✅ Yes |
| `serieslist:filter` | LocalSeriesCtrl | SeriesListCtrl | ❌ Frontend JS only |
| `serieslist:genreFilter` | LocalSeriesCtrl | SeriesListCtrl | ❌ Frontend JS only |
| `serieslist:statusFilter` | LocalSeriesCtrl | SeriesListCtrl | ❌ Frontend JS only |
| `serieslist:stateChange` | (internal) | SeriesListCtrl | ❌ Frontend JS only |
| `setDate` | datePicker | CalendarEvents | ❌ Frontend JS only |
| `sidepanel:sizeChange` | sidePanel directive | calendar | ❌ Frontend JS only |
| `sidepanel:stateChange` | sidePanel directive | calendar | ❌ Frontend JS only |
| `storage:update` | FavoritesService | BackupCtrl, FastSearch, SeriesListCtrl, SidepanelSerieCtrl, CalendarEvents | ✅ Yes |
| `torrent:select:{traktId}` | TorrentSearchEngines, AutoDownloadService | SidepanelEpisodeCtrl, SidepanelSeasonCtrl, AutodlstatusCtrl | ✅ Yes |
| `torrent:update:{infoHash}` | BaseTorrentClient, uTorrent | TorrentCtrl, TorrentRemoteControl | ✅ Yes |
| `torrentclient:connected` | BaseTorrentClient, uTorrent | TorrentCtrl, AutoDownloadService, TorrentRemoteControl | ✅ Yes |
| `tpbmirrorresolver:status` | ThePirateBayMirrorResolver, SettingsTorrentCtrl | SettingsTorrentCtrl | ✅ Yes |
| `translateLanguageChanged` | (angular-translate) | (internal) | ❌ Use Laravel i18n |
| `watchlist:updated` | WatchlistService | WatchlistCtrl | ✅ Yes |

**Summary: 17 events broadcast via Reverb, 12 events frontend-only JS.**

### Reverb Configuration for NativePHP

```php
// config/broadcasting.php
'reverb' => [
    'driver' => 'reverb',
    'key' => 'duckietv-local-key',        // No auth needed, local only
    'secret' => 'duckietv-local-secret',
    'app_id' => 'duckietv',
    'options' => [
        'host' => '127.0.0.1',
        'port' => 6001,
        'scheme' => 'http',
    ],
],

// NativePHP boot: ensure Reverb starts with the app
// In AppServiceProvider or NativePHP lifecycle hook:
// Artisan::call('reverb:start', ['--daemon' => true]);
```

---

## PHASE 6: Support Services (Lower Priority)

| Service | Lines | Purpose | Laravel Equivalent |
|---|---|---|---|
| FanartService | 450 | Fetches show artwork from Fanart.tv | `App\Services\FanartService` |
| TMDBService | ~100 | TMDB image fetching | `App\Services\TMDBService` |
| SceneNameResolver | ~80 | Maps show names to torrent scene names | `App\Services\SceneNameResolver` |
| SceneXemResolver | ~60 | XEM season/episode mapping | `App\Services\SceneXemResolver` |
| BackupService | 192 | Export/import database | `App\Services\BackupService` |
| NotificationService | ~50 | Desktop notifications | NativePHP notification API |
| OpenSubtitles | 203 | Subtitle search | `App\Services\SubtitleService` |
| TorrentFreak | ~60 | TorrentFreak top 10 | `App\Services\TorrentFreakService` |
| TorrentHashListService | ~40 | Hash tracking | Part of Episode model |
| TorrentMonitor | ~80 | Monitors active downloads | `App\Jobs\TorrentMonitorJob` |
| EpisodeWatchedMonitor | 153 | Watched state tracking | Laravel Observer on Episode |
| SynologyAPI/DSVideo | 313 | Synology NAS integration | `App\Services\SynologyService` |
| TraktTVStorageSyncTarget | ~60 | Trakt sync target | Absorbed into TraktService |
| SeriesListState / SidePanelState / SeriesAddingState | ~30 each | UI state | Frontend JS state (ES6 modules) |

---

## Migration Execution Order

### Sprint 1: Foundation (est. 1-2 weeks)
1. `laravel new duckietv` + NativePHP setup
2. Create all 6 Eloquent models + migrations (from entity definitions above)
3. Port SettingsService with all ~100 default keys
4. Create backup import — read existing DuckieTV SQLite DB, populate new schema
5. **Milestone: Can import an existing DuckieTV database into Laravel**

### Sprint 2: Data Services (est. 2-3 weeks)
1. Port TraktTVv2 service (API wrapper, parsers)
2. Port FavoritesService (add/remove shows, fill from Trakt)
3. Port TraktTVUpdateService as scheduled Job
4. Port SceneNameResolver + SceneXemResolver
5. **Milestone: Can add shows from Trakt, update them automatically**

### Sprint 3: Calendar View (est. 1-2 weeks)
1. Port CalendarEvents service (date-range episode queries)
2. Create calendar Blade layout + controller
3. Build the week/month/date view rendering
4. Wire up episode detail sidepanel
5. **Milestone: Working calendar showing your tracked shows**

### Sprint 4: Torrent Integration (est. 2-3 weeks)
1. Port GenericTorrentSearchEngine (config + symfony/dom-crawler)
2. Port all 18 search engine configs
3. Port TorrentSearchEngines registry
4. Port 2-3 torrent clients (start with qBittorrent + Transmission)
5. Port AutoDownloadService as scheduled Job
6. **Milestone: Can search and download torrents**

### Sprint 5: Polish & Remaining (est. 2-3 weeks)
1. Port remaining torrent clients
2. Port FanartService / TMDBService
3. Settings UI
4. Backup/restore
5. i18n (18 languages in `_locales/`)
6. **Milestone: Feature parity**

---

## Key Architectural Decisions

### What gets EASIER in Laravel
- **Database:** SQLite via Eloquent is vastly better than WebSQL + CRUD.js. Migrations, relationships, query builder — all built in
- **Background jobs:** `AutoDownloadService` and `TraktTVUpdateService` become proper scheduled jobs instead of `setInterval` hacks
- **Torrent search:** Server-side HTTP + DOM parsing with no CORS issues, no browser sandbox
- **Settings:** Database-backed instead of localStorage
- **Backup:** Eloquent makes export/import trivial

### What gets HARDER
- **Calendar interactivity:** The current Angular datePicker is deeply interactive. A Blade equivalent needs JS (jQuery or Alpine.js)
- **Real-time updates:** Download progress, auto-download status — need polling or SSE instead of Angular's digest cycle
- **Side panel / state management:** Angular's `$stateProvider` neatly manages panel expand/collapse/show. In Blade, this becomes URL-driven + JS

### Recommended Stack
- **Laravel 11** + **NativePHP** (Electron backend)
- **SQLite** (same as current, via Eloquent)
- **Bootstrap 5** (already used in current templates)
- **Vite** + ES6 modules (for interactive JS components)
- `symfony/dom-crawler` (for torrent search HTML parsing)
- Laravel **Scheduler** + **Queues** for background tasks
- Laravel **Reverb** for WebSocket events
- **Laravel Prompts** / Termwind for TUI rendering

---

## Renderer-Agnostic Architecture

### The Constraint

The same services must be able to render to both a web UI (Blade + JS) and a terminal TUI. This means **services must never return HTML, Blade views, or anything presentation-specific.** They return plain data objects (DTOs / arrays / Collections). Renderers consume those objects and decide how to present them.

### Three-Layer Architecture

```
┌─────────────────────────────────────────────────────┐
│  RENDERERS (swap these, everything else stays)      │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐ │
│  │  Web (Blade  │  │  TUI        │  │  API (JSON)  │ │
│  │  + JS + Echo)│  │  (Artisan)  │  │  (future)    │ │
│  └──────┬───────┘  └──────┬──────┘  └──────┬───────┘ │
├─────────┼──────────────────┼───────────────┼─────────┤
│  PRESENTERS (format data for each renderer)          │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  │
│  │  Web         │  │  Console    │  │  JSON        │  │
│  │  Controllers │  │  Commands   │  │  Resources   │  │
│  └──────┬───────┘  └──────┬──────┘  └──────┬───────┘ │
├─────────┼──────────────────┼───────────────┼─────────┤
│  SERVICES (pure data, no presentation)               │
│  ┌─────────────────────────────────────────────────┐ │
│  │  CalendarService, FavoritesService, TraktService │ │
│  │  TorrentSearchService, AutoDownloadService, etc. │ │
│  └──────┬───────────────────────────────────────────┘ │
├─────────┼────────────────────────────────────────────┤
│  MODELS + DATABASE                                    │
│  ┌─────────────────────────────────────────────────┐ │
│  │  Serie, Season, Episode, Jackett, Setting, etc.  │ │
│  └──────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────┘
```

### Service Layer: Pure Data Returns

Services NEVER return views, formatted strings, or renderer-specific objects. They return Eloquent models, Collections, arrays, or simple DTOs.

```php
// app/Services/CalendarService.php
class CalendarService
{
    /**
     * Returns calendar data as a pure data structure.
     * Both Web and TUI renderers consume this identically.
     *
     * @return array<string, array<CalendarDay>>
     * where CalendarDay = [
     *     'date' => Carbon,
     *     'isToday' => bool,
     *     'episodes' => Collection<Episode> (with serie loaded),
     * ]
     */
    public function getWeek(Carbon $date, bool $showSpecials = true): array
    {
        $start = $date->copy()->startOfWeek($this->startDay);
        $end = $start->copy()->addDays(6)->endOfDay();

        $episodes = Episode::with('serie')
            ->whereBetween('firstaired', [$start->getTimestampMs(), $end->getTimestampMs()])
            ->when(!$showSpecials, fn($q) => $q->where('seasonnumber', '>', 0))
            ->orderBy('firstaired')
            ->get()
            ->groupBy(fn($ep) => Carbon::createFromTimestampMs($ep->firstaired)->toDateString());

        $days = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $dateStr = $d->toDateString();
            $days[$dateStr] = [
                'date'     => $d->copy(),
                'isToday'  => $d->isToday(),
                'episodes' => ($episodes[$dateStr] ?? collect())
                    ->sortBy(['firstaired', 'serie.name', 'episodenumber']),
            ];
        }
        return $days;
    }

    /**
     * Same structure, just more rows.
     */
    public function getMonth(Carbon $date, bool $showSpecials = true): array
    {
        // returns weeks[] of days[], same CalendarDay structure
    }

    /**
     * Upcoming unwatched episodes (the "todo" list).
     */
    public function getUpcoming(int $days = 7): Collection
    {
        return Episode::with('serie')
            ->where('firstaired', '<=', now()->getTimestampMs())
            ->where('firstaired', '>=', now()->subDays($days)->getTimestampMs())
            ->where('watched', 0)
            ->whereHas('serie', fn($q) => $q->where('displaycalendar', 1))
            ->orderBy('firstaired')
            ->get();
    }
}
```

```php
// app/Services/FavoritesService.php — returns models, never views
class FavoritesService
{
    public function all(string $orderBy = 'name', bool $reverse = false): Collection
    {
        return Serie::withCount(['episodes as not_watched_count' => fn($q) =>
            $q->where('watched', 0)->where('firstaired', '<=', now()->getTimestampMs())
        ])->orderBy($orderBy, $reverse ? 'desc' : 'asc')->get();
    }

    public function getById(int $id): Serie { /* ... */ }
    public function add(array $traktData): Serie { /* ... */ }
    public function remove(Serie $serie): void { /* ... */ }
}
```

```php
// app/Services/TorrentSearchService.php — returns arrays, never views
class TorrentSearchService
{
    /**
     * @return array<array{
     *     releasename: string,
     *     magnetUrl: string|null,
     *     torrentUrl: string|null,
     *     size: string,
     *     seeders: int,
     *     leechers: int,
     *     detailUrl: string|null,
     *     engine: string,
     * }>
     */
    public function search(string $query, ?string $engine = null, ?string $sort = null): array;
}
```

### Web Presenter: Controllers + Blade

```php
// app/Http/Controllers/CalendarController.php
class CalendarController extends Controller
{
    public function index(CalendarService $calendar, SettingsService $settings)
    {
        $view = $settings->get('calendar.mode', 'week');
        $data = match($view) {
            'week'  => $calendar->getWeek(now()),
            'date'  => $calendar->getMonth(now()),
        };

        return view('calendar.index', [
            'days' => $data,
            'view' => $view,
            'upcoming' => $calendar->getUpcoming(),
        ]);
    }

    // JSON endpoint for client-side navigation
    public function events(Request $request, CalendarService $calendar)
    {
        $start = Carbon::parse($request->get('start'));
        $end = Carbon::parse($request->get('end'));
        return response()->json($calendar->getWeek($start));
    }
}
```

### TUI Presenter: Artisan Commands

```php
// app/Console/Commands/CalendarCommand.php
class CalendarCommand extends Command
{
    protected $signature = 'duckie:calendar
        {--week : Show week view (default)}
        {--month : Show month view}
        {--date= : Start date (default: today)}';

    protected $description = 'Display the episode calendar';

    public function handle(CalendarService $calendar, SettingsService $settings)
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : now();
        $view = $this->option('month') ? 'date' : 'week';

        $days = match($view) {
            'week'  => $calendar->getWeek($date),
            'date'  => $calendar->getMonth($date),
        };

        // Render the same data to terminal
        $this->renderCalendar($days, $view);
    }

    private function renderCalendar(array $days, string $view): void
    {
        $headers = collect($days)->map(fn($d) => $d['date']->format('D j'))->toArray();
        $this->line('');
        $this->line('  <fg=cyan;options=bold>' . implode('  │  ', $headers) . '</>');
        $this->line('  ' . str_repeat('─', 80));

        // Find max episodes for any day to know how many rows
        $maxRows = collect($days)->max(fn($d) => $d['episodes']->count());

        for ($row = 0; $row < $maxRows; $row++) {
            $cols = [];
            foreach ($days as $day) {
                $ep = $day['episodes']->values()->get($row);
                if ($ep) {
                    $name = Str::limit($ep->serie->name, 10);
                    $code = $ep->getFormattedEpisode();
                    $watched = $ep->watched ? '<fg=green>✓</>' : ' ';
                    $downloaded = $ep->downloaded ? '<fg=yellow>↓</>' : ' ';
                    $cols[] = "{$watched}{$downloaded}{$name} {$code}";
                } else {
                    $cols[] = str_repeat(' ', 16);
                }
            }
            $this->line('  ' . implode(' │ ', $cols));
        }
    }
}
```

### Full TUI Command Set

```php
// Calendar
duckie:calendar                    // This week's episodes
duckie:calendar --month            // Month grid view
duckie:calendar --date=2026-01-01  // Specific week

// Favorites
duckie:favorites                   // List all tracked shows
duckie:favorites:add {query}       // Search Trakt + add
duckie:favorites:remove {id}       // Remove a show
duckie:favorites:refresh {id?}     // Refresh from Trakt (all if no id)

// Episodes
duckie:upcoming                    // Unwatched aired episodes
duckie:mark-watched {episode_id}   // Mark episode watched
duckie:mark-downloaded {ep_id}     // Mark episode downloaded

// Torrents
duckie:search {query}              // Search torrents
duckie:search {query} --engine=1337x
duckie:download {magnetUrl}        // Send to torrent client
duckie:torrents                    // Active downloads + progress

// Auto-download
duckie:autodownload                // Run auto-download check now
duckie:autodownload:status         // Show auto-download log

// Settings
duckie:settings                    // List all settings
duckie:settings:set {key} {value}  // Set a value

// Backup
duckie:backup:export {path}        // Export database
duckie:backup:import {path}        // Import database
```

### Rules for Service Layer

1. **Services NEVER import/use** `View`, `Response`, Blade, HTML, `$this->line()`, `echo`, or anything renderer-specific
2. **Services return:** Eloquent Models, Collections, arrays, Carbon dates, plain scalars, or DTOs
3. **Services accept:** primitives, Carbon dates, Models, or typed parameter objects — never Request objects
4. **All formatting happens in the presenter layer** — the Controller for web, the Command for TUI
5. **Business logic lives in services**, not controllers or commands. If a controller and a command both need the same logic, it MUST be in a service
6. **Events are dispatched from services/models**, never from controllers or commands. Both presenters react to the same events

---

## Forms Architecture: Formly JSON → Laravel Form Requests + Data Classes

### How Forms Work Currently

DuckieTV uses **angular-formly** with a custom `FormlyLoader` service. Forms are defined as JSON files in `templates/formly-forms/` and loaded at runtime. The JSON describes fields declaratively (type, validation, labels, conditional visibility), and formly renders them automatically.

There are **4 form definitions** used across **14 controllers:**

| JSON Form | Used By | Fields |
|---|---|---|
| `TorrentClientSettings.json` | 11 torrent client controllers (qBittorrent, Transmission, Deluge, rTorrent, Aria2, Tixati, BiglyBT, Ktorrent, tTorrent, Vuze, µTorrent WebUI) | server (url), port (0-65535), path, use_auth (checkbox), username, password, token, progressX100 |
| `SerieSettings.json` | SerieSettingsCtrl (per-show settings modal) | ignoreHideSpecials, autoDownload, alias (select), customDelay (dhm pattern), searchProvider (select), customSearchString, customIncludes, customExcludes, ignoreGlobalQuality, customSeeders (min 1), ignoreGlobalIncludes, ignoreGlobalExcludes, customSearchSizeMin/Max, dlPath |
| `JackettSearchEngine.json` | jackettSearchEngineDialogCtrl (add/edit Jackett indexer) | name (disabled when editing), apiKey (required), torznab URL (required), torznabEnabled (checkbox) |
| `SynologySettings.json` | SynologyCtrl | protocol (select: http/https), ip, port, username, password |

The pattern in every controller is:
1. `FormlyLoader.load('FormName')` → returns field definitions
2. Build a `$scope.model` object from current settings/entity
3. Optionally inject select options via `FormlyLoader.setMapping()`
4. Optionally inject validators via `FormlyLoader.setMapping()`
5. Bind `$scope.fields` and `$scope.model` to the formly directive
6. On save: coerce types (checkbox booleans back to 0/1), validate, persist

### Laravel Replacement: Form Requests + Data Classes

Each form becomes a **Data class** (defines shape + defaults) and a **Form Request** (validation rules). The service layer handles persistence. Controllers and Artisan commands both use the same Data + Validation classes.

#### Data Classes

```php
// app/Data/TorrentClientData.php
namespace App\Data;

/**
 * Represents the configuration for any torrent client.
 * Used by all 11+ client settings forms — same shape, different defaults per client.
 */
class TorrentClientData
{
    public function __construct(
        public string  $server = 'http://localhost',
        public int     $port = 8080,
        public ?string $path = null,
        public bool    $use_auth = true,
        public ?string $username = null,
        public ?string $password = null,
        public ?string $token = null,
        public bool    $progressX100 = false,
    ) {}

    /**
     * Load from settings for a specific client prefix.
     * e.g., 'qbittorrent32plus', 'transmission', 'deluge'
     */
    public static function fromSettings(string $clientPrefix, SettingsService $settings): self
    {
        return new self(
            server:       $settings->get("{$clientPrefix}.server", 'http://localhost'),
            port:         (int) $settings->get("{$clientPrefix}.port", 8080),
            path:         $settings->get("{$clientPrefix}.path"),
            use_auth:     (bool) $settings->get("{$clientPrefix}.use_auth", true),
            username:     $settings->get("{$clientPrefix}.username"),
            password:     $settings->get("{$clientPrefix}.password"),
            token:        $settings->get("{$clientPrefix}.token"),
            progressX100: (bool) $settings->get("{$clientPrefix}.progressX100", false),
        );
    }

    /**
     * Persist to settings for a specific client prefix.
     */
    public function toSettings(string $clientPrefix, SettingsService $settings): void
    {
        $settings->set("{$clientPrefix}.server", $this->server);
        $settings->set("{$clientPrefix}.port", $this->port);
        if ($this->path !== null) $settings->set("{$clientPrefix}.path", $this->path);
        $settings->set("{$clientPrefix}.use_auth", $this->use_auth);
        if ($this->username !== null) $settings->set("{$clientPrefix}.username", $this->username);
        if ($this->password !== null) $settings->set("{$clientPrefix}.password", $this->password);
        if ($this->token !== null) $settings->set("{$clientPrefix}.token", $this->token);
        $settings->set("{$clientPrefix}.progressX100", $this->progressX100);
    }

    /**
     * Which fields are visible for this client.
     * Replaces formly's hideExpression logic.
     */
    public static function visibleFields(string $clientName): array
    {
        $base = ['server', 'port'];
        return match($clientName) {
            'transmission', 'biglybt', 'vuze'
                => [...$base, 'path', 'use_auth', 'username', 'password', 'progressX100'],
            'rtorrent'
                => [...$base, 'path', 'use_auth', 'username', 'password'],
            'aria2'
                => [...$base, 'token'],
            'deluge'
                => [...$base, 'use_auth', 'password'],
            'tixati', 'ktorrent', 'ttorrent', 'utorrentwebui'
                => [...$base, 'use_auth', 'username', 'password'],
            'qbittorrent41plus'
                => [...$base, 'use_auth', 'username', 'password'],
            default
                => [...$base, 'use_auth', 'username', 'password'],
        };
    }
}
```

```php
// app/Data/SerieSettingsData.php
namespace App\Data;

use App\Models\Serie;

class SerieSettingsData
{
    public function __construct(
        public bool    $ignoreHideSpecials = false,
        public bool    $autoDownload = true,
        public ?string $alias = null,
        public ?int    $customDelay = null,         // stored as minutes
        public ?string $customDelayInput = null,     // display format: "d hh:mm"
        public ?string $searchProvider = null,
        public ?string $customSearchString = null,
        public ?string $customIncludes = null,
        public ?string $customExcludes = null,
        public bool    $ignoreGlobalQuality = false,
        public bool    $ignoreGlobalIncludes = false,
        public bool    $ignoreGlobalExcludes = false,
        public ?int    $customSeeders = null,
        public ?int    $customSearchSizeMin = null,
        public ?int    $customSearchSizeMax = null,
        public ?string $dlPath = null,
    ) {}

    public static function fromSerie(Serie $serie): self
    {
        return new self(
            ignoreHideSpecials:  (bool) $serie->ignore_hide_specials,
            autoDownload:        (bool) $serie->auto_download,
            alias:               $serie->alias,
            customDelay:         $serie->custom_delay,
            customDelayInput:    $serie->custom_delay !== null
                                     ? self::minutesToDhm($serie->custom_delay)
                                     : null,
            searchProvider:      $serie->search_provider,
            customSearchString:  $serie->custom_search_string,
            customIncludes:      $serie->custom_includes,
            customExcludes:      $serie->custom_excludes,
            ignoreGlobalQuality: (bool) $serie->ignore_global_quality,
            ignoreGlobalIncludes:(bool) $serie->ignore_global_includes,
            ignoreGlobalExcludes:(bool) $serie->ignore_global_excludes,
            customSeeders:       $serie->custom_seeders,
            customSearchSizeMin: $serie->custom_search_size_min,
            customSearchSizeMax: $serie->custom_search_size_max,
            dlPath:              $serie->dl_path,
        );
    }

    public function applySerie(Serie $serie): Serie
    {
        $serie->ignore_hide_specials  = $this->ignoreHideSpecials;
        $serie->auto_download         = $this->autoDownload;
        $serie->alias                 = $this->alias;
        $serie->custom_delay          = $this->customDelayInput !== null
                                            ? self::dhmToMinutes($this->customDelayInput)
                                            : null;
        $serie->search_provider       = $this->searchProvider;
        $serie->custom_search_string  = $this->customSearchString;
        $serie->custom_includes       = $this->customIncludes;
        $serie->custom_excludes       = $this->customExcludes;
        $serie->ignore_global_quality = $this->ignoreGlobalQuality;
        $serie->ignore_global_includes = $this->ignoreGlobalIncludes;
        $serie->ignore_global_excludes = $this->ignoreGlobalExcludes;
        $serie->custom_seeders        = $this->customSeeders;
        $serie->custom_search_size_min = $this->customSearchSizeMin;
        $serie->custom_search_size_max = $this->customSearchSizeMax;
        $serie->dl_path               = $this->dlPath;
        return $serie;
    }

    public static function minutesToDhm(int $minutes): string
    {
        $d = intdiv($minutes, 1440);
        $h = intdiv($minutes % 1440, 60);
        $m = $minutes % 60;
        return sprintf('%d %02d:%02d', $d, $h, $m);
    }

    public static function dhmToMinutes(string $dhm): int
    {
        $parts = preg_split('/[\s:]+/', $dhm);
        return ((int)$parts[0] * 1440) + ((int)$parts[1] * 60) + (int)$parts[2];
    }
}
```

```php
// app/Data/JackettEngineData.php
namespace App\Data;

use App\Models\Jackett;

class JackettEngineData
{
    public function __construct(
        public string  $name = '',
        public string  $apiKey = '',
        public string  $torznab = '',
        public bool    $torznabEnabled = false,
        public bool    $isNew = true,
    ) {}

    public static function fromJackett(Jackett $jackett): self
    {
        return new self(
            name:           $jackett->name,
            apiKey:         $jackett->apiKey,
            torznab:        $jackett->torznab,
            torznabEnabled: (bool) $jackett->torznabEnabled,
            isNew:          false,
        );
    }

    public function applyJackett(Jackett $jackett): Jackett
    {
        $jackett->name           = $this->name;
        $jackett->apiKey         = $this->apiKey;
        $jackett->torznab        = $this->torznab;
        $jackett->torznabEnabled = $this->torznabEnabled ? 1 : 0;
        $jackett->json           = json_encode($this->buildConfig());
        return $jackett;
    }

    private function buildConfig(): array
    {
        $apiVersion = str_contains($this->torznab, '/api/v2.') ? 2 : 1;
        // ... port the config building from jackettSearchEngineDialogCtrl
    }
}
```

#### Form Requests (Validation)

```php
// app/Http/Requests/TorrentClientSettingsRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TorrentClientSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'server'       => ['required', 'url'],
            'port'         => ['required', 'integer', 'min:0', 'max:65535'],
            'path'         => ['nullable', 'string'],
            'use_auth'     => ['boolean'],
            'username'     => ['nullable', 'string', 'required_if:use_auth,true'],
            'password'     => ['nullable', 'string'],
            'token'        => ['nullable', 'string'],
            'progressX100' => ['boolean'],
        ];
    }

    public function toData(): TorrentClientData
    {
        return new TorrentClientData(...$this->validated());
    }
}
```

```php
// app/Http/Requests/SerieSettingsRequest.php
namespace App\Http\Requests;

use App\Data\SerieSettingsData;
use Illuminate\Foundation\Http\FormRequest;

class SerieSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        $adPeriodMinutes = (int) app(SettingsService::class)->get('autodownload.period') * 24 * 60;

        return [
            'ignoreHideSpecials'  => ['boolean'],
            'autoDownload'        => ['boolean'],
            'alias'               => ['nullable', 'string', 'max:250'],
            'customDelayInput'    => [
                'nullable', 'string',
                'regex:/^\d{1,2}\s[0-2]\d:[0-5]\d$/',
                function ($attr, $value, $fail) use ($adPeriodMinutes) {
                    if ($value && SerieSettingsData::dhmToMinutes($value) > $adPeriodMinutes) {
                        $fail(__('Delay cannot exceed autodownload period (:max)', [
                            'max' => SerieSettingsData::minutesToDhm($adPeriodMinutes),
                        ]));
                    }
                },
            ],
            'searchProvider'      => ['nullable', 'string', 'max:20'],
            'customSearchString'  => ['nullable', 'string', 'max:150'],
            'customIncludes'      => ['nullable', 'string', 'max:150'],
            'customExcludes'      => ['nullable', 'string', 'max:150'],
            'ignoreGlobalQuality' => ['boolean'],
            'ignoreGlobalIncludes'=> ['boolean'],
            'ignoreGlobalExcludes'=> ['boolean'],
            'customSeeders'       => ['nullable', 'integer', 'min:1'],
            'customSearchSizeMin' => ['nullable', 'integer', 'min:0'],
            'customSearchSizeMax' => ['nullable', 'integer', 'min:0'],
            'dlPath'              => ['nullable', 'string'],
        ];
    }

    public function toData(): SerieSettingsData
    {
        return new SerieSettingsData(...$this->validated());
    }
}
```

```php
// app/Http/Requests/JackettEngineRequest.php
namespace App\Http\Requests;

class JackettEngineRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'           => [$this->isNew() ? 'required' : 'sometimes', 'string', 'max:40'],
            'apiKey'         => ['required', 'string', 'max:40'],
            'torznab'        => ['required', 'url', 'max:200'],
            'torznabEnabled' => ['boolean'],
        ];
    }

    private function isNew(): bool
    {
        return !$this->route('jackett');
    }

    public function toData(): JackettEngineData
    {
        return new JackettEngineData(
            ...$this->validated(),
            isNew: $this->isNew(),
        );
    }
}
```

#### Web Controller Usage

```php
// app/Http/Controllers/Settings/TorrentClientController.php
class TorrentClientController extends Controller
{
    public function edit(string $client, SettingsService $settings)
    {
        $prefix = TorrentClientData::prefixFor($client);
        $data = TorrentClientData::fromSettings($prefix, $settings);
        $visibleFields = TorrentClientData::visibleFields($client);

        return view('settings.torrent-client', [
            'client'        => $client,
            'data'          => $data,
            'visibleFields' => $visibleFields,
        ]);
    }

    public function update(string $client, TorrentClientSettingsRequest $request, SettingsService $settings)
    {
        $prefix = TorrentClientData::prefixFor($client);
        $request->toData()->toSettings($prefix, $settings);

        return redirect()->route('settings.torrent-client', $client)
            ->with('success', __('Settings saved'));
    }

    public function test(string $client, TorrentClientSettingsRequest $request, TorrentClientFactory $factory)
    {
        $torrentClient = $factory->make($client);
        $torrentClient->setConfig($request->toData());

        try {
            $torrentClient->connect();
            return response()->json(['connected' => true]);
        } catch (\Exception $e) {
            return response()->json(['connected' => false, 'error' => $e->getMessage()], 422);
        }
    }
}
```

#### TUI Command Usage (Same Data Classes, Same Validation)

```php
// app/Console/Commands/TorrentClientSettingsCommand.php
class TorrentClientSettingsCommand extends Command
{
    protected $signature = 'duckie:torrent-client
        {client : Client name (qbittorrent, transmission, deluge, etc.)}
        {--test : Test connection after saving}';

    public function handle(SettingsService $settings, TorrentClientFactory $factory)
    {
        $client = $this->argument('client');
        $prefix = TorrentClientData::prefixFor($client);
        $current = TorrentClientData::fromSettings($prefix, $settings);
        $visible = TorrentClientData::visibleFields($client);

        // Prompt for each visible field using current values as defaults
        $data = new TorrentClientData(
            server:   in_array('server', $visible)
                        ? $this->ask('Server URL', $current->server) : $current->server,
            port:     in_array('port', $visible)
                        ? (int) $this->ask('Port', $current->port) : $current->port,
            path:     in_array('path', $visible)
                        ? $this->ask('Path', $current->path) : $current->path,
            use_auth: in_array('use_auth', $visible)
                        ? $this->confirm('Use authentication?', $current->use_auth) : $current->use_auth,
            username: in_array('username', $visible)
                        ? $this->ask('Username', $current->username) : $current->username,
            password: in_array('password', $visible)
                        ? $this->secret('Password') ?? $current->password : $current->password,
            token:    in_array('token', $visible)
                        ? $this->ask('Token', $current->token) : $current->token,
        );

        // Validate using the same rules as the web form
        $validator = Validator::make((array) $data, (new TorrentClientSettingsRequest)->rules());
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return 1;
        }

        $data->toSettings($prefix, $settings);
        $this->info("✓ {$client} settings saved.");

        if ($this->option('test')) {
            $torrentClient = $factory->make($client);
            $torrentClient->setConfig($data);
            try {
                $torrentClient->connect();
                $this->info('✓ Connected successfully.');
            } catch (\Exception $e) {
                $this->error('✗ Connection failed: ' . $e->getMessage());
                return 1;
            }
        }

        return 0;
    }
}
```

### Form Migration Summary

| Current (Formly JSON) | Laravel Replacement | Shared Across |
|---|---|---|
| `TorrentClientSettings.json` + 11 controllers | `TorrentClientData` + `TorrentClientSettingsRequest` | Web form, TUI command, API |
| `SerieSettings.json` + SerieSettingsCtrl | `SerieSettingsData` + `SerieSettingsRequest` | Web modal, TUI command, API |
| `JackettSearchEngine.json` + jackettCtrl | `JackettEngineData` + `JackettEngineRequest` | Web modal, TUI command, API |
| `SynologySettings.json` + SynologyCtrl | `SynologyData` + `SynologySettingsRequest` | Web form, TUI command, API |

### Form Architecture Rules

1. **Data classes define shape.** Every form has a Data class with typed properties and constructors from/to models and settings
2. **Form Requests define validation.** All validation rules live in one place, used by both web and API routes. TUI commands validate against the same rules via `Validator::make()`
3. **Data classes handle coercion.** Boolean ↔ integer, dhm ↔ minutes, etc. lives in the Data class, not in controllers or commands
4. **Visible field logic is on the Data class.** The `visibleFields()` method replaces formly's `hideExpression`. Both web and TUI use it to know which fields to show
5. **No form rendering logic in services.** Services accept Data objects and persist them. They don't know if the data came from a web form, a TUI prompt, or an API call

---

## File-by-File Reference

### Services (js/services/) — by priority

| File | Lines | Priority | Complexity |
|---|---|---|---|
| SettingsService.js | 375 | 🔴 Critical | Medium |
| FavoritesService.js | 455 | 🔴 Critical | High |
| CalendarEvents.js | 286 | 🔴 Critical | Medium |
| TraktTVv2.js | 655 | 🔴 Critical | High |
| TraktTVUpdateService.js | 125 | 🔴 Critical | Medium |
| AutoDownloadService.js | 418 | 🔴 Critical | High |
| TorrentSearchEngines.js | 369 | 🔴 Critical | Medium |
| GenericTorrentSearchEngine.js | 468 | 🔴 Critical | High |
| BaseTorrentClient.js | 378 | 🟠 High | High |
| DuckieTorrent.js | 89 | 🟠 High | Low |
| SceneNameResolver.js | ~80 | 🟠 High | Low |
| SceneXemResolver.js | ~60 | 🟠 High | Low |
| FanartService.js | 450 | 🟡 Medium | Medium |
| TMDBService.js | ~100 | 🟡 Medium | Low |
| BackupService.js | 192 | 🟡 Medium | Low |
| EpisodeWatchedMonitor.js | 153 | 🟡 Medium | Low |
| TorrentMonitor.js | ~80 | 🟡 Medium | Medium |
| TorrentHashListService.js | ~40 | 🟡 Medium | Low |
| TraktTVTrending.js | ~80 | 🟡 Medium | Low |
| FavoritesManager.js | ~30 | 🟠 High | Low (wrapper) |
| NotificationService.js | ~50 | 🟢 Low | Low |
| OpenSubtitles.js | 203 | 🟢 Low | Medium |
| TorrentFreak.js | ~60 | 🟢 Low | Low |
| SeriesMetaTranslations.js | ~40 | 🟢 Low | Low |
| SynologyDSVideo.js | ~100 | 🟢 Low | Medium |
| SyncManager.js | ~60 | 🟢 Low | Low |

### Torrent Client Implementations (js/services/TorrentClients/)

| Client | Lines | Port Priority |
|---|---|---|
| qBittorrent41plus.js | 308 | 🔴 First (most popular) |
| Transmission.js | 245 | 🔴 First |
| Deluge.js | 226 | 🟠 Second |
| rTorrent.js | 301 | 🟠 Second |
| uTorrent.js | 801 | 🟡 Third |
| uTorrentWebUI.js | 303 | 🟡 Third |
| Aria2.js | 241 | 🟡 Third |
| Tixati.js | 328 | 🟡 Third |
| tTorrent.js | 354 | 🟢 Fourth |
| Ktorrent.js | 284 | 🟢 Fourth |
| BiglyBT.js | 100 | 🟢 Fourth |
| Vuze.js | 120 | 🟢 Fourth |
| None.js | 165 | 🔴 First (fallback) |
| TorrentData.js | ~40 | 🔴 First (shared model) |

### Controllers (js/controllers/) — become Laravel Controllers

| File | Lines | Maps To |
|---|---|---|
| sidepanel/SidepanelSerieCtrl.js | 146 | `SerieController@show` |
| sidepanel/SidepanelSeasonCtrl.js | 147 | `SeasonController@show` |
| sidepanel/SidepanelSeasonsCtrl.js | ~50 | `SeasonController@index` |
| sidepanel/SidepanelEpisodeCtrl.js | ~80 | `EpisodeController@show` |
| sidepanel/TorrentCtrl.js | ~80 | `TorrentController@index` |
| sidepanel/TorrentDetailsCtrl.js | ~60 | `TorrentController@show` |
| sidepanel/AutodlstatusCtrl.js | 156 | `AutoDownloadController@status` |
| sidepanel/AboutCtrl.js | 236 | `AboutController@index` |
| sidepanel/SettingsCtrl.js | ~30 | `SettingsController@index` |
| sidepanel/SidepanelTraktSerieCtrl.js | ~80 | `TraktController@preview` |
| sidepanel/SynologyDSVideoCtrl.js | ~60 | `SynologyController@index` |
| serieslist/SeriesListCtrl.js | 206 | `FavoritesController@index` |
| serieslist/LocalSeriesCtrl.js | ~80 | `FavoritesController@filter` |
| serieslist/TraktTVSearchCtrl.js | ~60 | `TraktController@search` |
| serieslist/TraktTVTrendingCtrl.js | ~80 | `TraktController@trending` |
| settings/BackupCtrl.js | 263 | `BackupController` |
| settings/SettingsTorrentCtrl.js | 323 | `SettingsController@torrent` |
| settings/TraktTVCtrl.js | 242 | `TraktController@settings` |
| settings/CalendarCtrl.js | ~40 | `SettingsController@calendar` |
| settings/DisplayCtrl.js | ~30 | `SettingsController@display` |
| settings/LanguageCtrl.js | ~40 | `SettingsController@language` |
| settings/SerieSettingsCtrl.js | 142 | `SerieController@settings` |
| settings/jackettSearchEngineCtrl.js | 145 | `JackettController` |
| BackupDialogCtrl.js | ~60 | `BackupController@dialog` |

---

*Generated from analysis of the full DuckieTV-angular source tree (~24,000 lines across ~130 JS files + 70 templates).*
