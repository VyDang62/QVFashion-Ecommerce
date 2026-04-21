<?php

namespace App\Http\Controllers\Admin;
use App\Helpers\Logger;
use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
class AttributeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:attributes.view', only: ['index', 'show']),

            new Middleware('can:attributes.create', only: ['create', 'store']),

            new Middleware('can:attributes.edit', only: ['edit', 'update']),

            new Middleware('can:attributes.delete', only: ['destroy']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10);
        $query = Attribute::query()->with('values');
        if ($request->filled('search')) {
            $query->where('attribute_name', 'ilike', '%' . $request->search . '%');
        }
        $attributes = $query->latest()->paginate($perPage)->withQueryString();
        return Inertia::render('admin/Attributes/Index', [
            'attributes' => $attributes,
            'filters' => $request->only(['search', 'perPage'])
        ]);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('admin/Attributes/Create');
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'attribute_name' => 'required|string|max:255|unique:attributes,attribute_name',
            'values'         => 'required|array|min:1',
            'values.*.value'    => 'required|string|max:255',
            'values.*.hex_code' => 'nullable|string|max:7',
        ]);
        try {
            DB::transaction(function () use ($request) {
                $attribute = Attribute::create([
                    'attribute_name' => $request->attribute_name
                ]);
                foreach ($request->values as $item) {
                    $attribute->values()->create([
                        'value'    => $item['value'],
                        'hex_code' => $item['hex_code'] ?? null,
                    ]);
                }
                Logger::log(
                    'Create Attribute',
                    $attribute,
                    "Đã tạo thuộc tính mới: {$attribute->attribute_name}",
                    ['values_count' => count($request->values)]
                );
            });
            return redirect()->route('admin.attributes.index')->with('success', 'Thuộc tính và các giá trị đã được thêm thành công!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi.');
        }
    }
    /**
     * Display the specified resource.
     */
    public function show(Attribute $attribute)
    {
        //
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Attribute $attribute)
    {
        $attribute->load('values');
        return Inertia::render('admin/Attributes/Edit', [
            'attribute' => $attribute,
        ]);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Attribute $attribute)
    {
        $request->validate([
            'attribute_name' => 'required|string|max:255|unique:attributes,attribute_name,' . $attribute->id,
            'values'         => 'required|array|min:1',
            'values.*.value'    => 'required|string|max:255',
            'values.*.hex_code' => 'nullable|string|max:7',
        ]);
        try {
            DB::transaction(function () use ($request, $attribute) {
                $oldName = $attribute->attribute_name;

                $attribute->update(['attribute_name' => $request->attribute_name]);
                $sentIds = collect($request->values)->pluck('id')->filter()->toArray();
                //Tổng số lượng thuộc tính bị xóa để lưu vào log
                $deletedCount = $attribute->values()->whereNotIn('id', $sentIds)->count();
                $attribute->values()->whereNotIn('id', $sentIds)->delete();
                foreach ($request->values as $valData) {
                    if (!empty($valData['id'])) {
                        AttributeValue::where('id', $valData['id'])->update([
                            'value'    => $valData['value'],
                            'hex_code' => $valData['hex_code'] ?? null,
                        ]);
                    } else {
                        $attribute->values()->create([
                            'value'    => $valData['value'],
                            'hex_code' => $valData['hex_code'] ?? null,
                        ]);
                    }
                }
                Logger::log(
                    'Update Attribute',
                    $attribute,
                    "Đã cập nhật thuộc tính: {$attribute->attribute_name}",
                    [
                        'old_name' => $oldName,
                        'new_name' => $attribute->attribute_name,
                        'deleted_values_count' => $deletedCount,
                        'total_values' => count($request->values)
                    ]
                );
            });
            return redirect()->route('admin.attributes.index')->with('success', 'Cập nhật thành công!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi: Có thể giá trị bạn định xóa đang được sử dụng bởi sản phẩm!');
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Attribute $attribute)
    {
        $isUsed = $attribute->values()->whereHas('productVariants')->exists();

        if ($isUsed) {
            return back()->with('error', "Không thể xóa thuộc tính '{$attribute->attribute_name}' vì đang có sản phẩm sử dụng các giá trị của nó!");
        }

        try {
            $id = $attribute->id;
            $attributeName = $attribute->attribute_name;
            return DB::transaction(function () use ($attribute, $id, $attributeName) {
                $attribute->delete();

                DB::table('activity_logs')->insert([
                    'user_id' => auth()->id(),
                    'action' => 'Delete Attribute',
                    'model_type' => Attribute::class,
                    'model_id' => $id,
                    'description' => "Đã xóa vĩnh viễn thuộc tính: {$attributeName}",
                    'ip_address' => request()->ip(),
                    'properties' => json_encode(['attribute_name' => $attributeName]),
                    'created_at' => now(),
                ]);

                return redirect()->route('admin.attributes.index')->with('success', 'Thuộc tính đã được xóa thành công!');
            });
        } catch (QueryException $e) {
            return back()->with('error', 'Lỗi hệ thống: ' . $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
}

