<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Models\Contractor;
use App\Models\ImplementingOffice;
use App\Models\Program;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalProjects = Project::count();
        $totalBudget = Project::sum('approved_budget_contract');
        $activeProjects = Project::where('status', 'active')->count();
        $totalPrograms = Program::count();
        $totalContractors = Contractor::count();
        $totalOffices = ImplementingOffice::count();

        return [
            Stat::make('Total Projects', Number::format($totalProjects))
                ->description('All registered projects')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('primary')
                ->chart([7, 2, 10, 3, 15, 4, 17]),
            
            Stat::make('Total Budget', '₱' . Number::abbreviate($totalBudget, precision: 2))
                ->description('Total approved budget')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
            
            Stat::make('Active Projects', Number::format($activeProjects))
                ->description('Currently active')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('warning'),
            
            Stat::make('Programs', Number::format($totalPrograms))
                ->description('Government programs')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('info'),
            
            Stat::make('Contractors', Number::format($totalContractors))
                ->description('Registered contractors')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('danger'),
            
            Stat::make('Offices', Number::format($totalOffices))
                ->description('Implementing offices')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('gray'),
        ];
    }
}
