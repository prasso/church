<?php

namespace Prasso\Church\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Prasso\Church\Filament\Resources\GroupResource\Pages;
use Prasso\Church\Models\Group;
use Prasso\Church\Models\Member;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationGroup = 'Church Management';
    
    protected static ?int $navigationSort = 20;
    
    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('Groups');
    }

    public static function getPluralLabel(): string
    {
        return __('Groups');
    }

    public static function getLabel(): string
    {
        return __('Group');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Group Information'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('ministry_id')
                            ->relationship('ministry', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('contact_person_id')
                            ->label('Contact Person')
                            ->relationship('contactPerson', 'first_name', function ($query) {
                                return $query->select(['id', 'first_name', 'last_name'])
                                    ->selectRaw("CONCAT(first_name, ' ', last_name) as full_name");
                            })
                            ->getOptionLabelFromRecordUsing(fn (Member $record) => "{$record->first_name} {$record->last_name}")
                            ->searchable(),
                    ])->columns(2),
                
                Forms\Components\Section::make(__('Meeting Details'))
                    ->schema([
                        Forms\Components\TextInput::make('meeting_schedule')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('meeting_location')
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('start_date')
                            ->required(),
                        Forms\Components\DatePicker::make('end_date'),
                    ])->columns(2),
                
                Forms\Components\Section::make(__('Group Settings'))
                    ->schema([
                        Forms\Components\TextInput::make('max_members')
                            ->numeric()
                            ->minValue(1),
                        Forms\Components\Toggle::make('is_open')
                            ->label('Open for New Members')
                            ->default(true),
                        Forms\Components\Toggle::make('requires_approval')
                            ->label('Requires Approval to Join')
                            ->default(false),
                    ])->columns(3),
                
                Forms\Components\Section::make(__('Members'))
                    ->schema([
                        Forms\Components\Repeater::make('members')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('member_id')
                                    ->label('Member')
                                    ->relationship('members', 'first_name', function ($query) {
                                        return $query->select(['id', 'first_name', 'last_name'])
                                            ->selectRaw("CONCAT(first_name, ' ', last_name) as full_name");
                                    })
                                    ->getOptionLabelFromRecordUsing(fn (Member $record) => "{$record->first_name} {$record->last_name}")
                                    ->searchable()
                                    ->required(),
                                Forms\Components\Select::make('role')
                                    ->options([
                                        'leader' => 'Leader',
                                        'co-leader' => 'Co-Leader',
                                        'member' => 'Member',
                                    ])
                                    ->default('member')
                                    ->required(),
                                Forms\Components\DatePicker::make('join_date')
                                    ->default(now())
                                    ->required(),
                                Forms\Components\DatePicker::make('leave_date'),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'active' => 'Active',
                                        'inactive' => 'Inactive',
                                        'pending' => 'Pending',
                                        'removed' => 'Removed',
                                    ])
                                    ->default('active')
                                    ->required(),
                                Forms\Components\Textarea::make('notes')
                                    ->maxLength(65535),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ministry.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contactPerson.full_name')
                    ->label('Contact Person')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('meeting_schedule')
                    ->searchable(),
                Tables\Columns\TextColumn::make('meeting_location')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_open')
                    ->boolean()
                    ->label('Open'),
                Tables\Columns\TextColumn::make('members_count')
                    ->counts('members')
                    ->label('Members'),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('ministry')
                    ->relationship('ministry', 'name'),
                Tables\Filters\Filter::make('is_open')
                    ->query(fn (Builder $query): Builder => $query->where('is_open', true))
                    ->label('Open Groups'),
                Tables\Filters\Filter::make('active')
                    ->query(fn (Builder $query): Builder => $query->whereNull('end_date')->orWhere('end_date', '>=', now()))
                    ->label('Active Groups'),
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
            'index' => Pages\ListGroups::route('/'),
            'create' => Pages\CreateGroup::route('/create'),
            'edit' => Pages\EditGroup::route('/{record}/edit'),
            'view' => Pages\ViewGroup::route('/{record}'),
        ];
    }
}
