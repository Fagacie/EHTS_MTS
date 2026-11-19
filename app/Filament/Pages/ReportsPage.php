<?php

namespace App\Filament\Pages;

use App\Services\Report\ReportsService;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class ReportsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|UnitEnum|null $navigationGroup = 'Communications & Reports';

    protected static ?string $navigationLabel = 'Reports & Analytics';

    protected static ?string $title = 'ETMS Administrative Reports';

    protected static ?string $slug = 'reports';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.reports-page';

    public ?string $fromDate = null;

    public ?string $toDate = null;

    public function mount(): void
    {
        $from = request()->query('from');
        $to = request()->query('to');

        $this->fromDate = is_string($from) && $from !== '' ? $from : null;
        $this->toDate = is_string($to) && $to !== '' ? $to : null;
    }

    public function applyFilters(): void
    {
        // Trigger re-render with updated $fromDate and $toDate bounds
    }

    public function resetFilters(): void
    {
        $this->fromDate = null;
        $this->toDate = null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'report' => app(ReportsService::class)->generate($this->fromDate, $this->toDate),
        ];
    }
}
