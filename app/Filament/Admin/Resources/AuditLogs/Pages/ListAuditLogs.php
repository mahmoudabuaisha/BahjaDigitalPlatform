<?php

namespace App\Filament\Admin\Resources\AuditLogs\Pages;

use App\Filament\Admin\Resources\AuditLogs\AuditLogResource;
use Filament\Resources\Pages\ListRecords;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    public function getSubheading(): ?string
    {
        return 'سجل للقراءة فقط — كل قرار حساس يظهر هنا بمن قام به ووقته وسببه.';
    }
}
