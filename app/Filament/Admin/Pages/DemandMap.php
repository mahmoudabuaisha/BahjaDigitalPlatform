<?php

namespace App\Filament\Admin\Pages;

use App\Models\NeighbourhoodCall;
use App\Services\NeighbourhoodDemandService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * خريطة الطلب: أين ينادي الأطفال ولا تصل الفعاليات.
 * الإدارة ترى الصورة كاملة — والفرق ترى المجمَّع فوق عتبة العرض.
 */
class DemandMap extends Page
{
    protected static string $routePath = '/demand-map';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHandRaised;

    protected static ?string $title = 'خريطة الطلب';

    protected static ?string $navigationLabel = 'خريطة الطلب';

    protected static string|UnitEnum|null $navigationGroup = 'إدارة المحتوى';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.admin.pages.demand-map';

    private function demand(): NeighbourhoodDemandService
    {
        return app(NeighbourhoodDemandService::class);
    }

    /** @return Collection<int, object> */
    public function areas(): Collection
    {
        return $this->demand()->byArea();
    }

    /** @return Collection<int, object> */
    public function places(): Collection
    {
        // الإدارة ترى كل الأماكن: عتبة العرض حماية من الفرق لا من المسؤول
        return $this->demand()->byPlace(forTeams: false, limit: 20);
    }

    /** @return array<string, int> */
    public function summary(): array
    {
        return [
            'standing' => NeighbourhoodCall::query()->standing()->count(),
            'children' => (int) NeighbourhoodCall::query()->standing()->sum('children_count'),
            'answered' => NeighbourhoodCall::query()
                ->whereNotNull('answered_at')
                ->where('answered_at', '>=', now()->subDays(30))
                ->count(),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $standing = NeighbourhoodCall::query()->standing()->count();

        return $standing > 0 ? (string) $standing : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
