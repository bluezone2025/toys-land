<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\RelationManagers\OrdersRelationManager;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'المبيعات';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'customers';

    protected static ?string $modelLabel = 'عميل';

    protected static ?string $pluralModelLabel = 'العملاء والمستخدمين';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->columnSpanFull()->schema([
                TextInput::make('name')->label('الاسم')->required()->maxLength(120),
                TextInput::make('email')->label('البريد الإلكتروني')->email()->required()->unique(ignoreRecord: true),
                TextInput::make('phone')->label('النقال')->tel()->maxLength(20),
                TextInput::make('password')->label('كلمة مرور جديدة')->password()->revealable()->minLength(8)
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation) => $operation === 'create')
                    ->helperText('اتركها فاضية إذا ما تبي تغيرها'),
                Toggle::make('is_admin')->label('مدير (صلاحية دخول لوحة التحكم)')
                    ->disabled(fn (?User $record) => $record?->is(auth()->user())),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('name')->label('الاسم')->searchable(['name', 'email', 'phone'])->description(fn (User $r) => $r->email),
                TextColumn::make('phone')->label('النقال')->placeholder('—'),
                TextColumn::make('orders_count')->label('الطلبات')->counts('orders')->badge()->sortable(),
                TextColumn::make('orders_sum_total')->label('إجمالي المشتريات')->sum(['orders' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'total')->numeric(3)->suffix(' د.ك')->sortable()->placeholder('0'),
                IconColumn::make('is_admin')->label('مدير')->boolean(),
                TextColumn::make('last_seen_at')->label('آخر ظهور')->since()->sortable()->placeholder('—'),
                TextColumn::make('created_at')->label('تاريخ التسجيل')->date('Y/m/d')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_admin')->label('المدراء'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [OrdersRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}
