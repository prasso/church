<?php

namespace Prasso\Church\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Prasso\Church\Filament\Resources\EventResource\Pages;
use Prasso\Church\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationGroup = 'Church Management';
    
    protected static ?int $navigationSort = 30;
    
    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationLabel(): string
    {
        return __('Events');
    }

    public static function getPluralLabel(): string
    {
        return __('Events');
    }

    public static function getLabel(): string
    {
        return __('Event');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Event Information'))
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('event_type_id')
                            ->label('Type')
                            ->relationship('eventType', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('location')
                            ->label('Location')
                            ->maxLength(255),
                        Forms\Components\Select::make('ministry_id')
                            ->relationship('ministry', 'name')
                            ->searchable()
                            ->preload(),
                    ])->columns(2),
                
                Forms\Components\Section::make(__('Event Details'))
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->required(),
                        Forms\Components\TimePicker::make('start_time')
                            ->label('Start Time')
                            ->seconds(false)
                            ->required(),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->helperText('Leave empty for no end date'),
                        Forms\Components\TimePicker::make('end_time')
                            ->label('End Time')
                            ->seconds(false),
                        Forms\Components\TextInput::make('capacity')
                            ->numeric()
                            ->minValue(1),
                        Forms\Components\Toggle::make('is_recurring')
                            ->label('Recurring Event')
                            ->default(false),
                        Forms\Components\Select::make('recurrence_pattern')
                            ->options([
                                'daily' => 'Daily',
                                'weekly' => 'Weekly',
                                'biweekly' => 'Bi-weekly',
                                'monthly' => 'Monthly',
                                'custom' => 'Custom',
                            ])
                            ->visible(fn (Forms\Get $get): bool => $get('is_recurring')),
                        Forms\Components\TextInput::make('recurrence_details')
                            ->maxLength(255)
                            ->visible(fn (Forms\Get $get): bool => $get('is_recurring')),
                    ])->columns(2),
                
                Forms\Components\Section::make(__('Occurrences'))
                    ->schema([
                        Forms\Components\Repeater::make('occurrences')
                            ->relationship()
                            ->schema([
                                Forms\Components\DatePicker::make('date')
                                    ->required(),
                                Forms\Components\TimePicker::make('start_time')
                                    ->seconds(false)
                                    ->required(),
                                Forms\Components\TimePicker::make('end_time')
                                    ->seconds(false),
                                Forms\Components\TextInput::make('location_override')
                                    ->maxLength(255),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'scheduled' => 'Scheduled',
                                        'cancelled' => 'Cancelled',
                                        'completed' => 'Completed',
                                    ])
                                    ->default('scheduled')
                                    ->required(),
                                Forms\Components\Textarea::make('cancellation_reason')
                                    ->maxLength(65535)
                                    ->visible(fn (Forms\Get $get): bool => $get('status') === 'cancelled'),
                            ])
                            ->columns(3),
                    ]),
                
                Forms\Components\Section::make(__('Additional Information'))
                    ->schema([
                        Forms\Components\FileUpload::make('image_url')
                            ->image()
                            ->directory('events')
                            ->visibility('public'),
                        Forms\Components\Toggle::make('is_public')
                            ->label('Public Event')
                            ->default(true)
                            ->helperText('Public events are visible to everyone'),
                        Forms\Components\Toggle::make('registration_required')
                            ->label('Registration Required')
                            ->default(false),
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('eventType.name')
                    ->label('Type')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('next_date')
                    ->label('Next Date')
                    ->getStateUsing(fn (\Prasso\Church\Models\Event $record) => optional($record->nextOccurrence())->date)
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('next_time')
                    ->label('Time')
                    ->getStateUsing(fn (\Prasso\Church\Models\Event $record) => optional($record->nextOccurrence())->start_time)
                    ->time(),
                Tables\Columns\IconColumn::make('is_recurring')
                    ->boolean()
                    ->label('Recurring'),
                Tables\Columns\TextColumn::make('occurrences_count')
                    ->counts('occurrences')
                    ->label('Occurrences'),
                Tables\Columns\IconColumn::make('is_public')
                    ->boolean()
                    ->label('Public'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event_type_id')
                    ->label('Type')
                    ->relationship('eventType', 'name')
                    ->multiple()
                    ->preload(),
                Tables\Filters\Filter::make('upcoming')
                    ->query(fn (Builder $query): Builder => $query->whereHas('occurrences', function ($query) {
                        $query->where('date', '>=', now());
                    }))
                    ->label('Upcoming Events'),
                Tables\Filters\Filter::make('recurring')
                    ->query(fn (Builder $query): Builder => $query->where('recurrence_pattern', '!=', 'none'))
                    ->label('Recurring Events'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
            'view' => Pages\ViewEvent::route('/{record}'),
        ];
    }
}
