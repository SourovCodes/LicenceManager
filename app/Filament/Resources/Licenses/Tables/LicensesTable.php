<?php

namespace App\Filament\Resources\Licenses\Tables;

use App\Enums\LicenseStatus;
use App\Models\License;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class LicensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('license_key')
                    ->label('License Key')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('License key copied!')
                    ->fontFamily('mono')
                    ->weight('bold'),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->description(fn (License $record): ?string => $record->customer_email)
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->description(function (License $record): ?string {
                        if (! $record->expires_at) {
                            return 'Not activated';
                        }
                        $days = $record->daysUntilExpiry();
                        if ($days === 0) {
                            return 'Expires today';
                        }
                        if ($days < 0) {
                            return 'Expired';
                        }

                        return "{$days} days remaining";
                    })
                    ->color(function (License $record) {
                        if (! $record->expires_at) {
                            return 'gray';
                        }
                        $days = $record->daysUntilExpiry();
                        if ($days <= 0) {
                            return 'danger';
                        }
                        if ($days <= 30) {
                            return 'warning';
                        }

                        return 'success';
                    }),
                TextColumn::make('domain_usage')
                    ->label('Domain Changes')
                    ->state(fn (License $record): string => "{$record->domain_changes_used}/{$record->max_domain_changes}")
                    ->color(fn (License $record) => $record->canChangeDomain() ? 'success' : 'danger')
                    ->toggleable(),
                TextColumn::make('validity_days')
                    ->label('Validity')
                    ->suffix(' days')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('activated_at')
                    ->label('Activated')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(LicenseStatus::class)
                    ->multiple()
                    ->preload(),
                SelectFilter::make('product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                TernaryFilter::make('activated')
                    ->label('Activation Status')
                    ->placeholder('All licenses')
                    ->trueLabel('Activated only')
                    ->falseLabel('Not activated')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('activated_at'),
                        false: fn ($query) => $query->whereNull('activated_at'),
                    ),
                TernaryFilter::make('expiring_soon')
                    ->label('Expiring Soon')
                    ->placeholder('All licenses')
                    ->trueLabel('Within 30 days')
                    ->falseLabel('More than 30 days')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('expires_at')
                            ->where('expires_at', '<=', now()->addDays(30))
                            ->where('expires_at', '>', now()),
                        false: fn ($query) => $query->where(function ($q) {
                            $q->whereNull('expires_at')
                                ->orWhere('expires_at', '>', now()->addDays(30));
                        }),
                    ),
            ])
            ->deferFilters(false)
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('revoke')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Revoke License')
                    ->modalDescription('Are you sure you want to revoke this license? This action cannot be easily undone.')
                    ->visible(fn (License $record): bool => $record->status !== LicenseStatus::Revoked)
                    ->action(function (License $record) {
                        $record->update(['status' => LicenseStatus::Revoked]);
                        Notification::make()
                            ->title('License revoked')
                            ->success()
                            ->send();
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No licenses yet')
            ->emptyStateDescription('Create your first license to get started.')
            ->emptyStateIcon('heroicon-o-key');
    }
}
