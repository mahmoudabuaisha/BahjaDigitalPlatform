<?php

namespace App\Filament\Admin\Resources\AttendanceReports\Pages;

use App\Filament\Admin\Resources\AttendanceReports\AttendanceReportResource;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceReports extends ListRecords
{
    protected static string $resource = AttendanceReportResource::class;

    public function getSubheading(): ?string
    {
        return 'أرقام الأثر الرسمية تُبنى على الحضور المتحقَّق منه فقط.';
    }
}
