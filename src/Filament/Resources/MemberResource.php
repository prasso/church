<?php

namespace Prasso\Church\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Prasso\Church\Filament\Resources\MemberResource\Pages;
use Prasso\Church\Models\Member;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationGroup = 'Church Management';
    
    protected static ?int $navigationSort = 10;
    
    protected static ?string $recordTitleAttribute = 'full_name';

    public static function getNavigationLabel(): string
    {
        return __('Members');
    }

    public static function getPluralLabel(): string
    {
        return __('Members');
    }

    public static function getLabel(): string
    {
        return __('Member');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Personal Information'))
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('middle_name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('last_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                                'other' => 'Other',
                                'prefer_not_to_say' => 'Prefer not to say',
                            ])
                            ->required(),
                        Forms\Components\DatePicker::make('birthdate'),
                        Forms\Components\Select::make('marital_status')
                            ->options([
                                'single' => 'Single',
                                'married' => 'Married',
                                'divorced' => 'Divorced',
                                'widowed' => 'Widowed',
                                'separated' => 'Separated',
                            ]),
                        Forms\Components\FileUpload::make('photo_path')
                            ->image()
                            ->directory('members')
                            ->visibility('public')
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('300')
                            ->imageResizeTargetHeight('300'),
                    ])->columns(2),
                
                Forms\Components\Section::make(__('Contact Information'))
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('address')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('city')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('state')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('postal_code')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('country')
                            ->default('USA')
                            ->maxLength(255),
                    ])->columns(2),
                
                Forms\Components\Section::make(__('Church Information'))
                    ->schema([
                        Forms\Components\DatePicker::make('membership_date')
                            ->label('Date Joined'),
                        Forms\Components\Select::make('membership_status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'visitor' => 'Visitor',
                                'pending' => 'Pending',
                            ])
                            ->default('active')
                            ->required(),
                        Forms\Components\DatePicker::make('baptism_date')
                            ->label('Baptism Date'),
                        Forms\Components\DatePicker::make('anniversary')
                            ->label('Anniversary Date'),
                        Forms\Components\Select::make('family_id')
                            ->relationship('family', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Toggle::make('is_head_of_household')
                            ->label('Head of Household')
                            ->default(false),
                    ])->columns(2),
                
                Forms\Components\Section::make(__('Additional Information'))
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo_path')
                    ->label('Photo')
                    ->circular(),
                Tables\Columns\TextColumn::make('first_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('membership_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'visitor' => 'warning',
                        'pending' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('membership_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('family.name')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('membership_status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'visitor' => 'Visitor',
                        'pending' => 'Pending',
                    ]),
                Tables\Filters\Filter::make('is_head_of_household')
                    ->query(fn (Builder $query): Builder => $query->where('is_head_of_household', true))
                    ->label('Heads of Household'),
                Tables\Filters\Filter::make('has_family')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('family_id'))
                    ->label('Has Family'),
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
                            Forms\Components\Select::make('membership_status')
                                ->label('New Status')
                                ->options([
                                    'active' => 'Active',
                                    'inactive' => 'Inactive',
                                    'visitor' => 'Visitor',
                                    'pending' => 'Pending',
                                ])
                                ->required(),
                        ])
                        ->action(function (array $data, Collection $records): void {
                            foreach ($records as $record) {
                                $record->update([
                                    'membership_status' => $data['membership_status'],
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
            'index' => Pages\ListMembers::route('/'),
            'create' => Pages\CreateMember::route('/create'),
            'edit' => Pages\EditMember::route('/{record}/edit'),
            'view' => Pages\ViewMember::route('/{record}'),
        ];
    }
}
