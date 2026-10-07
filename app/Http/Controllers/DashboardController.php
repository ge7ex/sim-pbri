<?php

namespace App\Http\Controllers;

use App\Queries\DashboardQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardQuery $query): Response
    {
        return Inertia::render('Dashboard', ['dashboard' => $query->forActor($request->user())]);
    }
}
