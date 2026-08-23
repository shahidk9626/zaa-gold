<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'description'];

    /**
     * Retrieve a setting value by key.
     */
    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Retrieve the authorized signature image formatted as base64 Data URI for PDF embedding.
     */
    public static function getAuthorizedSignatureDataUri()
    {
        $path = self::get('authorized_signature_path');
        if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            $content = \Illuminate\Support\Facades\Storage::disk('public')->get($path);
            if (!empty($content)) {
                $mime = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($path) ?? 'image/png';
                return 'data:' . $mime . ';base64,' . base64_encode($content);
            }
        }
        return null;
    }

    /**
     * Retrieve the public web URL for authorized signature preview.
     */
    public static function getAuthorizedSignatureUrl()
    {
        $path = self::get('authorized_signature_path');
        if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }
        return null;
    }
}
