<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class LocationController extends Controller
{
    protected $apiBaseUrl = 'https://provinces.open-api.vn/api';
    protected $cacheTime = 2592000;
    public function getProvinces()
    {
        return Cache::remember('provinces', $this->cacheTime, function () {
            return Http::get("{$this->apiBaseUrl}/p/")->json();
        });
    }

    public function getDistricts($provinceCode)
    {
        $cacheKey = "districts_{$provinceCode}";

        return Cache::remember($cacheKey, $this->cacheTime, function () use ($provinceCode) {
            return Http::get("{$this->apiBaseUrl}/p/{$provinceCode}?depth=2")->json();
        });
    }

    public function getWards($districtCode)
    {
        $cacheKey = "wards_{$districtCode}";

        return Cache::remember($cacheKey, $this->cacheTime, function () use ($districtCode) {
            return Http::get("{$this->apiBaseUrl}/d/{$districtCode}?depth=2")->json();
        });
    }
}
