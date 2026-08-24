<?php

namespace App\Filament\Admin\Resources\TeamApplications\Pages;

use App\Filament\Admin\Resources\TeamApplications\TeamApplicationResource;
use Filament\Resources\Pages\ListRecords;

class ListTeamApplications extends ListRecords
{
    protected static string $resource = TeamApplicationResource::class;

    public function getSubheading(): ?string
    {
        return 'لا يبدأ الفريق التشغيل قبل اعتماد طلبه.';
    }
}
