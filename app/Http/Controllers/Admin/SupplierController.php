<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Logger;
use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Database\QueryException;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
class SupplierController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:suppliers.view', only: ['index', 'show']),

            new Middleware('can:suppliers.create', only: ['create', 'store']),

            new Middleware('can:suppliers.edit', only: ['edit', 'update']),

            new Middleware('can:suppliers.delete', only: ['destroy']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10);
        $status = $request->input('status', 'active');
        $searchTerm = $request->input('search');

        $query = Supplier::query();
        if ($status === 'trash') {
            $query->onlyTrashed();
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('supplier_name', 'ilike', '%' . $searchTerm . '%')
                ->orWhere('phone', 'ilike', '%' . $searchTerm . '%')
                ->orWhere('supplier_address', 'ilike', '%' . $searchTerm . '%');
            });
        }

        $suppliers = $query->latest()->paginate($perPage)->withQueryString();

        return Inertia::render('admin/Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => [
                'search' => $searchTerm,
                'perPage' => (int) $perPage,
                'status' => $status, 
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('admin/Suppliers/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validate = $request->validate([
            'supplier_name'      => 'required|string|max:255',
            'phone'      =>  'required|numeric',
            'supplier_address' => 'required|string|max:255',
        ]);
        $supplier = Supplier::create($validate);
        Logger::log(
            'Create Supplier',
            $supplier,
            "Đã thêm nhà cung cấp mới: {$supplier->supplier_name}",
            $validate
        );
        return redirect()->route('admin.suppliers.index')->with('success','Nhà cung cấp đã được thêm thành công!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier)
    {
        return Inertia::render('admin/Suppliers/Edit', [
            'supplier' => $supplier,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validate = $request->validate([
            'supplier_name'      => 'required|string|max:255',
            'phone'      =>  'required|numeric',
            'supplier_address' => 'required|string|max:255',
        ]);
        $oldData = $supplier->only(['supplier_name', 'phone', 'supplier_address']);
        $supplier->update($validate);

        Logger::log(
            'Update Supplier',
            $supplier,
            "Đã cập nhật thông tin nhà cung cấp: {$supplier->supplier_name}",
            [
                'old' => $oldData,
                'new' => $validate
            ]
        );
        return redirect()->route('admin.suppliers.index')->with('success', 'Cập nhật nhà cung cấp thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        try {
            $hasPendingReceipts = $supplier->goodsReceipts()
                ->where('receipt_status', 'pending')
                ->exists();

            if ($hasPendingReceipts) {
                return back()->with('error', 'Không thể xóa nhà cung cấp này vì đang có phiếu nhập hàng chờ xử lý!');
            }

            $supplierName = $supplier->supplier_name;

            $supplier->delete();

            Logger::log(
                'Soft Delete Supplier',
                $supplier,
                "Đã tạm xóa nhà cung cấp: {$supplierName}"
            );

            return redirect()->route('admin.suppliers.index')->with('success', 'Nhà cung cấp đã được tạm xóa!');

        } catch (QueryException $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function restore($id)
    {
        try {
            $supplier = Supplier::withTrashed()->findOrFail($id);
            
            $supplier->restore();

            Logger::log(
                'Restore Supplier',
                $supplier,
                "Đã khôi phục nhà cung cấp: {$supplier->supplier_name}"
            );

            return back()->with('success', "Đã khôi phục nhà cung cấp {$supplier->supplier_name} thành công!");

        } catch (\Exception $e) {
            return back()->with('error', 'Không thể khôi phục: ' . $e->getMessage());
        }
    }

    public function forceDelete($id)
    {
        try {
            $supplier = Supplier::withTrashed()->withCount('goodsReceipts')->findOrFail($id);

            if ($supplier->goods_receipts_count > 0) {
                return back()->with('error', "Không thể xóa vĩnh viễn! Nhà cung cấp này đã có {$supplier->goods_receipts_count} phiếu nhập hàng trong lịch sử.");
            }

            $supplierName = $supplier->supplier_name;

            DB::transaction(function () use ($supplier, $id, $supplierName) {
                $supplier->forceDelete();

                DB::table('activity_logs')->insert([
                    'user_id' => auth()->id(),
                    'action' => 'Force Delete Supplier',
                    'model_type' => Supplier::class,
                    'model_id' => $id,
                    'description' => "Đã xóa vĩnh viễn nhà cung cấp: {$supplierName}",
                    'properties' => json_encode(['supplier_name' => $supplierName]),
                    'ip_address' => request()->ip(),
                    'created_at' => now(),
                ]);
            });

            return back()->with('success', "Đã xóa vĩnh viễn nhà cung cấp khỏi hệ thống!");

        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi hệ thống khi xóa vĩnh viễn: ' . $e->getMessage());
        }
    }
}
