<?php
namespace App\Enums;
enum Gender: string
{
    case MALE = 'male';
    case FEMALE = 'female';

    public function label(): string
    {
        return match($this) {
            self::MALE => 'Nam',
            self::FEMALE => 'Nữ',
        };
    }

    public static function labels(): array
    {
        return [
            self::MALE->value => 'Nam',
            self::FEMALE->value => 'Nữ',
        ];
    }

    public static function toSelectOptions(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }

    public static function genderSlugs()
    {
        return [
            'nam' => 'men'
        ];
    }
}