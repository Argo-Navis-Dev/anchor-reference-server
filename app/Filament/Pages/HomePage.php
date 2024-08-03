<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\WelcomeWidget;
use Filament\Pages\Dashboard;
use Filament\Widgets\AccountWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Log;

class HomePage extends Dashboard
{

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('home_lang.entity.name');
    }
    public function getTitle(): string | Htmlable
    {
        return __('home_lang.page.title');
    }

    public function getWidgets(): array
    {
        return [
            WelcomeWidget::class,
            AccountWidget::make()
        ];
    }

    public function getColumns(): int | string | array
    {
        return 3;
    }
}