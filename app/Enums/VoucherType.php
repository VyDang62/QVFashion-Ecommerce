<?php
namespace App\Enums;

enum VoucherType: string
{
    case FIXED = 'fixed';
    case PERCENTAGE = 'percentage';

    public function label(): string
    {
        return match($this){
            self::FIXED => 'Số tiền cố định (VNĐ)',
            self::PERCENTAGE => 'Phần trăm (%)',
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