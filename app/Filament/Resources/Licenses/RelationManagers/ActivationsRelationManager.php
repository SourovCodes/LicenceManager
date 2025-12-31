<?php

namespace App\Filament\Resources\Licenses\RelationManagers;

use App\Models\LicenseActivation;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ActivationsRelationManager extends RelationManager
{
    protected static string $relationship = 'activations';

    protected static ?string $title = 'Domain Activations';

    protected static BackedEnum|string|null $icon = Heroicon::OutlinedComputerDesktop;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('domain')
                    ->label('Domain')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('example.com')
                    ->columnSpanFull(),
                TextInput::make('ip_address')
                    ->label('IP Address')
                    ->maxLength(45)
                    ->placeholder('192.168.1.1'),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
                DateTimePicker::make('activated_at')
                    ->label('Activated At')
                    ->default(now()),
                DateTimePicker::make('deactivated_at')
                    ->label('Deactivated At')
                    ->visible(fn ($get) => ! $get('is_active')),
                Textarea::make('deactivation_reason')
                    ->label('Deactivation Reason')
                    ->rows(2)
                    ->columnSpanFull()
                    ->visible(fn ($get) => ! $get('is_active')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('domain')
            ->columns([
                TextColumn::make('domain')
                    ->label('Domain')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Domain copied!')
                    ->icon('heroicon-o-globe-alt'),
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->fontFamily('mono')
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                TextColumn::make('activated_at')
                    ->label('Activated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All')
                    ->trueLabel('Active')
                    ->falseLabel('Deactivated'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Activation'),
            ])
            ->recordActions([
                Action::make('toggle_status')
                    ->label(fn (LicenseActivation $record) => $record->is_active ? 'Deactivate' : 'Reactivate')
                    ->icon(fn (LicenseActivation $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (LicenseActivation $record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function (LicenseActivation $record) {
                        if ($record->is_active) {
                            $record->deactivate('Manually deactivated');
                        } else {
                            $record->reactivate();
                        }
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('activated_at', 'desc');
    }
}
