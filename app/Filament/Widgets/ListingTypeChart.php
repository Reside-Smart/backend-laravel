<?php

namespace App\Filament\Widgets;

use App\Models\Listing;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;

class ListingTypeChart extends ChartWidget
{
    protected static ?string $heading = 'Properties by Type';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $rentCount = Listing::where('type', 'rent')->count();
        $sellCount = Listing::where('type', 'sell')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Properties by Type',
                    'data' => [$rentCount, $sellCount],
                    'backgroundColor' => ['#25B4F8', '#9553E9'],
                ],
            ],
            'labels' => ['Rent', 'Sell'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
