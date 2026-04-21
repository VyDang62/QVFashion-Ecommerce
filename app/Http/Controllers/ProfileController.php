<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'full_name'      => ['required', 'string', 'max:255'],
            'phone_number'   => ['nullable', 'string', 'max:20'],
            'province'           => ['nullable', 'string'],
            'province_id'           => ['nullable', 'integer'],
            'district'       => ['nullable', 'string'],
            'district_id'       => ['nullable', 'integer'],
            'ward'           => ['nullable', 'string'],
            'ward_code'           => ['nullable', 'string'],
            'address_detail' => ['nullable', 'string', 'max:500'],
        ], [
            'full_name.required' => 'Vui lòng không để trống họ tên của bạn.',
        ]);
        $user->fill($validated);

        $user->save();
        
        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
