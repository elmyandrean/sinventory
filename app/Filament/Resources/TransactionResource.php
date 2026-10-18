<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Item;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Transaksi Barang';
    protected static ?string $modelLabel = 'Transaksi';
    protected static ?string $pluralModelLabel = 'Transaksi Barang';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Transaksi')
                    ->schema([
                        Forms\Components\Hidden::make('user_id')
                            ->default(fn () => Auth::id()),

                        Forms\Components\Select::make('item_id')
                            ->label('Barang Inventaris')
                            ->relationship('item', 'name')
                            ->getOptionLabelFromRecordUsing(fn (Item $record) => "{$record->name} (SKU: {$record->sku} | Sisa Stok: {$record->stock} {$record->unit})")
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $item = Item::find($state);
                                    if ($item) {
                                        $set('current_stock_info', "Stok saat ini: {$item->stock} {$item->unit}");
                                    }
                                }
                            }),

                        Forms\Components\Select::make('type')
                            ->label('Jenis Transaksi')
                            ->options([
                                'in' => 'Barang Masuk (Inbound)',
                                'out' => 'Barang Keluar (Outbound)',
                            ])
                            ->required()
                            ->native(false)
                            ->reactive(),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Jumlah (Qty)')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->helperText(fn (callable $get) => $get('current_stock_info') ?? 'Masukkan jumlah barang yang masuk/keluar'),

                        Forms\Components\DatePicker::make('transaction_date')
                            ->label('Tanggal Transaksi')
                            ->default(now())
                            ->required()
                            ->native(false),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan / Keterangan')
                            ->placeholder('Contoh: Restock supplier A, Pembelian kantor, atau Rusak/Afkir')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in' => 'INBOUND (Masuk)',
                        'out' => 'OUTBOUND (Keluar)',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'in' => 'success',
                        'out' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'in' => 'heroicon-m-arrow-down-tray',
                        'out' => 'heroicon-m-arrow-up-tray',
                        default => 'heroicon-m-question-mark-circle',
                    }),

                Tables\Columns\TextColumn::make('item.name')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Transaction $record): string => "SKU: " . ($record->item?->sku ?? '-')),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn ($record) => "{$record->quantity} " . ($record->item?->unit ?? '')),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Petugas (Staff/Admin)')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('transaction_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe Transaksi')
                    ->options([
                        'in' => 'Barang Masuk (Inbound)',
                        'out' => 'Barang Keluar (Outbound)',
                    ]),

                Tables\Filters\SelectFilter::make('item_id')
                    ->label('Barang')
                    ->relationship('item', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('transaction_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
        ];
    }
}
