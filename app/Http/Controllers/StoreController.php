<?php

namespace App\Http\Controllers;

use App\Models\AgeGroup;
use App\Models\AnalyticsEvent;
use App\Models\Category;
use App\Models\HeroSlide;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function home()
    {
        $base = fn () => Product::active()->with('ageGroups')->sorted();
        $groupIds = fn (string $group) => Category::active()->where('home_group', $group)->pluck('id');

        $eduIds = $groupIds('edu');
        $funIds = $groupIds('fun');
        $firstSlug = fn ($ids) => Category::whereIn('id', $ids)->orderBy('sort')->value('slug');

        return view('store.home', [
            'slides' => HeroSlide::active()->get(),
            'ageGroups' => AgeGroup::active()->get(),
            'bestSellers' => $base()->where('is_best_seller', true)->take(12)->get()
                ->whenEmpty(fn () => $base()->orderByDesc('sales_count')->take(12)->get()),
            'eduProducts' => $base()->whereHas('categories', fn (Builder $q) => $q->whereIn('categories.id', $eduIds))->take(12)->get(),
            'funProducts' => $base()->whereHas('categories', fn (Builder $q) => $q->whereIn('categories.id', $funIds))->take(12)->get(),
            'eduLink' => route('products.index', array_filter(['category' => $firstSlug($eduIds)])),
            'funLink' => route('products.index', array_filter(['category' => $firstSlug($funIds)])),
            'catalog' => $base()->take(20)->get(),
        ]);
    }

    public function index(Request $request)
    {
        $selected = (string) $request->query('category', 'all');
        $age = $request->query('age');
        $search = trim((string) $request->query('search'));
        $sort = (string) $request->query('sort', 'default');

        $query = Product::active()->with('ageGroups');
        $title = __('store.allToys');

        if ($search !== '') {
            $query->where(fn (Builder $q) => $q->where('name_ar', 'like', "%{$search}%")
                ->orWhere('name_en', 'like', "%{$search}%")
                ->orWhere('sku', $search));
            $title = tt('نتائج البحث عن: ', 'Search results for: ').$search;
            if (! $request->has('page')) {
                AnalyticsEvent::record('search', ['meta' => ['q' => mb_substr($search, 0, 100)]]);
            }
        }
        if ($age) {
            $query->whereHas('ageGroups', fn (Builder $q) => $q->where('key', $age));
            $title = tt('ألعاب مناسبة لعمر (', 'Toys for Age (').$age.')';
        }
        if ($selected !== 'all' && ($category = Category::where('slug', $selected)->first())) {
            $query->whereHas('categories', fn (Builder $q) => $q->where('categories.id', $category->id));
            $title = $search === '' && ! $age ? $category->tr('title') : $title;
        }
        $title = $title === __('store.allToys') ? tt('كل ألعاب اليهال', 'All Kids Toys') : $title;

        $this->applySort($query, $sort);

        return view('store.products', [
            'products' => $query->paginate(20)->withQueryString(),
            'pageTitle' => $title,
            'selected' => $selected,
            'age' => $age,
            'search' => $search,
            'sort' => $sort,
        ]);
    }

    public function category(Request $request, Category $category)
    {
        abort_unless($category->is_active, 404);

        $age = $request->query('age');
        $sort = (string) $request->query('sort', 'default');
        $query = $category->products()->active()->with('ageGroups');
        if ($age) {
            $query->whereHas('ageGroups', fn (Builder $q) => $q->where('key', $age));
        }
        $this->applySort($query, $sort);

        return view('store.category', [
            'category' => $category,
            'products' => $query->paginate(20)->withQueryString(),
            'ageGroups' => AgeGroup::active()->get(),
            'age' => $age,
            'sort' => $sort,
        ]);
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);
        $product->increment('views_count');

        $product->load(['ageGroups', 'categories', 'options' => fn ($q) => $q->where('is_active', true)]);
        $categoryIds = $product->categories->pluck('id');

        // Same-category products first, then the rest of the catalog.
        $related = Product::active()->with('ageGroups')->whereKeyNot($product->id)
            ->whereHas('categories', fn (Builder $q) => $q->whereIn('categories.id', $categoryIds))->sorted()->take(12)->get();
        if ($related->count() < 12) {
            $related = $related->concat(Product::active()->with('ageGroups')->whereKeyNot($product->id)
                ->whereNotIn('id', $related->pluck('id'))->sorted()->take(12 - $related->count())->get());
        }

        return view('store.product', [
            'product' => $product,
            'editions' => $product->options->where('type', 'edition')->values(),
            'addons' => $product->options->where('type', 'addon')->values(),
            'related' => $related,
        ]);
    }

    protected function applySort($query, string $sort): void
    {
        match ($sort) {
            'price-low' => $query->orderBy('price'),
            'price-high' => $query->orderByDesc('price'),
            'newest' => $query->latest('id'),
            default => $query->orderBy('sort')->orderBy('id'),
        };
    }
}
