<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Dotswan\MapPicker\Fields\Map;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('User Information')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Full Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('John Doe')
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('email')
                                    ->label('Email Address')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->placeholder('john@example.com')
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('phone_number')
                                    ->tel()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(20)
                                    ->placeholder('+1 (555) 123-4567')
                                    ->columnSpan(1),
                                Forms\Components\Select::make('role')
                                    ->options([
                                        'user' => 'User',
                                        'admin' => 'Administrator',
                                    ])
                                    ->required()
                                    ->label('User Role')
                                    ->columnSpan(1),
                                Forms\Components\Textarea::make('address')
                                    ->required()
                                    ->placeholder('Enter Address')
                                    ->columnSpan(2),
                                DateTimePicker::make('email_verified_at')
                                    ->label('Email Verified At')
                                    ->placeholder('Verification Date')
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('password')
                                    ->password()
                                    ->dehydrateStateUsing(fn($state) => Hash::make($state))
                                    ->dehydrated(fn($state) => filled($state))
                                    ->required(fn(string $context): bool => $context === 'create')
                                    ->columnSpan(2)
                                    ->autocomplete('new-password')
                                    ->placeholder('Enter new password'),
                            ])
                            ->columns(2),
                    ]),
                Group::make()
                    ->schema([
                        Section::make('Profile Image & Location')
                            ->schema([
                                Forms\Components\FileUpload::make('image')
                                    ->image()
                                    ->maxSize(2048)
                                    ->directory('user-images')
                                    ->acceptedFileTypes(['image/*'])
                                    ->columnSpan(2),
                                Map::make('location')
                                    ->columnSpan(2)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if (is_array($state) && isset($state['lat'], $state['lng'])) {
                                            $set('latitude', $state['lat']);
                                            $set('longitude', $state['lng']);
                                        }
                                    }),
                                Forms\Components\Hidden::make('latitude'),
                                Forms\Components\Hidden::make('longitude'),
                            ])->columns(2)
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->circular()
                    ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=FFFFFF&background=25B4F8')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Full Name'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->label('Email Address'),
                Tables\Columns\TextColumn::make('phone_number')
                    ->searchable()
                    ->sortable()
                    ->label('Phone Number'),
                Tables\Columns\BadgeColumn::make('role')
                    ->colors([
                        'gray' => 'user',
                        'primary' => 'admin',
                    ])
                    ->label('Role'),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->label('Verified'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'user' => 'User',
                    ])
                    ->label('Role'),
                Tables\Filters\Filter::make('verified')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('email_verified_at'))
                    ->label('Email Verified'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->tooltip('Edit User'),
                Tables\Actions\DeleteAction::make()
                    ->tooltip('Delete User'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ListingsRelationManager::class,
            RelationManagers\ReviewsRelationManager::class,
            RelationManagers\FavoritesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
