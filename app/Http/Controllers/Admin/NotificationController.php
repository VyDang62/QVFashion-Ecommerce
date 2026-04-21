<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
class NotificationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:notifications.view', only: ['index']),

            new Middleware('can:notifications.edit', only: ['markAsRead', 'markAllAsRead']),
        ];
    }
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10);
        $search = $request->input('search');
        $filter = $request->input('filter', 'all');

        $query = $request->user()->notifications()->latest();

        //Lọc theo trạng thái đọc
        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        if ($request->filled('search')) {
            $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            
            $query->where(function ($q) use ($escapedSearch) {
                $q->where('data->message', 'ilike', '%' . $escapedSearch . '%')
                  ->orWhere('data->label', 'ilike', '%' . $escapedSearch . '%');
            });
        }

        $notifications = $query->paginate($perPage)->withQueryString();
        
        return Inertia::render('admin/Notifications/Index', [
            'notifications' => $notifications,
            'filters' => [
                'search' => $search,
                'perPage' => (int) $perPage,
                'filter' => $filter
            ]
        ]);
    }

    public function markAsRead($id)
    {
        auth()->user()->notifications()->where('id', $id)->update(['read_at' => now()]);
        
        return back()->with('success', 'Đã đánh dấu thông báo là đã đọc.');
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        
        return back()->with('success', 'Đã đánh dấu tất cả thông báo là đã đọc.');
    }
}
