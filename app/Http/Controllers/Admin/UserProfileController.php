<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UserProfileController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/UserProfile/UserProfile',[]);
    }

    public function updateInfo(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'full_name'    => 'required|string|max:255',
            'email'        => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone_number' => ['nullable','string','regex:/^(0)[0-9]{9}$/',],
        ], [
            'full_name.required' => 'Vui lòng nhập họ và tên!',
            'email.required'     => 'Email không được để trống!',
            'email.unique'       => 'Email này đã được sử dụng bởi một tài khoản khác!',
            'phone_number.regex'  => 'Số điện thoại không đúng định dạng!',
        ]);

        $user->fill($validated);

        $user->save();

        return back()->with('success', 'Thông tin cá nhân đã được cập nhật thành công!');
    }

    public function updateAddress(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'province'           => 'nullable|string',
            'province_id'           => 'nullable|integer',
            'district'       => 'nullable|string',
            'district_id'       => 'nullable|integer',
            'ward'           => 'nullable|string',
            'ward_code'           => 'nullable|string',
            'address_detail' => 'nullable|string|max:500',
        ]);

        $user->update($validated);

        return back()->with('success', 'Địa chỉ đã được cập nhật thành công!');
    }
}
