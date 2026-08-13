<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class PwaController extends Controller
{
    public function manifest(): Response
    {
        return response()
            ->view('pwa.manifest')
            ->header('Content-Type', 'application/manifest+json')
            ->header('Cache-Control', 'public, max-age=86400');
    }

    /**
     * الـ Service Worker يُقدَّم عبر Blade كي تُحقن قائمة precache
     * ورقم الإصدار من Vite manifest وقت الطلب.
     */
    public function serviceWorker(): Response
    {
        $manifestPath = public_path('build/manifest.json');

        $version = is_file($manifestPath) ? md5_file($manifestPath) : 'dev';

        $buildAssets = [];

        if (is_file($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true) ?: [];

            foreach ($manifest as $entry) {
                if (isset($entry['file'])) {
                    $buildAssets[] = '/build/'.$entry['file'];
                }

                foreach ($entry['css'] ?? [] as $css) {
                    $buildAssets[] = '/build/'.$css;
                }
            }
        }

        return response()
            ->view('pwa.sw', [
                'version' => $version,
                'buildAssets' => array_values(array_unique($buildAssets)),
            ])
            ->header('Content-Type', 'application/javascript; charset=UTF-8')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }
}
