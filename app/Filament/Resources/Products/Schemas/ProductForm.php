<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductType;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product Information')
                    ->description('Enter the basic product details.')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, ?string $state, callable $set) {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state));
                                }
                            })
                            ->placeholder('Enter product name'),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->alphaDash()
                            ->placeholder('product-slug')
                            ->helperText('URL-friendly identifier. Auto-generated from name.'),
                        MarkdownEditor::make('description')
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'link',
                                'bulletList',
                                'orderedList',
                                'heading',
                                'codeBlock',
                            ])
                            ->placeholder('Describe the product...'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Classification & Status')
                    ->description('Configure the product type and availability.')
                    ->icon('heroicon-o-tag')
                    ->schema([
                        Select::make('type')
                            ->options(ProductType::class)
                            ->default(ProductType::Plugin)
                            ->required()
                            ->native(false)
                            ->preload(),
                        Toggle::make('active')
                            ->label('Active')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger')
                            ->helperText('Inactive products are hidden from customers.'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
