<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Pages;

use App\Filament\Widgets\WelcomeWidget;
use Filament\Pages\Dashboard;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\Widget;
use Illuminate\Contracts\Support\Htmlable;

/**
 * This class represents the home page of the Anchor Reference Server application.
 */
class HomePage extends Dashboard
{

    /**
     * Represents the sort order of the navigation item.
     *
     * @var ?int $navigationSort
     */
    protected static ?int $navigationSort = 1;

    /**
     * Returns the navigation label of the entity.
     *
     * @return string The navigation label of the entity.
     */
    public static function getNavigationLabel(): string
    {
        return __('home_lang.entity.name');
    }

    /**
     * Returns the title of the page.
     *
     * @return string The title of the page.
     *
     * @throws \Throwable
     */
    public function getTitle(): string
    {
        return __('home_lang.page.title');
    }

    /**
     * Retrieve the widgets to be displayed on the home page.
     *
     * @return array<mixed>
     */
    public function getWidgets(): array
    {
        return [
            WelcomeWidget::class,
            AccountWidget::make()
        ];
    }

    /**
     * Retrieve the number of columns for display.
     *
     * @return int|string|array<mixed>
     */
    public function getColumns(): int|string|array
    {
        return 3;
    }
}
