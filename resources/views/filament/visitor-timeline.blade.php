{{-- Journey timeline for VisitorProfileResource (Filament admin; plain inline styles so no theme build is needed). --}}
@php
    use App\Support\TrackingReports;
    $record = $getRecord();
    $rows = $record->timeline(300);
    $platforms = ['meta' => 'Meta', 'tiktok' => 'TikTok', 'snap' => 'Snap', 'ga4' => 'GA4'];
@endphp
@if ($rows->isEmpty())
    <p style="opacity:.6;font-size:.875rem">ما في نشاط مسجّل لهذا الزائر.</p>
@else
    <ol style="list-style:none;margin:0;padding:0;border-inline-start:2px solid rgba(148,163,184,.35)">
        @foreach ($rows as $row)
            @php
                $important = in_array($row->type, ['add_to_cart', 'begin_checkout', 'checkout_progress', 'add_payment_info', 'purchase'], true);
                $dayChanged = $loop->first || $rows[$loop->index - 1]->at->toDateString() !== $row->at->toDateString();
                $detail = $row->meta['search_term'] ?? $row->meta['item_list_name'] ?? $row->meta['payment_type'] ?? $row->meta['shipping_tier'] ?? $row->meta['transaction_id'] ?? null;
            @endphp
            @if ($dayChanged)
                <li style="margin:.75rem 0 .25rem;padding-inline-start:1rem;font-size:.75rem;font-weight:700;opacity:.6">{{ $row->at->timezone('Asia/Kuwait')->translatedFormat('l j F Y') }}</li>
            @endif
            <li style="position:relative;padding:.4rem 0 .4rem 1rem;padding-inline-start:1rem;display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;font-size:.875rem;{{ $important ? 'font-weight:700' : '' }}">
                <span style="position:absolute;inset-inline-start:-6px;top:.85rem;width:10px;height:10px;border-radius:9999px;background:{{ $row->type === 'purchase' ? '#10b981' : ($important ? '#f59e0b' : '#94a3b8') }}"></span>
                <span style="min-width:4.5rem;font-variant-numeric:tabular-nums;opacity:.6;font-size:.75rem">{{ $row->at->timezone('Asia/Kuwait')->format('h:i:s A') }}</span>
                <span>{{ TrackingReports::label($row->type) }}</span>
                @if ($row->product)
                    <a href="{{ route('products.show', $row->product->slug) }}" target="_blank" style="color:#0ea5e9">{{ $row->product->name_ar }}</a>
                @elseif ($detail)
                    <span style="color:#0ea5e9">{{ $detail }}</span>
                @endif
                @if ($row->value)
                    <x-filament::badge color="success" size="sm">{{ TrackingReports::money($row->value) }}</x-filament::badge>
                @endif
                @if ($row->type === 'page_view')
                    <span style="opacity:.6;font-size:.75rem;direction:ltr">{{ \Illuminate\Support\Str::limit($row->path, 60) }}</span>
                    @if ($row->source)
                        <x-filament::badge color="info" size="sm">{{ $row->source }}</x-filament::badge>
                    @endif
                @endif
                @foreach ($row->delivery ?? [] as $platform => $status)
                    <x-filament::badge :color="$status === 'ok' ? 'gray' : 'danger'" size="sm" :tooltip="$status">{{ $platforms[$platform] ?? $platform }} {{ $status === 'ok' ? '✓' : '✗' }}</x-filament::badge>
                @endforeach
            </li>
        @endforeach
    </ol>
@endif
