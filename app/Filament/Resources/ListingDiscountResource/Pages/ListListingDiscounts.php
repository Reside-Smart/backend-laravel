<?php

namespace App\Filament\Resources\ListingDiscountResource\Pages;

use App\Filament\Resources\ListingDiscountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListListingDiscounts extends ListRecords
{
    protected static string $resource = ListingDiscountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
