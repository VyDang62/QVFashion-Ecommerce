<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Logger;
use App\Http\Controllers\Controller;
use App\Enums\BannerPosition;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
class BannerController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:banners.view', only: ['index']),

            new Middleware('can:banners.create', only: ['create', 'store']),

            new Middleware('can:banners.edit', only: ['edit', 'update']),

            new Middleware('can:banners.delete', only: ['destroy', 'restore', 'forceDelete']),
        ];
    }
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10);
        $status = $request->input('status', 'active');
        $search = $request->input('search');

        $query = ($status === 'trash') ? Banner::onlyTrashed() : Banner::query();

        if($request->filled('search')){
            $query->where('title','ilike','%'.$search.'%');
        }

        $banners = $query->orderBy('order','asc')->latest()->paginate($perPage)->withQueryString();
        return Inertia::render('admin/Banners/Index',[
            'banners' => $banners,
            'filters' => [
                'search' => $search,
                'perPage' => (int) $perPage,
                'status' => $status
            ]
        ]);
    }
    public function create()
    {
        return Inertia::render('admin/Banners/Create',[
            'positions' => BannerPosition::toSelectOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validate = $request->validate([
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'link_url' => 'nullable|url',
            'position' => ['required',new Enum(BannerPosition::class)],
            'order' => 'nullable|integer',
            'is_active' => 'boolean',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048'
        ],[
            'image.image' => 'Tệp tải lên phải là hình ảnh!',
            'position.required' => 'Vui lòng chọn vị trí ảnh hiển thị!'
        ]);
        
        if($request->hasFile('image')){
            $path = $request->file('image')->store('uploads/banners','public');
            $validate['image_path'] = $path;
        }
        
        $banner = Banner::create($validate);

        Logger::log(
            'Create Banner',
            $banner,
            "Đã thêm banner mới: " . ($banner->title ?? "ID: {$banner->id}"),
            [
                'position' => $banner->position,
                'image_path' => $banner->image_path
            ]
        );

        return redirect()->route('admin.banners.index')->with('success','Thêm banner mới thành công!');
    }

    public function edit(Banner $banner)
    {
        return Inertia::render('admin/Banners/Edit',[
            'banner' => $banner,
            'positions' => BannerPosition::toSelectOptions(),
        ]);
    }

    public function update(Request $request, Banner $banner)
    {
        $validate = $request->validate([
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'link_url' => 'nullable|url',
            'position' => ['required',new Enum(BannerPosition::class)],
            'order' => 'nullable|integer',
            'is_active' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048'
        ],[
            'image.image' => 'Tệp tải lên phải là hình ảnh!',
            'position.required' => 'Vui lòng chọn vị trí ảnh hiển thị!'
        ]);

        $oldData = $banner->only(['title', 'position', 'is_active', 'order']);

        if($request->hasFile('image')){
            if($banner->image_path && Storage::disk('public')->exists($banner->image_path)){
                Storage::disk('public')->delete($banner->image_path);
            }

            $path = $request->file('image')->store('uploads/banners','public');
            $validate['image_path'] = $path;
        }

        $banner->update($validate);

        Logger::log(
            'Update Banner',
            $banner,
            "Đã cập nhật banner: " . ($banner->title ?? "ID: {$banner->id}"),
            [
                'old' => $oldData,
                'new' => $banner->only(['title', 'position', 'is_active', 'order'])
            ]
        );
        return redirect()->route('admin.banners.index')->with('success','Cập nhật banner thành công!');
    }
    public function destroy(Banner $banner)
    {
        $bannerTitle = $banner->title ?? "ID: {$banner->id}";
        $banner->delete();

        Logger::log(
            'Soft Delete Banner',
            $banner,
            "Đã tạm xóa banner: {$bannerTitle}"
        );
        return back()->with('success', 'Banner đã được tạm xóa!');
    }

    public function restore($id)
    {
        $banner = Banner::withTrashed()->findOrFail($id);
        $banner->restore();

        Logger::log(
            'Restore Banner',
            $banner,
            "Đã khôi phục banner: " . ($banner->title ?? "ID: {$banner->id}")
        );
        return back()->with('success', "Đã khôi phục banner!");
    }

    public function forceDelete($id)
    {
        try {
            $banner = Banner::withTrashed()->findOrFail($id);
            $bannerTitle = $banner->title ?? "ID: {$id}";
            $imagePath = $banner->image_path;

            DB::transaction(function () use ($banner, $id, $bannerTitle, $imagePath) {
                if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                    Storage::disk('public')->delete($imagePath);
                }

                $banner->forceDelete();

                DB::table('activity_logs')->insert([
                    'user_id' => auth()->id(),
                    'action' => 'Force Delete Banner',
                    'model_type' => Banner::class,
                    'model_id' => $id,
                    'description' => "Đã xóa vĩnh viễn banner: {$bannerTitle}",
                    'ip_address' => request()->ip(),
                    'properties' => json_encode([
                        'title' => $bannerTitle,
                        'image_path' => $imagePath
                    ]),
                    'created_at' => now(),
                ]);
            });

            return back()->with('success', 'Đã xóa vĩnh viễn banner và tệp tin liên quan!');

        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi khi xóa vĩnh viễn banner: ' . $e->getMessage());
        }
    }
}
