<?php

namespace App\Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Http\Requests\UpdateOwnPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class AccountPasswordController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Modules/Access/Pages/Account/Password');
    }

    public function update(UpdateOwnPasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        DB::transaction(function () use ($request, $user): void {
            $user->forceFill([
                'password' => $request->validated('password'),
                'must_change_password' => false,
                'remember_token' => Str::random(60),
            ])->save();

            DB::table('user_access_events')->insert([
                'actor_user_id' => $user->id,
                'subject_user_id' => $user->id,
                'event' => 'password_changed',
                'details' => null,
                'created_at' => now(),
            ]);
        });

        $request->session()->regenerate();
        $request->session()->put('password_hash_web', $user->getAuthPassword());

        return redirect()->route('dashboard')->with('success', 'เปลี่ยนรหัสผ่านแล้ว');
    }
}
