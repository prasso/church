<?php

namespace Prasso\Church\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Prasso\Church\Filament\Resources\VolunteerPositionResource\Pages;
use Prasso\Church\Models\VolunteerPosition;
use Prasso\Church\Models\Ministry;
use Prasso\Church\Models\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class VolunteerPositionResource extends Resource
{
    protected static ?string $model = VolunteerPosition::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    
    protected static ?string $navigationGroup = 'Volunteering';
    
    protected static ?int $navigationSort = 10;
    
    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationLabel(): string
    {
        return __('Volunteer Positions');
    }

    public static function getPluralLabel(): string
    {
        return __('Volunteer Positions');
    }

    public static function getLabel(): string
    {
        return __('Volunteer Position');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Position Details'))
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('ministry_id')
                            ->label('Ministry')
                            ->relationship('ministry', 'name')
                            ->searchable(),
                        Forms\Components\Select::make('group_id')
                            ->label('Group')
                            ->relationship('group', 'name')
                            ->searchable(),
                        Forms\Components\TextInput::make('location')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('time_commitment')
                            ->maxLength(255)
                            ->helperText('E.g., "2 hours/week", "Monthly", etc.'),
                    ])->columns(2),
                
                Forms\Components\Section::make(__('Requirements & Availability'))
                    ->schema([
                        Forms\Components\TagsInput::make('skills_required')
                            ->helperText('Press Enter to add each skill'),
                        Forms\Components\TextInput::make('max_volunteers')
                            ->numeric()
                            ->minValue(0),
                        Forms\Components\DatePicker::make('start_date'),
                        Forms\Components\DatePicker::make('end_date')
                            ->after('start_date'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
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
                Tables\Columns\TextColumn::make('ministry.name')
                    ->label('Ministry')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('group.name')
                    ->label('Group')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('time_commitment')
                    ->sortable(),
                Tables\Columns\TextColumn::make('volunteers_count')
                    ->label('Volunteers')
                    ->counts('volunteers')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('ministry_id')
                    ->relationship('ministry', 'name')
                    ->label('Ministry'),
                Tables\Filters\SelectFilter::make('group_id')
                    ->relationship('group', 'name')
                    ->label('Group'),
                Tables\Filters\Filter::make('active')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true))
                    ->label('Active Positions Only'),
                Tables\Filters\Filter::make('open')
                    ->query(function (Builder $query): Builder {
                        return $query->where('is_active', true)
                            ->where(function ($query) {
                                $query->whereNull('max_volunteers')
                                    ->orWhereRaw('(SELECT COUNT(*) FROM chm_volunteer_assignments WHERE position_id = chm_volunteer_positions.id AND status = "active") < max_volunteers');
                            })
                            ->where(function ($query) {
                                $query->whereNull('start_date')
                                    ->orWhere('start_date', '<=', now());
                            })
                            ->where(function ($query) {
                                $query->whereNull('end_date')
                                    ->orWhere('end_date', '>=', now());
                            });
                    })
                    ->label('Open Positions Only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activatePositions')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivatePositions')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false])),
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
            'index' => Pages\ListVolunteerPositions::route('/'),
            'create' => Pages\CreateVolunteerPosition::route('/create'),
            'edit' => Pages\EditVolunteerPosition::route('/{record}/edit'),
            'view' => Pages\ViewVolunteerPosition::route('/{record}'),
        ];
    }
}
