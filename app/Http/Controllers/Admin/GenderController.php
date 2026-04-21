<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gender;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
class GenderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('perPage',10);
        $query = Gender::query();
        if ($request->filled('search')){
            $query->where('gender_name','like','%'.$request->search.'%');
        }

        $genders = $query->latest()->paginate($perPage)->withQueryString();

        return Inertia::render('admin/Genders/Index',[
            'genders' => $genders,
            'filters' => $request->only(['search','perPage'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('admin/Genders/Create', [
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validate = $request->validate([
        'gender_name'      => 'required|string|max:255',
        ]);
        Gender::create($validate);
        return redirect()->route('admin.genders.index')->with('success','Giới tính đã được thêm thành công!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Gender $gender)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Gender $gender)
    {

        return Inertia::render('admin/Genders/Edit', [
            'gender' => $gender,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Gender $gender)
    {
        $validate = $request->validate([
            'gender_name'      => 'required|string|max:255',
        ]);

        $gender->update($validate);
        return redirect()->route('admin.genders.index')->with('success', 'Cập nhật giới tính thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Gender $gender)
    {
        try {
            if ($gender->categories()->exists()) {
                return back()->with('error', "Không thể xóa! Giới tính '{$gender->gender_name}' đang được gán cho các danh mục sản phẩm!");
            }

            $gender->delete();

            return redirect()->route('admin.genders.index')
                ->with('success', 'Giới tính đã được xóa thành công!');

        } catch (QueryException $e) {
            return back()->with('error', 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }
}
