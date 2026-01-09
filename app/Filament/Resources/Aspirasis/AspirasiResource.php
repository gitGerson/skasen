<?php

namespace App\Filament\Resources\Aspirasis;

use App\Filament\Resources\Aspirasis\Pages\CreateAspirasi;
use App\Filament\Resources\Aspirasis\Pages\EditAspirasi;
use App\Filament\Resources\Aspirasis\Pages\ListAspirasis;
use App\Filament\Resources\Aspirasis\Pages\ViewAspirasi;
use App\Filament\Resources\Aspirasis\Schemas\AspirasiForm;
use App\Filament\Resources\Aspirasis\Schemas\AspirasiInfolist;
use App\Filament\Resources\Aspirasis\Tables\AspirasisTable;
use App\Models\Aspirasi;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AspirasiResource extends Resource
{
    protected static ?string $model = Aspirasi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChatBubbleLeftRight;

    public static function form(Schema $schema): Schema
    {
        return AspirasiForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AspirasiInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AspirasisTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->hasRole('siswa')) {
            $query->where('user_id', $user->id);
        }

        return $query;
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
            'index' => ListAspirasis::route('/'),
            'create' => CreateAspirasi::route('/create'),
            'view' => ViewAspirasi::route('/{record}'),
            'edit' => EditAspirasi::route('/{record}/edit'),
        ];
    }
}
