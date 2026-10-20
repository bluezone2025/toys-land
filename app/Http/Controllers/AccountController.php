<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Product;
use App\Support\Favorites;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->route('tab') ?? $request->query('tab');
        $tab = in_array($tab, ['orders', 'favorites', 'addresses', 'account']) ? $tab : 'orders';

        // Guests may still browse their session wishlist.
        if (! auth()->check()) {
            if ($tab !== 'favorites') {
                return redirect()->guest(route('login'));
            }
        }

        $user = auth()->user();

        if (! $user) {
            return view('account.guest-favorites', [
                'favorites' => Product::active()->with('ageGroups')->whereIn('id', Favorites::ids())->get(),
            ]);
        }

        return view('account.index', [
            'tab' => $tab,
            'user' => $user,
            'orders' => $user->orders()->withSum('items', 'qty')->latest('id')->get(),
            'favorites' => $user->favoriteProducts()->active()->with('ageGroups')->get(),
            'addresses' => $user->addresses()->orderByDesc('is_default')->latest('id')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validateWithBag('profile', [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);
        $user->update($data);

        return redirect()->route('account.tab', 'account')->with('toast', tt('تم تحديث بياناتك الشخصية بنجاح ✅', 'Profile updated successfully ✅'));
    }

    public function password(Request $request)
    {
        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);
        $request->user()->update(['password' => $data['password']]);

        return redirect()->route('account.tab', 'account')->with('toast', tt('تم تغيير كلمة المرور 🔒', 'Password updated 🔒'));
    }

    public function storeAddress(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'area' => ['required', 'string', 'max:100'],
            'details' => ['required', 'string', 'max:255'],
        ]);
        $user = $request->user();
        $user->addresses()->create($data + ['is_default' => ! $user->addresses()->exists()]);

        return redirect()->route('account.tab', 'addresses')->with('toast', tt('تم إضافة العنوان بنجاح 📍', 'Address added 📍'));
    }

    public function defaultAddress(Request $request, Address $address)
    {
        abort_unless($address->user_id === $request->user()->id, 403);
        $request->user()->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return redirect()->route('account.tab', 'addresses');
    }

    public function destroyAddress(Request $request, Address $address)
    {
        abort_unless($address->user_id === $request->user()->id, 403);
        $address->delete();

        return redirect()->route('account.tab', 'addresses');
    }
}
