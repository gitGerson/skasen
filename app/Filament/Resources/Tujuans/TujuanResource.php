<?php

namespace App\Filament\Resources\Tujuans;

use App\Filament\Resources\Tujuans\Pages\CreateTujuan;
use App\Filament\Resources\Tujuans\Pages\EditTujuan;
use App\Filament\Resources\Tujuans\Pages\ListTujuans;
use App\Filament\Resources\Tujuans\Schemas\TujuanForm;
use App\Filament\Resources\Tujuans\Tables\TujuansTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Models\Tujuan;
use UnitEnum;

class TujuanResource extends Resource
{
    protected static ?string $model = Tujuan::class;

    protected static string | UnitEnum | null $navigationGroup = 'Master Data';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::AtSymbol;

    public static function form(Schema $schema): Schema
    {
        return TujuanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TujuansTable::configure($table);
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
            'index' => ListTujuans::route('/'),
            // 'create' => CreateTujuan::route('/create'),
            // 'edit' => EditTujuan::route('/{record}/edit'),
        ];
    }
}
