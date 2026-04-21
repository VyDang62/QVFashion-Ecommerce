<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Logger;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:users.view', only: ['index', 'show']),
            
            new Middleware('can:users.create', only: ['create', 'store']),
            
            new Middleware('can:users.edit', only: ['edit', 'update', 'updateInfo', 'updatePassword', 'toggleStatus']),
            
            new Middleware('can:users.delete', only: ['destroy', 'restore', 'forceDelete']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('perPage',10);
        $status = $request->input('status','active');
        $searchTerm = $request->input('search');
        $role = $request->input('role');
        $query = User::with('roles');

        if ($status === 'trash') {
            $query->onlyTrashed();
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('full_name', 'ilike', '%' . $searchTerm . '%')
                ->orWhere('email', 'ilike', '%' . $searchTerm . '%');
            });
        }
        
        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        $users = $query->latest()->paginate($perPage)->withQueryString();
        return Inertia::render('admin/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $searchTerm,
                'perPage' => (int) $perPage,
                'status' => $status,
                'role' => $role,
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('admin/Users/Create', [
            'roles' => Role::all()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'full_name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role_ids'    => 'array'
        ]);

        $user = User::create([
            'full_name'     => $request->full_name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);
        $roles = $request->roles ?: ['customer'];
        $user->syncRoles($roles);

        Logger::log(
            'Create User',
            $user,
            "Đã tạo tài khoản mới: {$user->full_name} ({$user->email})",
            ['roles' => $roles]
        );

        return redirect()->route('admin.users.index')->with('success', 'Tạo người dùng thành công.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return Inertia::render('admin/Users/Show',[
            'user' => $user->load(['roles','orders', 'voucherUsages','ratings','wishlists']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        return Inertia::render('admin/Users/Edit', [
            'user' => $user->load('roles'),
            'roles' => Role::all(),
            'userRoles' => $user->roles->pluck('name')
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'roles' => 'nullable|array'
        ]);

        $user->update([
            'name'  => $request->name,
            'email' => $request->email,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }
        
        $user->syncRoles($request->roles);

        return redirect()->route('admin.users.index')->with('success', 'Cập nhật mật khẩu thành công.');
    }

    public function updateInfo(Request $request, User $user) {
        $request->validate([
            'full_name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'roles' => 'nullable|array'
        ]);
        $oldData = $user->only('full_name', 'email');
        $user->update($request->only('full_name', 'email'));
        
        $roles = $request->roles ?: ['customer'];
        $user->syncRoles($roles);

        Logger::log(
            'Update User Info',
            $user,
            "Đã cập nhật thông tin cho người dùng: {$user->full_name}",
            [
                'old' => $oldData,
                'new' => $user->only('full_name', 'email'),
                'roles' => $roles
            ]
        );

        return back()->with('success', 'Cập nhật thông tin thành công!');
    }

    public function updatePassword(Request $request, User $user) {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        Logger::log(
            'Change User Password',
            $user,
            "Đã thay đổi mật khẩu quản trị cho người dùng: {$user->full_name}"
        );

        return back()->with('success', 'Đổi mật khẩu thành công!');
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Bạn không thể tự xóa chính mình!');
        }
        $user->delete();

        Logger::log(
            'Soft Delete User',
            $user,
            "Đã chuyển người dùng {$user->full_name} vào thùng rác"
        );

        return redirect()->route('admin.users.index')->with('success', "Người dùng {$user->name} đã được tạm xóa!");
    }

    public function toggleStatus(User $user)
    {
        if($user->id == Auth::id()){
            return back()->with('error','Bạn không thể thay đổi trang thái tài khoản của mình!');
        }

        $user->update([
            'is_active' => !$user->is_active
        ]);

        $status = $user->is_active ? 'Mở khóa' : 'Khóa';

        Logger::log(
            'Toggle User Status',
            $user,
            "Đã {$status} tài khoản của người dùng: {$user->full_name}",
            ['is_active' => $user->is_active]
        );

        return back()->with('success',"Đã {$status} tài khoản {$user->full_name} thành công!");
    }

    public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        
        $user->restore();

        Logger::log(
            'Restore User',
            $user,
            "Đã khôi phục tài khoản cho người dùng: {$user->full_name}"
        );

        return back()->with('success', "Đã khôi phục tài khoản của {$user->name} thành công!");
    }

    public function forceDelete($id)
    {
        $user = User::withTrashed()->withCount('orders')->findOrFail($id);
        if ($user->orders_count > 0) {
            return back()->with('error', "Không thể xóa vĩnh viễn! Người dùng này đã thực hiện {$user->orders_count} giao dịch!");
        }
        try{
            $userName = $user->full_name;
            $userEmail = $user->email;
            DB::transaction(function () use ($user, $id, $userName, $userEmail){
                $user->forceDelete();

                DB::table('activity_logs')->insert([
                    'user_id' => auth()->id(),
                    'action' => 'Force Delete User',
                    'model_type' => User::class,
                    'model_id' => $id,
                    'description' => "Đã xóa vĩnh viễn tài khoản người dùng: {$userName} ({$userEmail})",
                    'ip_address' => request()->ip(),
                    'created_at' => now(),
                ]);
            });
            return back()->with('success', "Đã xóa vĩnh viễn tài khoản khỏi hệ thống!");
        } catch (\Exception $e) {
            return back()->with('error', "Lỗi hệ thống khi xóa vĩnh viễn: " . $e->getMessage());
        }
    }
}
