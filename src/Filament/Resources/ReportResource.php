<?php

namespace Prasso\Church\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Prasso\Church\Models\Report;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

/**
 * Simple page class for ReportResource
 */
class ReportListPage extends \Filament\Resources\Pages\ListRecords
{
    protected static string $resource = ReportResource::class;
}

class ReportCreatePage extends \Filament\Resources\Pages\CreateRecord
{
    protected static string $resource = ReportResource::class;
}

class ReportEditPage extends \Filament\Resources\Pages\EditRecord
{
    protected static string $resource = ReportResource::class;
}

class ReportViewPage extends \Filament\Resources\Pages\ViewRecord
{
    protected static string $resource = ReportResource::class;
}

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    
    protected static ?string $navigationGroup = 'Reporting';
    
    protected static ?int $navigationSort = 10;
    
    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('Reports');
    }

    public static function getPluralLabel(): string
    {
        return __('Reports');
    }

    public static function getLabel(): string
    {
        return __('Report');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Report Details'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('report_type')
                            ->required()
                            ->options([
                                'attendance' => 'Attendance',
                                'membership' => 'Membership',
                                'giving' => 'Giving',
                            ]),
                        Forms\Components\Toggle::make('is_public')
                            ->label('Public Report')
                            ->helperText('If enabled, this report will be visible to all users')
                            ->default(false),
                    ])->columns(2),
                
                Forms\Components\Section::make(__('Report Configuration'))
                    ->schema([
                        Forms\Components\Repeater::make('columns')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('label')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'text' => 'Text',
                                        'number' => 'Number',
                                        'date' => 'Date',
                                        'boolean' => 'Boolean',
                                    ])
                                    ->required(),
                            ])
                            ->columns(3)
                            ->defaultItems(3),
                        
                        Forms\Components\KeyValue::make('filters')
                            ->keyLabel('Filter Name')
                            ->valueLabel('Default Value')
                            ->reorderable(),
                        
                        Forms\Components\KeyValue::make('settings')
                            ->keyLabel('Setting')
                            ->valueLabel('Value')
                            ->reorderable(),
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
                Tables\Columns\TextColumn::make('report_type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->colors([
                        'primary' => fn ($state) => $state === 'attendance',
                        'success' => fn ($state) => $state === 'membership',
                        'warning' => fn ($state) => $state === 'giving',
                    ])
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_public')
                    ->label('Public')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lastRun.created_at')
                    ->label('Last Run')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('report_type')
                    ->options([
                        'attendance' => 'Attendance',
                        'membership' => 'Membership',
                        'giving' => 'Giving',
                    ]),
                Tables\Filters\Filter::make('is_public')
                    ->query(fn (Builder $query): Builder => $query->where('is_public', true))
                    ->label('Public Reports Only'),
                Tables\Filters\Filter::make('my_reports')
                    ->query(fn (Builder $query): Builder => $query->where('created_by', auth()->id()))
                    ->label('My Reports Only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('run')
                    ->label('Run Report')
                    ->icon('heroicon-o-play')
                    ->url(fn (Report $record) => route('filament.site-admin.resources.reports.run', ['record' => $record->id]))
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('makePublic')
                        ->label('Make Public')
                        ->icon('heroicon-o-globe-alt')
                        ->action(fn (Collection $records) => $records->each->update(['is_public' => true])),
                    Tables\Actions\BulkAction::make('makePrivate')
                        ->label('Make Private')
                        ->icon('heroicon-o-lock-closed')
                        ->action(fn (Collection $records) => $records->each->update(['is_public' => false])),
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
            'index' => ReportListPage::route('/'),
            'create' => ReportCreatePage::route('/create'),
            'edit' => ReportEditPage::route('/{record}/edit'),
            'view' => ReportViewPage::route('/{record}'),
        ];
    }
}
