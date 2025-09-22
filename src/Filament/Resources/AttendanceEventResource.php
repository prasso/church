<?php

namespace Prasso\Church\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Prasso\Church\Models\AttendanceEvent;
use Prasso\Church\Models\Location;
use Prasso\Church\Models\Ministry;
use Prasso\Church\Models\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AttendanceEventResource extends Resource
{
    protected static ?string $model = AttendanceEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    
    protected static ?string $navigationGroup = 'Church Management';
    
    protected static ?int $navigationSort = 30;

    public static function getNavigationLabel(): string
    {
        return __('Attendance Events');
    }

    public static function getPluralLabel(): string
    {
        return __('Attendance Events');
    }

    public static function getLabel(): string
    {
        return __('Attendance Event');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Event Details'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\DateTimePicker::make('start_time')
                            ->required(),
                        Forms\Components\DateTimePicker::make('end_time'),
                        Forms\Components\Select::make('location_id')
                            ->label('Location')
                            ->relationship('location', 'name')
                            ->searchable(),
                        Forms\Components\Select::make('event_type_id')
                            ->label('Event Type')
                            ->relationship('eventType', 'name')
                            ->searchable(),
                    ])->columns(2),
                
                Forms\Components\Section::make(__('Organization'))
                    ->schema([
                        Forms\Components\Select::make('ministry_id')
                            ->label('Ministry')
                            ->relationship('ministry', 'name')
                            ->searchable(),
                        Forms\Components\Select::make('group_id')
                            ->label('Group')
                            ->relationship('group', 'name')
                            ->searchable(),
                        Forms\Components\TextInput::make('expected_attendance')
                            ->numeric()
                            ->minValue(0),
                        Forms\Components\Toggle::make('requires_check_in')
                            ->label('Requires Check-in'),
                    ])->columns(2),
                
                Forms\Components\Section::make(__('Recurrence'))
                    ->schema([
                        Forms\Components\Toggle::make('is_recurring')
                            ->label('Is Recurring Event'),
                        Forms\Components\Select::make('recurrence_pattern')
                            ->options([
                                'daily' => 'Daily',
                                'weekly' => 'Weekly',
                                'biweekly' => 'Bi-Weekly',
                                'monthly' => 'Monthly',
                                'custom' => 'Custom',
                            ])
                            ->visible(fn (callable $get) => $get('is_recurring')),
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
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('location.name')
                    ->label('Location')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ministry.name')
                    ->label('Ministry')
                    ->searchable(),
                Tables\Columns\TextColumn::make('group.name')
                    ->label('Group')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_recurring')
                    ->boolean()
                    ->label('Recurring'),
                Tables\Columns\TextColumn::make('expected_attendance')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('upcoming')
                    ->query(fn (Builder $query): Builder => $query->upcoming())
                    ->label('Upcoming Events'),
                Tables\Filters\Filter::make('past')
                    ->query(fn (Builder $query): Builder => $query->past())
                    ->label('Past Events'),
                Tables\Filters\SelectFilter::make('ministry_id')
                    ->relationship('ministry', 'name')
                    ->label('Ministry'),
                Tables\Filters\SelectFilter::make('group_id')
                    ->relationship('group', 'name')
                    ->label('Group'),
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
            'index' => AttendanceEventResource\Pages\ListAttendanceEvents::route('/'),
            'create' => AttendanceEventResource\Pages\CreateAttendanceEvent::route('/create'),
            'edit' => AttendanceEventResource\Pages\EditAttendanceEvent::route('/{record}/edit'),
            'view' => AttendanceEventResource\Pages\ViewAttendanceEvent::route('/{record}'),
        ];
    }
}
