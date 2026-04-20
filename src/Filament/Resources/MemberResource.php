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
use Illuminate\Validation\ValidationException;

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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // Super admins can see all members
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $query;
        }

        // Regular users can only see members from their own sites (excluding team_id=1)
        // Get site IDs through the team_site relationship, excluding team_id=1
        $siteIds = $user->teams()
            ->where('teams.id', '!=', 1)
            ->join('team_site', 'teams.id', '=', 'team_site.team_id')
            ->pluck('team_site.site_id')
            ->toArray();
        
        if (empty($siteIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('site_id', $siteIds);
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
                            ->label('First Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Required'),
                        Forms\Components\TextInput::make('middle_name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('last_name')
                            ->label('Last Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Required'),
                        Forms\Components\Select::make('gender')
                            ->label('Gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                                'prefer_not_to_say' => 'Prefer not to say',
                            ])
                            ->required()
                            ->helperText('Required'),
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
                            ->maxLength(255)
                            ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule, callable $get) {
                                return $rule->where('site_id', $get('site_id'));
                            })
                            ->helperText('Email must be unique within the selected site'),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('address')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('city')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('state')
                            ->maxLength(2),
                        Forms\Components\TextInput::make('postal_code')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('country')
                            ->default('USA')
                            ->maxLength(255),
                    ])->columns(2),
                
                Forms\Components\Section::make(__('Site Assignment'))
                    ->schema([
                        Forms\Components\Select::make('site_id')
                            ->options(function () {
                                $user = auth()->user();
                                
                                if (!$user) {
                                    return [];
                                }
                                
                                // Super admins can see all sites
                                if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                                    return \App\Models\Site::pluck('site_name', 'id')->toArray();
                                }
                                
                                // DEBUG: Log user info
                                \Log::info('MemberResource Site Filter Debug', [
                                    'user_id' => $user->id,
                                    'user_email' => $user->email,
                                    'is_super_admin' => method_exists($user, 'isSuperAdmin') ? $user->isSuperAdmin() : 'method_not_exists',
                                ]);
                                
                                // DEBUG: Get all user teams (excluding team_id=1 for non-super-admins)
                                $userTeams = $user->teams()->where('teams.id', '!=', 1)->get();
                                \Log::info('User Teams', [
                                    'team_count' => $userTeams->count(),
                                    'teams' => $userTeams->pluck('id', 'name')->toArray(),
                                ]);
                                
                                // DEBUG: Check team_site relationship (excluding team_id=1)
                                $teamSites = $user->teams()
                                    ->where('teams.id', '!=', 1)
                                    ->join('team_site', 'teams.id', '=', 'team_site.team_id')
                                    ->select('teams.id as team_id', 'teams.name as team_name', 'team_site.site_id')
                                    ->get();
                                \Log::info('Team Sites Join Result', [
                                    'count' => $teamSites->count(),
                                    'team_sites' => $teamSites->toArray(),
                                ]);
                                
                                $siteIds = $teamSites->pluck('site_id')->toArray();
                                
                                \Log::info('Final Site IDs', [
                                    'site_ids' => $siteIds,
                                    'is_empty' => empty($siteIds),
                                ]);
                                
                                if (empty($siteIds)) {
                                    \Log::warning('No site IDs found for user, returning empty options');
                                    return [];
                                }
                                
                                return \App\Models\Site::whereIn('id', $siteIds)
                                    ->pluck('site_name', 'id')
                                    ->toArray();
                            })
                            ->default(function () {
                                $user = auth()->user();
                                
                                if (!$user) {
                                    return null;
                                }
                                
                                // Super admins don't get auto-selection
                                if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                                    return null;
                                }
                                
                                // Get user's accessible sites (excluding team_id=1)
                                $siteIds = $user->teams()
                                    ->where('teams.id', '!=', 1)
                                    ->join('team_site', 'teams.id', '=', 'team_site.team_id')
                                    ->pluck('team_site.site_id')
                                    ->unique()
                                    ->toArray();
                                
                                // If only one site, auto-select it
                                if (count($siteIds) === 1) {
                                    return $siteIds[0];
                                }
                                
                                return null;
                            })
                            ->searchable()
                            ->preload()
                            ->label('Site')
                            ->required()
                            ->helperText('Each member must be assigned to a specific site'),
                    ])->columns(1),
                
                Forms\Components\Section::make(__('Church Information'))
                    ->schema([
                        Forms\Components\DatePicker::make('membership_date')
                            ->label('Date Joined'),
                        Forms\Components\Select::make('membership_status')
                            ->label('Membership Status')
                            ->options([
                                'visitor' => 'Visitor',
                                'regular_attendee' => 'Regular Attendee',
                                'member' => 'Member',
                                'inactive' => 'Inactive',
                                'removed' => 'Removed',
                            ])
                            ->default('member')
                            ->required()
                            ->helperText('Required'),
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
                Tables\Columns\TextColumn::make('site.site_name')
                    ->label('Site')
                    ->searchable()
                    ->sortable(),
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
                        'member' => 'success',
                        'inactive' => 'warning',
                        'removed' => 'danger',
                        'visitor' => 'warning',
                        'regular_attendee' => 'info',
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
                Tables\Filters\SelectFilter::make('site')
                    ->relationship(
                        name: 'site',
                        titleAttribute: 'site_name',
                        modifyQueryUsing: function (Builder $query) {
                            $user = auth()->user();
                            
                            if (!$user) {
                                return $query->whereRaw('1 = 0');
                            }
                            
                            // Super admins can see all sites
                            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                                return $query;
                            }
                            
                            // Regular users can only see sites from their teams
                            // DEBUG: Log user info for table filter
                            \Log::info('MemberResource Table Filter Debug', [
                                'user_id' => $user->id,
                                'user_email' => $user->email,
                                'is_super_admin' => method_exists($user, 'isSuperAdmin') ? $user->isSuperAdmin() : 'method_not_exists',
                            ]);
                            
                            // DEBUG: Get all user teams for table filter (excluding team_id=1)
                            $userTeams = $user->teams()->where('teams.id', '!=', 1)->get();
                            \Log::info('User Teams (Table Filter)', [
                                'team_count' => $userTeams->count(),
                                'teams' => $userTeams->pluck('id', 'name')->toArray(),
                            ]);
                            
                            // DEBUG: Check team_site relationship for table filter (excluding team_id=1)
                            $teamSites = $user->teams()
                                ->where('teams.id', '!=', 1)
                                ->join('team_site', 'teams.id', '=', 'team_site.team_id')
                                ->select('teams.id as team_id', 'teams.name as team_name', 'team_site.site_id')
                                ->get();
                            \Log::info('Team Sites Join Result (Table Filter)', [
                                'count' => $teamSites->count(),
                                'team_sites' => $teamSites->toArray(),
                            ]);
                            
                            $siteIds = $teamSites->pluck('site_id')->toArray();
                            
                            \Log::info('Final Site IDs (Table Filter)', [
                                'site_ids' => $siteIds,
                                'is_empty' => empty($siteIds),
                            ]);
                            
                            if (empty($siteIds)) {
                                \Log::warning('No site IDs found for user in table filter, returning empty query');
                                return $query->whereRaw('1 = 0');
                            }
                            
                            return $query->whereIn('id', $siteIds);
                        }
                    )
                    ->searchable()
                    ->preload()
                    ->label('Filter by Site'),
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

    /**
     * Custom validation to show all missing required fields at once
     */
    public static function validate(array $data): array
    {
        $requiredFields = [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'gender' => 'Gender',
            'site_id' => 'Site',
            'membership_status' => 'Membership Status',
        ];

        $missingFields = [];

        foreach ($requiredFields as $field => $label) {
            if (empty($data[$field])) {
                $missingFields[] = $label;
            }
        }

        if (!empty($missingFields)) {
            $fieldList = implode(', ', $missingFields);
            throw ValidationException::withMessages([
                'required_fields' => "The following required fields are missing: {$fieldList}. Please fill in all required fields before submitting.",
            ]);
        }

        // Validate email uniqueness within site if email is provided
        if (!empty($data['email']) && !empty($data['site_id'])) {
            $existingMember = Member::where('email', $data['email'])
                ->where('site_id', $data['site_id'])
                ->when(!empty($data['id']), function ($query) use ($data) {
                    return $query->where('id', '!=', $data['id']);
                })
                ->first();

            if ($existingMember) {
                throw ValidationException::withMessages([
                    'email' => 'A member with this email already exists in the selected site.',
                ]);
            }
        }

        return $data;
    }
}
