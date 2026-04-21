<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Logger;
use App\Http\Controllers\Controller;
use DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Database\QueryException;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
class RoleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:roles.view', only: ['index']),

            new Middleware('can:roles.create', only: ['create', 'store']),

            new Middleware('can:roles.edit', only: ['edit', 'update']),

            new Middleware('can:roles.delete', only: ['destroy']),
        ];
    }
    public function index(Request $request)
    {   
        $perPage = $request->input('perPage',10);
        $query = Role::with('permissions');

        if ($request->filled('search')){
            $query->where('name','ilike','%'.$request->search.'%');
        }

        $roles = $query->latest()->paginate($perPage)->withQueryString();

        return Inertia::render('admin/Roles/Index',[
            'roles' => $roles,
            'filters' => $request->only(['search','perPage'])
        ]);
    }

    public function create()
    {
        $permissions = Permission::all()->groupBy(function($perm) {
            return explode('.', $perm->name)[0]; 
        });

        return Inertia::render('admin/Roles/Create', [
            'permissionsGrouped' => $permissions
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'array'
        ]);
        $role = Role::create(['name' => $request->name]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->route('admin.roles.index')->with('success', 'Thêm vai trò thành công');
    }

    public function edit(Role $role)
    {
        return Inertia::render('admin/Roles/Edit', [
            'role' => $role,
            'permissionsGrouped' => Permission::all()->groupBy(fn($p) => explode('.', $p->name)[0]),
            'rolePermissions' => $role->permissions->pluck('name')
        ]);
    }

    public function update(Request $request, Role $role)
    {   
        $request->validate([
            'name' => 'required|unique:roles,name,' . $role->id,
            'permissions' => 'array'
        ]);
        $oldPermissions = $role->permissions->pluck('name')->toArray();
        $role->syncPermissions($request->permissions);

        Logger::log(
            'Update Role',
            $role,
            "Đã cập nhật vai trò: {$role->name}",
            [
                'old_permissions' => $oldPermissions,
                'new_permissions' => $request->permissions
            ]
        );

        return redirect()->route('admin.roles.index')->with('success', 'Cập nhật thành công!');
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'super-admin' || $role->name === 'sale' || $role->name === 'warehouse-manager'|| $role->name === 'customer') {
            return back()->with('error', 'Không thể xóa các vai trò Quản trị viên, Nhân viên bán hàng, Quản lý kho, Khách hàng của hệ thống!');
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', "Không thể xóa! Hiện đang có {$role->users()->count()} nhân viên đảm nhiệm vai trò này.");
        }

        try {
            $roleId = $role->id;
            $roleName = $role->name;
            $roleData = $role->load('permissions')->toArray();
            $role->delete();

            DB::table('activity_logs')->insert([
                'user_id' => auth()->id(),
                'action' => 'Delete Role',
                'model_type' => Role::class,
                'model_id' => $roleId,
                'description' => "Đã xóa vĩnh viễn vai trò: {$roleName}",
                'properties' => json_encode($roleData),
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ]);
            return redirect()->route('admin.roles.index')->with('success', 'Đã xóa vai trò thành công!');
            
        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra khi xóa vai trò: ' . $e->getMessage());
        }
    }
}
