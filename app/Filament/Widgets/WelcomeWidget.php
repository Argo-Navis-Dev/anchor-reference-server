<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

/**
 * A welcome widget in order to customize the information shown on the home page.
 */
class WelcomeWidget extends Widget
{
    protected static ?int $sort = -3;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 4;

    /**
     * @var view-string
     */
    protected static string $view = '/filament/widgets/welcome';
}
