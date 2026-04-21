<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Logger;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
class PageController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:pages.view', only: ['index']),

            new Middleware('can:pages.create', only: ['create', 'store']),

            new Middleware('can:pages.edit', only: ['edit', 'update']),

            new Middleware('can:pages.delete', only: ['destroy', 'restore', 'forceDelete']),
        ];
    }
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10);
        $status = $request->input('status', 'active');
        $searchTerm = $request->input('search');

        $query = Page::query();
        if ($status === 'trash') {
            $query->onlyTrashed();
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'ilike', '%' . $searchTerm . '%')
                  ->orWhere('content', 'ilike', '%' . $searchTerm . '%');
            });
        }

        $pages = $query->latest()->paginate($perPage)->withQueryString();

        return Inertia::render('admin/Pages/Index', [
            'pages' => $pages,
            'filters' => [
                'search' => $searchTerm,
                'perPage' => (int) $perPage,
                'status' => $status,
            ]
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/Pages/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages,slug',
            'content' => 'required|string',
            'is_active' => 'required|boolean',
        ]);

        $page = Page::create([
            'title' => $request->title,
            'slug' => $request->slug ?: Str::slug($request->title),
            'content' => $request->input('content'),
            'is_active' => $request->is_active,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'meta_keywords' => $request->meta_keywords,
        ]);

        Logger::log(
            'Create Page',
            $page,
            "Đã tạo trang tĩnh mới: {$page->title}"
        );

        return redirect()->route('admin.pages.index')->with('success', 'Tạo trang mới thành công!');
    }

    public function edit($id)
    {
        $page = Page::withTrashed()->findOrFail($id);
        return Inertia::render('admin/Pages/Edit', [
            'page' => $page
        ]);
    }

    public function update(Request $request, $id)
    {
        $page = Page::withTrashed()->findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages,slug,' . $page->id,
            'content' => 'required|string',
            'is_active' => 'required|boolean',
        ]);

        $page->update($request->all());

        Logger::log(
            'Update Page',
            $page,
            "Đã cập nhật nội dung trang: {$page->title}",
            ['changes' => $request->only(['title', 'is_active', 'slug'])]
        );

        return redirect()->route('admin.pages.index')->with('success', 'Cập nhật trang thành công!');
    }


    public function destroy($id)
    {
        $page = Page::findOrFail($id);
        $page->delete();

        Logger::log('Delete Page', $page, "Đã chuyển trang '{$page->title}' vào thùng rác");

        return back()->with('success', 'Đã chuyển trang vào thùng rác!');
    }

    public function restore($id)
    {
        $page = Page::onlyTrashed()->findOrFail($id);
        $page->restore();

        Logger::log('Restore Page', $page, "Đã khôi phục trang: {$page->title}");

        return back()->with('success', 'Khôi phục trang thành công!');
    }

    public function forceDelete($id)
    {
        try {
            $page = Page::onlyTrashed()->findOrFail($id);
            $title = $page->title;

            DB::transaction(function () use ($page, $id, $title) {
                $page->forceDelete();

                DB::table('activity_logs')->insert([
                    'user_id' => auth()->id(),
                    'action' => 'Force Delete Page',
                    'model_type' => Page::class,
                    'model_id' => $id,
                    'description' => "Đã xóa vĩnh viễn trang: {$title}",
                    'ip_address' => request()->ip(),
                    'created_at' => now(),
                ]);
            });

            return back()->with('success', 'Đã xóa vĩnh viễn trang thành công!');

        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi hệ thống khi xóa vĩnh viễn trang: ' . $e->getMessage());
        }
    }
}
