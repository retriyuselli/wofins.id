<?php

namespace App\Filament\Resources\DocumentCategories\Schemas;

use App\Enums\DocumentCategoryType;
use App\Models\DocumentCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DocumentCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category Details')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('code')
                            ->required()
                            ->maxLength(255)
                            ->unique(table: DocumentCategory::class, column: 'code', ignoreRecord: true),
                        Select::make('type')
                            ->options(DocumentCategoryType::class)
                            ->required(),
                        TextInput::make('format_number')
                            ->label('Numbering Format')
                            ->placeholder('{SEQ}/{CAT}/{CO}/{ROMAN_MONTH}/{Y}')
                            ->helperText('{SEQ} nomor urut, {CAT} kode kategori, {CO} inisial perusahaan yang membuat dokumen, {ROMAN_MONTH}, {Y}.'),
                        Select::make('parent_id')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Parent Category'),
                        Toggle::make('is_approval_required')
                            ->label('Requires Approval Workflow')
                            ->default(true),
                    ])->columns(2),
            ]);
    }
}
