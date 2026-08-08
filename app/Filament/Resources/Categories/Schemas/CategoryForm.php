<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use App\Models\Company;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        $isSuperAdmin = ProFeatures::actorIsSuperAdmin();

        return $schema
            ->components([
                Select::make('company_id')
                    ->label('Perusahaan')
                    ->options(fn () => Company::query()->orderBy('company_name')->pluck('company_name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required($isSuperAdmin)
                    ->visible($isSuperAdmin)
                    ->dehydrated($isSuperAdmin)
                    ->helperText('Wajib untuk super admin agar kategori tidak orphan.'),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $operation, $state, Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                TextInput::make('slug')
                    ->disabled()
                    ->dehydrated()
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        table: Category::class,
                        column: 'slug',
                        ignoreRecord: true,
                        modifyRuleUsing: function (Unique $rule, Get $get) {
                            $companyId = $get('company_id') ?: UserVisibility::companyId();

                            if ($companyId === null) {
                                return $rule->whereNull('company_id');
                            }

                            return $rule->where('company_id', $companyId);
                        }
                    ),
                Textarea::make('description')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
