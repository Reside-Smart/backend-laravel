<?php

namespace App\Filament\Resources\ListingResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RatingsRelationManager extends RelationManager
{
    protected static string $relationship = 'ratings';

    protected static ?string $recordTitleAttribute = 'rating';

    protected static ?string $title = 'Ratings';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('rating')
                ->label('Rating')
                ->required()
                ->numeric()
                ->minValue(0)
                ->maxValue(5)
                ->step(0.1)
                ->disabled(),
            Forms\Components\Select::make('user_id')
                ->relationship('user', 'name')
                ->required()
                ->disabled(),
            Forms\Components\DateTimePicker::make('created_at')
                ->disabled(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating Value')
                    ->formatStateUsing(fn(float $state) => number_format($state, 1) . ' ★')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('rating')
                    ->options([
                        '5' => '5 Stars',
                        '4' => '4 Stars',
                        '3' => '3 Stars',
                        '2' => '2 Stars',
                        '1' => '1 Star',
                    ])
                    ->label('Rating Filter'),
            ])
            ->headerActions([
                // No create action (read only)
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions (read only)
            ]);
    }
}
