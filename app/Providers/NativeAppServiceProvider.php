<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\MenuBar;
use Native\Desktop\Facades\Menu;
use Native\Desktop\Facades\Window;
use Native\Desktop\Events\Windows\WindowMinimized;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        // Reverb startup removed as per user request (switching to polling)

        Window::open()
            ->titleBarHidden()
            ->rememberState()
            ->width(1280)
            ->height(800)
            ->minWidth(1024)
            ->darkVibrancy()
            ->minHeight(768)
            ->title('DuckieTV.Next');

        MenuBar::create()
            ->icon(public_path('img/logo/icon16.png'))
            ->onlyShowContextMenu()
            ->withContextMenu(
                Menu::make(
                    Menu::label('DuckieTV'),
                    Menu::separator(),
                    Menu::route('home', 'Show DuckieTV'),
                    Menu::route('calendar.index', 'Show Calendar'),
                    Menu::route('series.index', 'Show Favorites'),
                    Menu::route('autodlstatus.index', 'Show ADLStatus'),
                    Menu::route('settings.index', 'Show Settings'),
                    Menu::route('about.index', 'Show About'),
                    Menu::separator(),
                    Menu::quit()
                )
            );

        Event::listen(WindowMinimized::class, function (WindowMinimized $event) {
            Window::close($event->id);
        });

    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
        ];
    }
}
