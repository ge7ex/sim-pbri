<?php

namespace App\Modules\SimResource\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SimResource\Actions\CreateSimResourceAction;
use App\Modules\SimResource\Actions\UpdateSimResourceAction;
use App\Modules\SimResource\Http\Requests\StoreSimResourceRequest;
use App\Modules\SimResource\Http\Requests\UpdateSimResourceRequest;
use App\Modules\SimResource\Models\SimResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class SimResourceController extends Controller
{
    public function index(Request $request): Response
    {
        $resources = SimResource::query()
            ->where('college_id', $request->user()->college_id)
            ->orderBy('kind')
            ->orderBy('name')
            ->get();

        return Inertia::render('Modules/SimResource/Pages/Index', [
            'resources' => $resources,
        ]);
    }

    public function store(
        StoreSimResourceRequest $request,
        CreateSimResourceAction $action,
    ): RedirectResponse {
        $action->execute(
            data: $request->validated(),
            actor: $request->user(),
        );

        return back()->with('success', 'เพิ่มทรัพยากรเรียบร้อยแล้ว');
    }

    public function update(
        UpdateSimResourceRequest $request,
        SimResource $simResource,
        UpdateSimResourceAction $action,
    ): RedirectResponse {
        $this->authorize('update', $simResource);

        $action->execute(
            resource: $simResource,
            data: $request->validated(),
        );

        return back()->with('success', 'ปรับปรุงทรัพยากรเรียบร้อยแล้ว');
    }
}
