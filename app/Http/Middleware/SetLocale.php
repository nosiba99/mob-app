<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    /**
     * التطبيق بيبعث لغة المستخدم بهيدر Accept-Language (مثلاً "ar" أو "en")
     * إذا ما انبعث شي، أو انبعثت لغة مش مدعومة، منستخدم العربي كافتراضي.
     */
    public function handle(Request $request, Closure $next)
    { 
        $supported = ['ar', 'en'];

        $locale = $request->header('Accept-Language', 'ar');
        $locale = substr($locale, 0, 2); // منشان لو انبعث "en-US" ناخد "en" بس

        if (!in_array($locale, $supported)) {
            $locale = 'ar';
        }

        App::setLocale($locale);
  
        return $next($request);
    }
}
