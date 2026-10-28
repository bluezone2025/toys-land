<?php

namespace App\Filament\Resources\TrackingEvents\Pages;

use App\Filament\Resources\TrackingEvents\TrackingEventResource;
use App\Filament\Widgets\Tracking\PlatformHealth;
use App\Models\AnalyticsEvent;
use App\Models\Product;
use App\Services\TrackingService;
use App\Support\Tracking;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListTrackingEvents extends ListRecords
{
    protected static string $resource = TrackingEventResource::class;

    protected function getHeaderWidgets(): array
    {
        return [PlatformHealth::class];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('dispatch')->label('إرسال المعلّق الآن')->icon('heroicon-o-paper-airplane')->color('gray')
                ->action(function (TrackingService $service) {
                    $r = $service->dispatchPending();
                    Notification::make()->title("تمت معالجة {$r['events']} حدث: وصل {$r['sent']}، فشل {$r['failed']}")->success()->send();
                }),
            Action::make('test')->label('إرسال حدث تجريبي')->icon('heroicon-o-beaker')
                ->requiresConfirmation()
                ->modalDescription('يرسل حدث "مشاهدة منتج" تجريبي لكل منصة مربوطة ويعرض النتيجة. إذا عبّيت "كود الاختبار" لـ Meta و TikTok في الإعدادات، يظهر الحدث في صفحة Test Events بدون ما يأثر على إحصائيات إعلاناتك.')
                ->action(function (TrackingService $service) {
                    if (! Tracking::serverPlatforms()) {
                        Notification::make()->title('ما في منصة مربوطة. أضف المفاتيح من الإعدادات ← التتبع والبيكسلات')->warning()->send();

                        return;
                    }
                    $product = Product::active()->first();
                    $item = $product ? Tracking::item($product) : ['item_id' => 'test', 'item_name' => 'Test', 'price' => 1, 'quantity' => 1];
                    $payload = Tracking::track('view_item', ['value' => $item['price'], 'items' => [$item]], ['force' => true, 'profile' => false, 'product_id' => $product?->id, 'path' => '/admin/test']);
                    $event = AnalyticsEvent::where('event_id', $payload['event_id'])->firstOrFail();
                    $service->send(collect([$event]));
                    $delivery = $event->fresh()->delivery ?? [];
                    $ok = collect($delivery)->every(fn ($s) => $s === 'ok');
                    Notification::make()->title($ok ? 'وصل الحدث لكل المنصات ✅' : 'في منصات ما وصلها الحدث')
                        ->body(collect($delivery)->map(fn ($s, $p) => "{$p}: {$s}")->implode("\n"))
                        ->{$ok ? 'success' : 'danger'}()->persistent()->send();
                }),
        ];
    }
}
