<?php

namespace App\Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Report\Http\Requests\ReportFilterRequest;
use App\Modules\Report\Queries\ReportStatisticsQuery;
use Inertia\Inertia;
use Inertia\Response;

final class ReportController extends Controller
{
    public function __invoke(ReportFilterRequest $request, ReportStatisticsQuery $query): Response
    {
        return Inertia::render('Modules/Report/Pages/Index', $query->forActor($request->user(), $request->filters()));
    }
}
