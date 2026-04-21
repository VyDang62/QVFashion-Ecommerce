<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Logger;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
class SettingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:settings.view', only: ['index']),

            new Middleware('can:settings.edit', only: ['update']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $settingsGroups = Setting::all()->groupBy('group');

        return Inertia::render('admin/Settings/Index',[
            'settingsGroups' => $settingsGroups
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Setting $setting)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Setting $setting)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $settingsInput = $request->input('settings', []);
        $changes = [];

        try {
            DB::transaction(function () use ($request, $settingsInput, &$changes) {
                foreach ($settingsInput as $key => $value) {
                    $setting = Setting::where('key', $key)->first();
                    
                    if ($setting && $setting->type !== 'image') {
                        $oldValue = $setting->value;
                        
                        if ($oldValue != $value) {
                            Setting::set($key, $value, $setting->type, $setting->group);
                            Cache::forget("setting.{$key}");
                            
                            $changes[$key] = [
                                'old' => $oldValue,
                                'new' => $value
                            ];
                        }
                    }
                }

                if ($request->hasFile('settings.website_logo')) {
                    $setting = Setting::where('key', 'website_logo')->first();
                    $oldLogo = $setting ? $setting->value : null;

                    if ($oldLogo) {
                        Storage::disk('public')->delete($oldLogo);
                    }

                    $path = $request->file('settings.website_logo')->store('uploads/logos', 'public');
                    Setting::set('website_logo', $path, 'image', 'general');
                    Cache::forget("setting.website_logo");

                    $changes['website_logo'] = [
                        'old' => $oldLogo,
                        'new' => $path
                    ];
                }

                if (!empty($changes)) {
                    $changedKeys = implode(', ', array_keys($changes));
                    
                    Logger::log(
                        'Update System Settings',
                        null,
                        "Đã cập nhật các cấu hình: {$changedKeys}",
                        ['changes' => $changes]
                    );
                }
            });
            return back()->with('success', 'Cập nhật cấu hình thành công!');
        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra khi cập nhật: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Setting $setting)
    {
        //
    }
}
