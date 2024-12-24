<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'Data Produk';
    protected static ?string $label = 'Produk';
    protected static ?string $navigationGroup = 'Data Master';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->label('Kategori Produk')
                    ->required(),
                TextInput::make('name')
                    ->maxLength(255)
                    ->required()
                    ->label('Nama Produk')
                    ->placeholder('Isi nama produk')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),
                TextInput::make('slug')
                    ->required()
                    ->readOnly()
                    ->maxLength(255)
                    ->placeholder('Slug akan diisi otomatis setelah mengisi nama produk'),
                TextInput::make('price')
                    ->required()
                    ->integer()
                    ->label('Harga Produk')
                    ->placeholder('Isi harga produk'),
                TextInput::make('size')
                    ->required()
                    ->maxLength(255)
                    ->label('Ukuran Produk')
                    ->placeholder('Isi ukuran produk'),
                TextInput::make('weight')
                    ->required()
                    ->integer()
                    ->label('Berat Produk PerKg')
                    ->placeholder('Isi berat produk'),
                TextInput::make('stock')
                    ->required()
                    ->integer()
                    ->label('Stok Produk')
                    ->placeholder('Isi stok produk')
                    ->columnSpanFull(),
                Repeater::make('members')
                    ->relationship('photos')
                    ->required()
                    ->label('Foto Produk')
                    ->schema([
                        FileUpload::make('path')
                            ->label('Foto')
                            ->required()
                            ->directory('product-photos')
                            ->disk('public')
                            ->image()
                            ->maxSize(1024)
                            ->imageEditor()
                            ->imageCropAspectRatio('1:1'),
                    ])
                    ->columnSpanFull()
                    ->grid([
                        'sm' => 2,
                        'md' => 3,
                        'lg' => 4,
                    ]),
                Textarea::make('description')
                    ->required()
                    ->label('Deskripsi Produk')
                    ->placeholder('Isi desripsi produk')
                    ->rows(10)
                    ->autosize()
                    ->columnSpan(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('Produk Kosong')
            ->emptyStateDescription('Belum ada produk, silahkan tambahkan produk disini.')
            ->emptyStateIcon('heroicon-o-folder-minus')
            ->query(Product::latest())
            ->columns([
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable(),
                ImageColumn::make('photos.path')
                    ->circular()
                    ->stacked()
                    ->limit(3)
                    ->label('Foto Produk'),
                TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price')
                    ->label('Harga Produk')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('size')
                    ->label('Ukuran'),
                TextColumn::make('weight')
                    ->label('Berat')
                    ->suffix('kg')
                    ->sortable(),
                TextColumn::make('stock')
                    ->label('Stok')
                    ->suffix('pcs')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Kategori')
                    ->relationship('category', 'name'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->modalHeading('Apakah Anda yakin ingin menghapus produk?')
                        ->modalDescription('Produk yang dihapus akan diletakkan di sampah.')
                        ->modalSubmitActionLabel('Ya, hapus!')
                        ->modalCancelActionLabel('Jangan hapus')
                        ->modalIcon('heroicon-o-trash')
                        ->successNotification(fn ($records) =>
                            Notification::make()
                                ->success()
                                ->title('Data dihapus.')
                                ->body('Sebanyak ' . $records->count() . ' data produk berhasil dihapus.')
                    ),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->modalHeading('Apakah Anda yakin ingin menghapus produk?')
                        ->modalDescription('Produk akan dihapus secara permanen.')
                        ->modalSubmitActionLabel('Ya, hapus!')
                        ->modalCancelActionLabel('Jangan hapus')
                        ->modalIcon('heroicon-o-trash')
                        ->successNotification(fn ($records) =>
                            Notification::make()
                                ->success()
                                ->title('Data dihapus permanen')
                                ->body('Sebanyak ' . $records->count() . ' data berhasil dihapus secara permanen.')
                    ),
                    Tables\Actions\RestoreBulkAction::make()
                        ->modalHeading('Apakah Anda yakin ingin memulihkan produk?')
                        ->modalDescription('Produk yang dipulihkan akan tampil di tabel kembali')
                        ->modalSubmitActionLabel('Ya, pulihkan!')
                        ->modalCancelActionLabel('Batal')
                        ->modalIcon('heroicon-o-arrow-uturn-left')
                        ->successNotification(fn ($records) =>
                            Notification::make()
                                ->success()
                                ->title('Data dipulihkan')
                                ->body('Sebanyak ' . $records->count() . ' data berhasil dipulihkan.')
                    ),
                ]),
            ])
            ->searchPlaceholder('Cari nama produk');
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
