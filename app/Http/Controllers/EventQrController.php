<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Symfony\Component\HttpFoundation\Response;

/**
 * رمز QR لصفحة الفعالية — يُعلَّق مطبوعاً في مراكز الإيواء،
 * ويظهر في صفحة الفعالية ليصوّره الأهالي ويشاركوه.
 */
class EventQrController extends Controller
{
    public function __invoke(Event $event): Response
    {
        abort_unless($event->status->isPubliclyVisible(), 404);

        $svg = (new Builder(
            writer: new SvgWriter(),
            data: $event->shortUrl(),
            size: 280,
            margin: 8,
        ))->build()->getString();

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
