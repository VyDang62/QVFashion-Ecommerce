<?php
namespace App\Enums;

enum BannerPosition: string{
    case HOME_SLIDER = 'home_slider';
    case SIDEBAR = 'sidebar';
    case PROMO_POPUP = 'promo_popup';
    case CATEGORY_TOP = 'category_top';
    case FOOTER_BANNER = 'footer_banner';

    public function label(): string
    {
        return match($this) {
            self::HOME_SLIDER => 'Slider Trang Chủ (1920x800)',
            self::SIDEBAR => 'Thanh bên',
            self::PROMO_POPUP => 'Popup Khuyến mãi',
            self::CATEGORY_TOP => 'Đầu trang danh mục',
            self::FOOTER_BANNER => 'Banner dưới chân trang',
        };
    }

    public static function toSelectOptions(): array
    {
        return array_map(fn($case) => [
            'label' => $case->label(),
            'value' => $case->value,
        ], self::cases());
    }
}