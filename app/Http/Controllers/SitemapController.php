<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Team;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            ['loc' => route('home'), 'priority' => '1.0'],
            ['loc' => route('guide'), 'priority' => '0.5'],
            ['loc' => route('feedback.create'), 'priority' => '0.3'],
        ];

        Event::publiclyVisible()
            ->upcoming()
            ->orderBy('start_date')
            ->limit(500)
            ->get(['id', 'updated_at'])
            ->each(function (Event $event) use (&$urls): void {
                $urls[] = [
                    'loc' => route('events.show', $event),
                    'lastmod' => $event->updated_at->toDateString(),
                    'priority' => '0.8',
                ];
            });

        Team::active()
            ->get(['id', 'slug', 'updated_at'])
            ->each(function (Team $team) use (&$urls): void {
                $urls[] = [
                    'loc' => route('teams.show', $team),
                    'lastmod' => $team->updated_at->toDateString(),
                    'priority' => '0.6',
                ];
            });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= '  <url><loc>'.e($url['loc']).'</loc>';

            if (isset($url['lastmod'])) {
                $xml .= '<lastmod>'.$url['lastmod'].'</lastmod>';
            }

            $xml .= '<priority>'.$url['priority'].'</priority></url>'."\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
