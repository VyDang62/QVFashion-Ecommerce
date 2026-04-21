<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\GHNService;
use Illuminate\Http\Request;

class GHNController extends Controller
{
    protected $ghn;

    public function __construct(GHNService $ghn)
    {
        $this->ghn = $ghn;
    }

    public function provinces() { return response()->json($this->ghn->getProvinces()); }

    public function districts($id) { return response()->json($this->ghn->getDistricts($id)); }

    public function wards($id) { return response()->json($this->ghn->getWards($id)); }

    public function calculateShippingFee(Request $request) {
        $result = $this->ghn->calculateShippingFee($request->all()); 
    
        return response()->json($result);
    }
}


