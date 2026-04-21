<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class GHNService{
    protected $token;
    protected $shopId;
    protected $baseUrl = 'https://dev-online-gateway.ghn.vn/shiip/public-api/';

    public function __construct()
    {
        $this->token = config('services.ghn.token');
        $this->shopId = config('services.ghn.shop_id');
    }
    //Lấy danh sách Tỉnh/Thành
    public function getProvinces()
    {
        return Http::withHeaders(['Token' => $this->token])
            ->get($this->baseUrl . 'master-data/province')->json();
    }

    //Lấy Quận/Huyện theo ProvinceID
    public function getDistricts($provinceId)
    {
        return Http::withHeaders(['Token' => $this->token])
            ->post($this->baseUrl . 'master-data/district', ['province_id' => (int)$provinceId])->json();
    }

    //Lấy Phường/Xã theo DistrictID
    public function getWards($districtId)
    {
        return Http::withHeaders(['Token' => $this->token])
            ->post($this->baseUrl . 'master-data/ward', ['district_id' => (int)$districtId])->json();
    }

    public function calculateShippingFee($data)
    {
        return Http::withHeaders([
            'Token' => $this->token,
            'ShopId' => $this->shopId
        ])->post($this->baseUrl . 'v2/shipping-order/fee', [
            "from_district_id" => 1566,
            "from_ward_code" => "510111",
            "to_district_id" => (int)$data['district_id'],
            "to_ward_code" => (string)$data['ward_code'],
            "service_type_id" => 2, //Chuyển phát chuẩn
            "weight" => 300, //gram
            "length" => 20, "width" => 20, "height" => 10,
            "insurance_value" => (int)$data['total_amount'],
        ])->json();
    }
}