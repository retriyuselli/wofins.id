<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\Invoice;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\RelationManagers\ExpensesRelationManager;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\BaseResource;
use App\Models\Order;
use App\Models\Product;
use App\Support\CompanySubscription;
use App\Support\UserVisibility;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

class OrderResource extends BaseResource
{
    protected static ?string $model = Order::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?string $navigationLabel = 'Proyek Wedding';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-shopping-cart';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ExpensesRelationManager::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return CompanySubscription::navigationBadge(CompanySubscription::RESOURCE_ORDERS);
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return CompanySubscription::summary(CompanySubscription::RESOURCE_ORDERS);
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return CompanySubscription::canCreate(CompanySubscription::RESOURCE_ORDERS) ? 'primary' : 'warning';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['prospect.name_event', 'number'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'view-closing' => Pages\ViewClosing::route('/view-closing'),
            'customer-expenses' => Pages\CustomerExpenses::route('/customer-expenses'),
            'customer-payments' => Pages\CustomerPayments::route('/customer-payments'),
            'view' => ViewOrder::route('/{record}'),
            'edit' => EditOrder::route('/{record}/edit'),
            'invoice' => Invoice::route('/{record}/invoice'),
        ];
    }

    /**
     * Soft-delete + isolasi per company.
     */
    public static function getEloquentQuery(): Builder
    {
        return UserVisibility::constrainCompanyQuery(
            parent::getEloquentQuery()
                ->withoutGlobalScopes([SoftDeletingScope::class])
                ->with([
                    'prospect:id,name_event,date_lamaran,date_akad,date_resepsi',
                    'employee:id,name',
                    'user:id,name',
                    'items.product:id,name',
                    'company:id,company_name',
                ])
        );
    }

    public static function getItemsRepeater(): Repeater
    {
        return Repeater::make('items')
            ->relationship()
            ->schema([
                Select::make('product_id')
                    ->label('Product')
                    ->options(function () {
                        $query = Product::query()->where('stock', '>', 1);
                        UserVisibility::constrainCompanyQuery($query);

                        return $query->pluck('name', 'id');
                    })
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        $product = Product::find($state);
                        $set('unit_price', $product?->product_price ?? 0);
                        self::updateTotalPrice($get, $set);
                    })
                    ->distinct()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                    ->columnSpan([
                        'md' => 6,
                    ])
                    ->searchable(),
                TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->default(1)
                    ->columnSpan([
                        'md' => 2,
                    ])
                    ->minValue(1)
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        $product = Product::find($get('product_id'));
                        $stock = $product?->stock;
                        if ($stock !== null && (int) $state > (int) $stock) {
                            $set('quantity', (int) $stock);
                            Notification::make()->title('Stock tidak mencukupi')->warning()->send();
                        }
                        self::updateTotalPrice($get, $set);
                    }),
                TextInput::make('unit_price')
                    ->label('Unit Price')
                    ->disabled()
                    ->dehydrated()
                    ->prefix('Rp. ')
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->dehydrateStateUsing(fn ($state) => is_numeric($state) ? (int) $state : (int) preg_replace('/[^\d]/', '', (string) $state))
                    ->required()
                    ->columnSpan([
                        'md' => 4,
                    ]),
            ])
            ->collapsible()
            ->reorderable()
            ->cloneable()
            ->itemLabel(fn (array $state): ?string => Product::find($state['product_id'])?->name)
            ->extraItemActions([
                Action::make('openProduct')
                    ->tooltip('Open product')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(function (array $arguments, Repeater $component): ?string {
                        $itemData = $component->getRawItemState($arguments['item']);
                        $product = Product::find($itemData['product_id']);
                        if (! $product) {
                            return null;
                        }

                        return ProductResource::getUrl('edit', ['record' => $product]);
                    }, shouldOpenInNewTab: true)
                    ->hidden(fn (array $arguments, Repeater $component): bool => blank($component->getRawItemState($arguments['item'])['product_id'])),
            ])
            ->defaultItems(1)
            ->hiddenLabel()
            ->columns([
                'md' => 12,
            ])
            // Jangan ->live() di repeater: setelah hydrate terus $set parent field → request Livewire tanpa henti.
            ->afterStateUpdated(function (Get $get, Set $set) {
                $orderItems = $get('items') ?? [];
                $calculatedProductPengurangan = 0;
                $calculatedProductPenambahan = 0;
                $calculatedTotalPrice = 0;

                if (is_array($orderItems)) {
                    foreach ($orderItems as $item) {
                        if (! empty($item['product_id']) && ! empty($item['quantity'])) {
                            $product = Product::find($item['product_id']);
                            if ($product) {
                                $calculatedProductPengurangan += $item['quantity'] * ($product->pengurangan ?? 0);
                                $calculatedProductPenambahan += $item['quantity'] * ($product->penambahan_publish ?? 0);
                                $calculatedTotalPrice += $item['quantity'] * ($product->product_price ?? 0);
                            }
                        }
                    }
                }

                static::setIfChanged($set, $get, 'pengurangan', $calculatedProductPengurangan);
                static::setIfChanged($set, $get, 'penambahan', $calculatedProductPenambahan);
                static::setIfChanged($set, $get, 'total_price', $calculatedTotalPrice);

                $promoRaw = $get('promo') ?? 0;
                $promo = is_numeric($promoRaw) ? (int) $promoRaw : (int) preg_replace('/[^\d]/', '', (string) $promoRaw);
                $grandTotal = Order::computeGrandTotalFromValues(
                    $calculatedTotalPrice,
                    $calculatedProductPenambahan,
                    $promo,
                    $calculatedProductPengurangan
                );
                static::setIfChanged($set, $get, 'grand_total', $grandTotal);
                self::updateDependentFinancialFields($get, $set);
            });
    }

    /**
     * Hindari $set berulang nilai sama (pemicu loop Livewire + mask uang).
     */
    public static function setIfChanged(Set $set, Get $get, string $field, mixed $value): void
    {
        $current = $get($field);

        if (is_bool($value) || is_bool($current)) {
            if ((bool) $current === (bool) $value) {
                return;
            }

            $set($field, (bool) $value);

            return;
        }

        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            if ((string) $current === (string) $value) {
                return;
            }

            $set($field, $value);

            return;
        }

        $normalize = static function ($v) {
            if ($v === null || $v === '') {
                return 0;
            }

            if (is_int($v) || is_float($v)) {
                return (int) $v;
            }

            if (is_numeric($v)) {
                return (int) $v;
            }

            return (int) preg_replace('/[^\d]/', '', (string) $v);
        };

        if ($normalize($current) === $normalize($value)) {
            return;
        }

        $set($field, $value);
    }

    public static function updateTotalPrice(Get $get, Set $set): void
    {
        $selectedProducts = collect($get('items'))->filter(fn ($item) => ! empty($item['product_id']) && ! empty($item['quantity']));

        $productIds = $selectedProducts->pluck('product_id')->unique()->filter()->toArray();

        // Fetch products from DB and key by ID for efficient lookup
        $productsFromDb = Product::whereIn('id', $productIds)->get()->keyBy('id');

        $calculatedTotalPrice = 0;
        $calculatedProductPengurangan = 0;
        $calculatedProductPenambahan = 0;

        foreach ($selectedProducts as $item) {
            $productId = $item['product_id'];
            $quantity = (int) ($item['quantity'] ?? 0);

            if (! isset($productsFromDb[$productId]) || $quantity < 1) {
                continue;
            }

            $product = $productsFromDb[$productId];
            // Harga jual order memakai product_price (bukan kolom price vendor).
            $productPrice = (int) ($product->product_price ?? 0);
            $productPengurangan = (int) ($product->pengurangan ?? 0);
            $productPenambahanPublish = (int) ($product->penambahan_publish ?? 0);

            $calculatedTotalPrice += $productPrice * $quantity;
            $calculatedProductPengurangan += $productPengurangan * $quantity;
            $calculatedProductPenambahan += $productPenambahanPublish * $quantity;
        }

        static::setIfChanged($set, $get, 'total_price', $calculatedTotalPrice);
        static::setIfChanged($set, $get, 'pengurangan', $calculatedProductPengurangan);
        static::setIfChanged($set, $get, 'penambahan', $calculatedProductPenambahan);

        // Recalculate grand_total
        $promoRaw = $get('promo') ?? 0;
        $promo = is_numeric($promoRaw) ? (int) $promoRaw : (int) preg_replace('/[^\d]/', '', (string) $promoRaw);
        $grandTotal = Order::computeGrandTotalFromValues(
            $calculatedTotalPrice,
            $calculatedProductPenambahan,
            $promo,
            $calculatedProductPengurangan
        );
        static::setIfChanged($set, $get, 'grand_total', $grandTotal);

        self::updateDependentFinancialFields($get, $set);
    }

    public static function updateExchangePaid(Get $get, Set $set): void
    {
        $paidAmount = $get('paid_amount') ?? 0;
        $totalPrice = $get('total_price') ?? 0;
        $promoPrice = $get('promo') ?? 0;
        $penambahanPrice = $get('penambahan') ?? 0;
        $penguranganPrice = $get('pengurangan') ?? 0;
        $exchangePaid = $totalPrice - $paidAmount - $promoPrice - $penguranganPrice + $penambahanPrice;
        static::setIfChanged($set, $get, 'change_amount', $exchangePaid);
    }

    public static function updateDependentFinancialFields(Get $get, Set $set): void
    {
        $normalize = fn ($v) => is_numeric($v) ? (int) $v : (int) preg_replace('/[^\d]/', '', (string) $v);
        $total_price = $normalize($get('total_price') ?? 0);
        $pengurangan_val = $normalize($get('pengurangan') ?? 0);
        $promo_val = $normalize($get('promo') ?? 0);
        $penambahan_val = $normalize($get('penambahan') ?? 0);
        $grandTotal = Order::computeGrandTotalFromValues(
            $total_price,
            $penambahan_val,
            $promo_val,
            $pengurangan_val
        );
        static::setIfChanged($set, $get, 'grand_total', $grandTotal);

        $paymentItems = $get('Jika Ada Pembayaran') ?? [];
        $bayar = 0;
        if (is_array($paymentItems)) {
            foreach ($paymentItems as $paymentItem) {
                // Hanya uang masuk yang dihitung sebagai pembayaran klien.
                $tipe = $paymentItem['kategori_transaksi'] ?? 'uang_masuk';
                if ($tipe === 'uang_keluar') {
                    continue;
                }

                $nominalValue = $normalize($paymentItem['nominal'] ?? 0);
                $bayar += $nominalValue;
            }
        }
        static::setIfChanged($set, $get, 'bayar', $bayar);

        $sisa = $grandTotal - $bayar;
        static::setIfChanged($set, $get, 'sisa', $sisa);
        static::setIfChanged($set, $get, 'is_paid', $sisa <= 0);

        self::updateClosingDate($get, $set);
    }

    public static function updateClosingDate(Get $get, Set $set): void
    {
        $paymentItems = $get('Jika Ada Pembayaran') ?? [];
        if (! empty($paymentItems)) {
            usort($paymentItems, function ($a, $b) {
                return strtotime($a['tgl_bayar'] ?? 'now') <=> strtotime($b['tgl_bayar'] ?? 'now');
            });
            if (isset($paymentItems[0]['tgl_bayar']) && ! empty($paymentItems[0]['tgl_bayar'])) {
                static::setIfChanged(
                    $set,
                    $get,
                    'closing_date',
                    Carbon::parse($paymentItems[0]['tgl_bayar'])->format('Y-m-d')
                );
            }
        }
    }
}
