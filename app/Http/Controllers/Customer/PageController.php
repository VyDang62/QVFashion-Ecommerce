<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PageController extends Controller
{
    public function show($slug)
    {
        $page = Page::where('slug', $slug)
                    ->where('is_active', true)
                    ->firstOrFail();

        return Inertia::render('customer/StaticPage', [
            'page' => $page
        ]);
    }
}
