<?php

namespace Prasso\Church\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Prasso\Church\Filament\Resources\PastoralVisitResource\Pages;
use Prasso\Church\Models\PastoralVisit;
use Prasso\Church\Models\Member;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class PastoralVisitResource extends Resource
{
    protected static ?string $model = PastoralVisit::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';
    
    protected static ?string $navigationGroup = 'Church Management';
    
    protected static ?int $navigationSort = 50;
    
    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationLabel(): string
    {
        return __('Pastoral Visits');
    }

    public static function getPluralLabel(): string
    {
        return __('Pastoral Visits');
    }

    public static function getLabel(): string
    {
        return __('Pastoral Visit');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'scheduled')->count() ?: null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Visit Details'))
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('purpose')
                            ->required()
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\DateTimePicker::make('scheduled_for')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'scheduled' => 'Scheduled',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'canceled' => 'Canceled',
                            ])
                            ->default('scheduled')
                            ->required(),
                        Forms\Components\Select::make('assigned_to')
                            ->label('Assigned To')
                            ->relationship('assignedTo', 'first_name', function ($query) {
                                return $query->select(['id', 'first_name', 'last_name'])
                                    ->selectRaw("CONCAT(first_name, ' ', last_name) as full_name");
                            })
                            ->getOptionLabelFromRecordUsing(fn (Member $record) => "{$record->first_name} {$record->last_name}")
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->user()?->member?->id)
                            ->required(),
                    ])->columns(2),
                
                Forms\Components\Section::make(__('Visit To'))
                    ->schema([
                        Forms\Components\Select::make('member_id')
                            ->label('Member')
                            ->relationship('member', 'first_name', function ($query) {
                                return $query->select(['id', 'first_name', 'last_name'])
                                    ->selectRaw("CONCAT(first_name, ' ', last_name) as full_name");
                            })
                            ->getOptionLabelFromRecordUsing(fn (Member $record) => "{$record->first_name} {$record->last_name}")
                            ->searchable(),
                        Forms\Components\Select::make('family_id')
                            ->relationship('family', 'name')
                            ->searchable()
                            ->preload(),
                    ])->columns(2),
                
                Forms\Components\Section::make(__('Location Information'))
                    ->schema([
                        Forms\Components\Select::make('location_type')
                            ->options([
                                'home' => 'Home',
                                'hospital' => 'Hospital',
                                'church' => 'Church',
                                'nursing_home' => 'Nursing Home',
                                'other' => 'Other',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('location_details')
                            ->maxLength(255),
                    ])->columns(2),
                
                Forms\Components\Section::make(__('Visit Completion'))
                    ->schema([
                        Forms\Components\DateTimePicker::make('started_at'),
                        Forms\Components\DateTimePicker::make('ended_at'),
                        Forms\Components\TextInput::make('duration_minutes')
                            ->numeric()
                            ->minValue(1),
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('follow_up_actions')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\DateTimePicker::make('follow_up_date'),
                    ])->columns(3),
                
                Forms\Components\Section::make(__('Spiritual Assessment'))
                    ->schema([
                        Forms\Components\TagsInput::make('spiritual_needs')
                            ->placeholder('Add spiritual needs...')
                            ->suggestions([
                                'Prayer',
                                'Counseling',
                                'Bible Study',
                                'Communion',
                                'Grief Support',
                                'Encouragement',
                                'Financial Assistance',
                                'Physical Assistance',
                            ]),
                        Forms\Components\Textarea::make('outcome_summary')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_confidential')
                            ->label('Confidential')
                            ->default(false)
                            ->helperText('Only pastoral staff can view confidential visits'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('member.full_name')
                    ->label('Member')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('family.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignedTo.full_name')
                    ->label('Assigned To')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('scheduled_for')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'scheduled' => 'warning',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'canceled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('location_type')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_confidential')
                    ->boolean()
                    ->label('Confidential'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'canceled' => 'Canceled',
                    ]),
                Tables\Filters\SelectFilter::make('assigned_to')
                    ->relationship('assignedTo', 'first_name')
                    ->label('Assigned To'),
                Tables\Filters\SelectFilter::make('location_type')
                    ->options([
                        'home' => 'Home',
                        'hospital' => 'Hospital',
                        'church' => 'Church',
                        'nursing_home' => 'Nursing Home',
                        'other' => 'Other',
                    ]),
                Tables\Filters\Filter::make('scheduled_for')
                    ->form([
                        Forms\Components\DatePicker::make('scheduled_from'),
                        Forms\Components\DatePicker::make('scheduled_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['scheduled_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('scheduled_for', '>=', $date),
                            )
                            ->when(
                                $data['scheduled_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('scheduled_for', '<=', $date),
                            );
                    }),
                Tables\Filters\Filter::make('confidential')
                    ->query(fn (Builder $query): Builder => $query->where('is_confidential', true))
                    ->label('Confidential Only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('updateStatus')
                        ->label('Update Status')
                        ->icon('heroicon-o-check-circle')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('New Status')
                                ->options([
                                    'scheduled' => 'Scheduled',
                                    'in_progress' => 'In Progress',
                                    'completed' => 'Completed',
                                    'canceled' => 'Canceled',
                                ])
                                ->required(),
                        ])
                        ->action(function (array $data, Collection $records): void {
                            foreach ($records as $record) {
                                $record->update([
                                    'status' => $data['status'],
                                ]);
                            }
                        }),
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
            'index' => Pages\ListPastoralVisits::route('/'),
            'create' => Pages\CreatePastoralVisit::route('/create'),
            'edit' => Pages\EditPastoralVisit::route('/{record}/edit'),
            'view' => Pages\ViewPastoralVisit::route('/{record}'),
        ];
    }
}
