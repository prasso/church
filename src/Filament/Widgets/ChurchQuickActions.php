<?php

namespace Prasso\Church\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\HtmlString;

class ChurchQuickActions extends Widget
{
    protected static string $view = 'church::filament.widgets.church-quick-actions';

    protected int|string|array $columnSpan = 'full';
}
