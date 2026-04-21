<?php

namespace App\Http\Controllers\Admin;
use App\Helpers\Logger;
use App\Models\ProductType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Enums\Gender;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
class CategoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:categories.view', only: ['index', 'show']),

            new Middleware('can:categories.create', only: ['create', 'store']),

            new Middleware('can:categories.edit', only: ['edit', 'update']),

            new Middleware('can:categories.delete', only: ['destroy', 'restore', 'forceDelete']),
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
        $query = Category::with(['parent', 'productType']);

        if ($status === 'trash') {
            $query->onlyTrashed();
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('category_name', 'ilike', '%' . $searchTerm . '%');
            });
        }

        $categories = $query->orderByRaw('COALESCE(parent_id,id),parent_id IS NOT NULL')->paginate($perPage)->withQueryString();

        return Inertia::render('admin/Categories/Index',[
            'categories' => $categories,
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
        return Inertia::render('admin/Categories/Create',[
            'productTypes' => ProductType::all(),
            'genders' => Gender::toSelectOptions(),
            'parentCategories' => Category::onlyParents()->get(['id','category_name'])
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validate = $request->validate([
            'category_name'      => 'required|string|max:255',
            'parent_id'          => 'nullable|exists:categories,id',
            'gender'          => ['required', new Enum(Gender::class)],
            'product_type_id'    => 'required|exists:product_types,id',
        ]);

        $category = Category::create($validate);
        Cache::forget('menu_categories');

        Logger::log(
            'Create Category',
            $category,
            "Đã thêm danh mục mới: {$category->category_name}",
            [
                'parent_id' => $category->parent_id,
                'gender' => $category->gender,
                'product_type_id' => $category->product_type_id
            ]
        );

        return redirect()->route('admin.categories.index')->with('success', 'Thêm danh mục thành công!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        $category->load('parent','productType');
        $hasChildren = $category->children()->exists();

        //Chưa có danh mục con thì trả về danh sách danh mục cha, ngược lại trả về danh sách rỗng
        $parentCategories = $hasChildren 
        ? [] 
        : Category::onlyParents()
            ->where('id', '!=', $category->id)
            ->get(['id', 'category_name']);

        return Inertia::render('admin/Categories/Edit',[
            'category' => $category,
            'genders' => Gender::toSelectOptions(),
            'productTypes' => ProductType::all(),
            'parentCategories' => $parentCategories,
            'hasChildren' => $hasChildren
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        if ($category->children()->exists() && $request->filled('parent_id')) {
            return redirect()->back()->withErrors(['parent_id' => 'Không thể chuyển đổi cấp bậc khi đã có danh mục con!']);
        }
        $validate = $request->validate([
            'category_name'      => 'required|string|max:255',
            'parent_id'       => [
                'nullable',
                'exists:categories,id',
                'not_in:' . $category->id,
            ],
            'gender'          => ['required', new Enum(Gender::class)],
            'product_type_id'    => 'required|exists:product_types,id',
        ]);

        $oldData = $category->only('category_name', 'parent_id');
        $category->update($validate);
        Cache::forget('menu_categories');

        Logger::log(
            'Update Category',
            $category,
            "Đã cập nhật danh mục: {$category->category_name}",
            [
                'old' => $oldData,
                'new' => $category->only('category_name', 'parent_id')
            ]
        );
        return redirect()->route('admin.categories.index')->with('success', 'Cập nhật giới tính thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        try {
            if ($category->children()->exists()) {
                return back()->with('error', 'Không thể xóa! Danh mục này đang chứa các danh mục con!');
            }

            if ($category->products()->withTrashed()->exists()) {
                return back()->with('error', 'Không thể xóa! Đang có sản phẩm thuộc danh mục này!');
            }

            $categoryName = $category->category_name;
            $category->delete();
            Cache::forget('menu_categories');

            Logger::log(
                'Soft Delete Category',
                $category,
                "Đã tạm xóa danh mục: {$categoryName}"
            );

            return redirect()->route('admin.categories.index')->with('success', 'Danh mục đã được tạm xóa!');

        } catch (QueryException $e) {
            return back()->with('error', 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    public function restore($id)
    {
        $category = Category::withTrashed()->findOrFail($id);
        
        $category->restore();
        Cache::forget('menu_categories');

        Logger::log(
            'Restore Category',
            $category,
            "Đã khôi phục danh mục: {$category->category_name}"
        );
        return back()->with('success', "Đã khôi phục danh mục {$category->category_name} thành công!");
    }

    public function forceDelete($id)
    {
        try {
            $category = Category::withTrashed()->findOrFail($id);

            if ($category->children()->exists()) {
                return back()->with('error', 'Không thể xóa vĩnh viễn! Danh mục này đang chứa các danh mục con!');
            }

            if ($category->products()->withTrashed()->exists()) {
                return back()->with('error', 'Không thể xóa vĩnh viễn! Đang có sản phẩm thuộc danh mục này!');
            }

            $categoryName = $category->category_name;

            DB::transaction(function () use ($category, $id, $categoryName) {
                $category->forceDelete();

                Cache::forget('menu_categories');

                DB::table('activity_logs')->insert([
                    'user_id' => auth()->id(),
                    'action' => 'Force Delete Category',
                    'model_type' => Category::class,
                    'model_id' => $id,
                    'description' => "Đã xóa vĩnh viễn danh mục: {$categoryName}",
                    'properties' => json_encode(['category_name' => $categoryName]),
                    'ip_address' => request()->ip(),
                    'created_at' => now(),
                ]);
            });

            return back()->with('success', 'Đã xóa vĩnh viễn danh mục và cập nhật hệ thống!');

        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi khi xóa vĩnh viễn: ' . $e->getMessage());
        }
    }
}
