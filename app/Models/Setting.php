<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];
    
    public static function getBaseUrl()
    {
        $setting = self::where('key', 'base_url')->first();
        return $setting ? $setting->value : null;
    }
    
    public static function setBaseUrl($url)
    {
        $setting = self::where('key', 'base_url')->first();
        if ($setting) {
            $setting->value = $url;
            $setting->save();
        } else {
            self::create([
                'key' => 'base_url',
                'value' => $url,
            ]);
        }
    }
}
