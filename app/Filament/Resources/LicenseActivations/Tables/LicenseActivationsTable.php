<?php

namespace App\Filament\Resources\LicenseActivations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class LicenseActivationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('license.license_key')
                    ->label('License Key')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('License key copied!')
                    ->fontFamily('mono')
                    ->limit(20)
                    ->tooltip(fn ($record) => $record->license?->license_key),
                TextColumn::make('license.customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
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
                    ->sortable()
                    ->toggleable()
                    ->fontFamily('mono'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                TextColumn::make('activated_at')
                    ->label('Activated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('deactivated_at')
                    ->label('Deactivated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
                TextColumn::make('deactivation_reason')
                    ->label('Reason')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->deactivation_reason)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All Activations')
                    ->trueLabel('Active Only')
                    ->falseLabel('Deactivated Only')
                    ->native(false),
                SelectFilter::make('license')
                    ->relationship('license', 'license_key')
                    ->searchable()
                    ->preload()
                    ->label('License'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('activated_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
