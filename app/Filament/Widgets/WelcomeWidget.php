<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

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
