<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Logger;
use App\Http\Controllers\Controller;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
class RatingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:ratings.view', only: ['index']),

            new Middleware('can:ratings.approve', only: ['toggleApproval']),

            new Middleware('can:ratings.delete', only: ['destroy']),
        ];
    }
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10);
        $status = $request->input('status');
        $score = $request->input('score');
        $search = $request->input('search');

        $query = Rating::with([
            'user:id,full_name', 
            'product' => function ($q) {
                $q->withTrashed()->select('id', 'product_name', 'slug', 'deleted_at');
        }]);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('content', 'ilike', '%' . $search . '%')
                ->orWhereHas('user', function ($sub) use ($search) {
                    $sub->where('full_name', 'ilike', '%' . $search . '%');
                })
                ->orWhereHas('product', function ($sub) use ($search) {
                    $sub->where('product_name', 'ilike', '%' . $search . '%');
                });
            });
        }

        if ($request->filled('status')) {
            $query->where('is_approved', $status);
        }

        if ($request->filled('score')) {
            $query->where('score', $score);
        }

        $ratings = $query->latest()->paginate($perPage)->withQueryString();

        return Inertia::render('admin/Ratings/Index', [
            'ratings' => $ratings,
            'filters' => [
                'search' => $search,
                'perPage' => (int) $perPage,
                'status' => $status,
                'score' => $score
            ]
        ]);
    }

    public function toggleApproval($id)
    {
        $rating = Rating::findOrFail($id);

        $rating->is_approved = !$rating->is_approved;
        $rating->save();

        $statusText = $rating->is_approved ? 'Phê duyệt' : 'Bỏ phê duyệt';

        Logger::log(
            'Toggle Rating Approval',
            $rating,
            "Đã {$statusText} đánh giá của khách hàng: {$rating->user->full_name} cho sản phẩm: {$rating->product->product_name}",
            [
                'is_approved' => $rating->is_approved,
                'score' => $rating->score,
                'content' => $rating->content
            ]
        );
        return back()->with('success', "Đã {$statusText} đánh giá của khách hàng thành công!");
    }

    public function destroy(Rating $rating)
    {
        try {
            $ratingId = $rating->id;
            $userName = $rating->user->full_name ?? 'Ẩn danh';

            $product = $rating->product()->withTrashed()->first();
            $productName = $product ? $product->product_name : 'bị xóa vĩnh viễn';

            $content = $rating->content;
            $score = $rating->score;

            DB::transaction(function () use ($rating, $ratingId, $userName, $productName, $content, $score) {
                $rating->delete();

                DB::table('activity_logs')->insert([
                    'user_id' => auth()->id(),
                    'action' => 'Delete Rating',
                    'model_type' => Rating::class,
                    'model_id' => $ratingId,
                    'description' => "Đã xóa vĩnh viễn đánh giá của {$userName} cho sản phẩm {$productName}",
                    'properties' => json_encode([
                        'content' => $content,
                        'score' => $score
                    ]),
                    'ip_address' => request()->ip(),
                    'created_at' => now(),
                ]);
            });

            return back()->with('success', 'Đánh giá đã được xóa vĩnh viễn khỏi hệ thống!');

        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi hệ thống khi xóa đánh giá: ' . $e->getMessage());
        }
    }
}
