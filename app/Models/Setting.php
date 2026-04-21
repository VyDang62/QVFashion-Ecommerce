<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
class Setting extends Model
{
    protected $fillable = ['key','value','type','group','description'];

    protected static function booted()
    {
        static::updated(fn ($setting) => Cache::forget("setting.{$setting->key}"));
        static::deleted(fn ($setting) => Cache::forget("setting.{$setting->key}"));
    }

    public static function get(string $key, $default = null){
        return Cache::rememberForever("setting.{$key}", function () use ($key, $default){
            $setting = self::where('key',$key)->first();
            
            if(!$setting){
                return $default;
            }

            return self::castValue($setting->value, $setting->type);
        });
    }

    public static function set(string $key, $value, string $type = 'string', string $group = 'general'){
        $valueToStore = is_array($value) || is_object($value) ? json_encode($value) : $value;

        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $valueToStore,
                'type' => $type,
                'group' => $group
            ]
        );

        Cache::forget("setting.{$key}");
        
        return $setting;
    }

    private static function castValue($value, $type)
    {
        return match ($type) {
            'integer', 'int' => (int) $value,
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'float', 'double' => (float) $value,
            'json', 'array' => json_decode($value, true),
            default => $value,
        };
    }

    public static function getLogoUrl(){
        $path = self::get('website_logo');
        if($path && Storage::disk('public')->exists($path)){
            return asset('storage/' .$path);
        }
        return asset('images/default-logo.png');
    }

    public static function getTaxRate()
    {
        //Lấy từ cache, nếu không có thì lấy từ DB và lưu vào cache trong 24h
        return cache()->remember('settings.tax_rate', 86400, function () {
            $value = DB::table('settings')->where('key', 'tax_rate')->value('value');
            return ($value ?? 10) / 100;
        });
    }
}
