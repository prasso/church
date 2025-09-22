<?php

namespace Prasso\Church\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Prasso\Church\Filament\Resources\PrayerRequestResource\Pages;
use Prasso\Church\Models\PrayerRequest;
use Prasso\Church\Models\Member;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class PrayerRequestResource extends Resource
{
    protected static ?string $model = PrayerRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';
    
    protected static ?string $navigationGroup = 'Church Management';
    
    protected static ?int $navigationSort = 40;
    
    protected static ?string $recordTitleAttribute = 'subject';

    public static function getNavigationLabel(): string
    {
        return __('Prayer Requests');
    }

    public static function getPluralLabel(): string
    {
        return __('Prayer Requests');
    }

    public static function getLabel(): string
    {
        return __('Prayer Request');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return static::getModel()::where('status', 'pending')->count() ? 'danger' : 'primary';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Prayer Request Details'))
                    ->schema([
                        Forms\Components\TextInput::make('subject')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('request_details')
                            ->required()
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('member_id')
                            ->label('For Member')
                            ->relationship('member', 'first_name', function ($query) {
                                return $query->select(['id', 'first_name', 'last_name'])
                                    ->selectRaw("CONCAT(first_name, ' ', last_name) as full_name");
                            })
                            ->getOptionLabelFromRecordUsing(fn (Member $record) => "{$record->first_name} {$record->last_name}")
                            ->searchable(),
                        Forms\Components\Select::make('requested_by')
                            ->label('Requested By')
                            ->relationship('requestedBy', 'first_name', function ($query) {
                                return $query->select(['id', 'first_name', 'last_name'])
                                    ->selectRaw("CONCAT(first_name, ' ', last_name) as full_name");
                            })
                            ->getOptionLabelFromRecordUsing(fn (Member $record) => "{$record->first_name} {$record->last_name}")
                            ->searchable(),
                        Forms\Components\DatePicker::make('request_date')
                            ->default(now())
                            ->required(),
                        Forms\Components\DatePicker::make('follow_up_date'),
                    ])->columns(2),
                
                Forms\Components\Section::make(__('Status & Visibility'))
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'in_progress' => 'In Progress',
                                'answered' => 'Answered',
                                'closed' => 'Closed',
                            ])
                            ->default('pending')
                            ->required(),
                        Forms\Components\Select::make('priority')
                            ->options([
                                'low' => 'Low',
                                'medium' => 'Medium',
                                'high' => 'High',
                                'urgent' => 'Urgent',
                            ])
                            ->default('medium')
                            ->required(),
                        Forms\Components\Toggle::make('is_confidential')
                            ->label('Confidential')
                            ->default(false)
                            ->helperText('Only staff can view confidential requests'),
                        Forms\Components\Toggle::make('share_with_group')
                            ->label('Share with Groups')
                            ->default(false),
                    ])->columns(2),
                
                Forms\Components\Section::make(__('Updates & Responses'))
                    ->schema([
                        Forms\Components\Repeater::make('updates')
                            ->relationship()
                            ->schema([
                                Forms\Components\DateTimePicker::make('update_date')
                                    ->default(now())
                                    ->required(),
                                Forms\Components\Textarea::make('update_text')
                                    ->required()
                                    ->maxLength(65535),
                                Forms\Components\Select::make('updated_by')
                                    ->relationship('updatedBy', 'first_name', function ($query) {
                                        return $query->select(['id', 'first_name', 'last_name'])
                                            ->selectRaw("CONCAT(first_name, ' ', last_name) as full_name");
                                    })
                                    ->getOptionLabelFromRecordUsing(fn (Member $record) => "{$record->first_name} {$record->last_name}")
                                    ->searchable(),
                            ])
                            ->defaultItems(0),
                    ]),
                
                Forms\Components\Section::make(__('Resolution'))
                    ->schema([
                        Forms\Components\Textarea::make('answer_details')
                            ->label('How was this prayer answered?')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\DatePicker::make('answered_date')
                            ->label('Date Answered'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('member.full_name')
                    ->label('For')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('requestedBy.full_name')
                    ->label('Requested By')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('request_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'answered' => 'success',
                        'closed' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'gray',
                        'medium' => 'info',
                        'high' => 'warning',
                        'urgent' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_confidential')
                    ->boolean()
                    ->label('Confidential'),
                Tables\Columns\TextColumn::make('follow_up_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('metadata.source')
                    ->label('Source')
                    ->formatStateUsing(fn ($state) => ucfirst($state ?? 'Manual'))
                    ->colors([
                        'primary' => fn($state) => $state === null || $state === 'manual',
                        'warning' => fn($state) => $state === 'sms',
                        'success' => fn($state) => $state === 'email',
                    ])
                    ->searchable(),
                Tables\Columns\TextColumn::make('metadata.phone')
                    ->label('Phone')
                    ->visible(fn ($record) => isset($record->metadata['phone']))
                    ->searchable(),
                Tables\Columns\TextColumn::make('metadata.sender_name')
                    ->label('SMS Sender')
                    ->visible(fn ($record) => isset($record->metadata['sender_name']))
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'answered' => 'Answered',
                        'closed' => 'Closed',
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ]),
                Tables\Filters\Filter::make('confidential')
                    ->query(fn (Builder $query): Builder => $query->where('is_confidential', true))
                    ->label('Confidential Only'),
                Tables\Filters\Filter::make('needs_follow_up')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('follow_up_date')->where('follow_up_date', '<=', now()))
                    ->label('Needs Follow-up'),
                Tables\Filters\Filter::make('from_sms')
                    ->query(fn (Builder $query): Builder => $query->fromSms())
                    ->label('SMS Prayer Requests'),
                Tables\Filters\Filter::make('recent')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(7)))
                    ->label('Last 7 Days'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('download')
                    ->label('Download as Text')
                    ->icon('heroicon-o-document-text')
                    ->action(function (PrayerRequest $record) {
                        $content = static::generateTextContent($record);
                        $filename = 'prayer-request-' . $record->id . '.txt';
                        
                        return response($content)
                            ->header('Content-Type', 'text/plain')
                            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
                    }),
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
                                    'pending' => 'Pending',
                                    'in_progress' => 'In Progress',
                                    'answered' => 'Answered',
                                    'closed' => 'Closed',
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
                    Tables\Actions\BulkAction::make('downloadSelected')
                        ->label('Download Selected as Text')
                        ->icon('heroicon-o-document-text')
                        ->action(function (Collection $records) {
                            $content = "PRAYER REQUESTS\n";
                            $content .= "Generated: " . now()->format('F j, Y g:i A') . "\n";
                            $content .= "Total: " . $records->count() . "\n\n";
                            
                            foreach ($records as $index => $record) {
                                $content .= "#" . ($index + 1) . " - " . $record->title . "\n";
                                $content .= "Status: " . ucfirst($record->status) . "\n";
                                $content .= "Date: " . $record->created_at->format('M j, Y') . "\n";
                                
                                if ($record->member) {
                                    $content .= "For: " . $record->member->full_name . "\n";
                                }
                                
                                if ($record->requestedBy) {
                                    $content .= "Requested By: " . $record->requestedBy->full_name . "\n";
                                }
                                
                                if (isset($record->metadata['source']) && $record->metadata['source'] === 'sms') {
                                    $content .= "Source: SMS\n";
                                    
                                    if (isset($record->metadata['phone'])) {
                                        $content .= "Phone: " . $record->metadata['phone'] . "\n";
                                    }
                                    
                                    if (isset($record->metadata['sender_name'])) {
                                        $content .= "Sender: " . $record->metadata['sender_name'] . "\n";
                                    }
                                }
                                
                                $content .= "\nRequest:\n" . $record->description . "\n\n";
                                
                                if ($record->status === 'answered' && !empty($record->answer)) {
                                    $content .= "Answer: " . $record->answer . "\n";
                                    $content .= "Answered on: " . $record->answered_at->format('M j, Y') . "\n";
                                }
                                
                                $content .= "\n" . str_repeat('-', 40) . "\n\n";
                            }
                            
                            return response($content)
                                ->header('Content-Type', 'text/plain')
                                ->header('Content-Disposition', 'attachment; filename="prayer-requests.txt"');
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
    
    /**
     * Generate a plain text version of the prayer request.
     *
     * @param  \Prasso\Church\Models\PrayerRequest  $record
     * @return string
     */
    public static function generateTextContent($record)
    {
        $content = "PRAYER REQUEST\n";
        $content .= "Title: {$record->title}\n";
        $content .= "Status: " . ucfirst($record->status) . "\n";
        $content .= "Date: " . $record->created_at->format('M j, Y') . "\n";
        
        if ($record->member) {
            $content .= "For: {$record->member->full_name}\n";
        }
        
        if ($record->requestedBy) {
            $content .= "Requested By: {$record->requestedBy->full_name}\n";
        }
        
        if (isset($record->metadata['source']) && $record->metadata['source'] === 'sms') {
            $content .= "Source: SMS\n";
            
            if (isset($record->metadata['phone'])) {
                $content .= "Phone: {$record->metadata['phone']}\n";
            }
            
            if (isset($record->metadata['sender_name'])) {
                $content .= "Sender: {$record->metadata['sender_name']}\n";
            }
        }
        
        $content .= "\nRequest:\n{$record->description}\n\n";
        
        if ($record->status === 'answered' && !empty($record->answer)) {
            $content .= "Answer: {$record->answer}\n";
            $content .= "Answered on: " . $record->answered_at->format('M j, Y') . "\n";
        }
        
        return $content;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrayerRequests::route('/'),
            'create' => Pages\CreatePrayerRequest::route('/create'),
            'edit' => Pages\EditPrayerRequest::route('/{record}/edit'),
            'view' => Pages\ViewPrayerRequest::route('/{record}'),
        ];
    }
}
