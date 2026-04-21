<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
class ActivityLogController extends Controller implements HasMiddleware
{
    
    public static function middleware()
    {
        return [
            new Middleware('can:activitylogs.view', only: ['index', 'show']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('perPage',10);
        $search = $request->input('search');
        $action = $request->input('action','');
        $query = ActivityLog::with('user');

        if ($request->filled('action')) {
            $query->where('action', 'ilike', '%' . $action . '%');
        }

        if($request->filled('search')){
            $query->where(function ($q) use ($search) {
                $q->where('description', 'ilike', '%' . $search . '%')
                  ->orWhereHas('user', function ($subQ) use ($search) {
                      $subQ->where('full_name', 'ilike', '%' . $search . '%');
                  });
            });
        }

        $activityLogs = $query->latest()->paginate($perPage)->withQueryString();

        return Inertia::render('admin/ActivityLogs/Index',[
            'activityLogs' => $activityLogs,
            'filters' => [
                'search' => $search,
                'perPage' => (int) $perPage,
                'action' => $action,
            ]
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
    public function show(ActivityLog $activityLog)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ActivityLog $activityLog)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ActivityLog $activityLog)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ActivityLog $activityLog)
    {
        //
    }
}
