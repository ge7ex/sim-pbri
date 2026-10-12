<?php

namespace App\Modules\Access\Http\Controllers;

use App\Core\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\College;
use App\Models\User;
use App\Modules\Access\Http\Requests\ResetUserPasswordRequest;
use App\Modules\Access\Http\Requests\StoreUserRequest;
use App\Modules\Access\Http\Requests\UpdateUserRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class UserManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = request()->user();
        $search = trim((string) $request->query('q', ''));
        $search = mb_substr($search, 0, 100);
        $query = User::query()->where('college_id', $actor->college_id);

        if ($search !== '') {
            $query->where(fn ($users) => $users
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%'));
        }

        return Inertia::render('Modules/Access/Pages/Users/Index', [
            'csrfToken' => $request->session()->token(),
            'users' => $query
                ->orderByDesc('id')
                ->paginate(25)
                ->through(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'role_label' => $user->accessRole()?->label(),
                    'is_active' => $user->is_active,
                    'must_change_password' => $user->must_change_password,
                ]),
            'roles' => array_map(fn (UserRole $role): array => [
                'value' => $role->value,
                'label' => $role->label(),
            ], UserRole::cases()),
            'collegeName' => $actor->college->name,
            'filters' => ['q' => $search],
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $actor = $request->user();
        $data = $request->validated();
        $temporaryPassword = $this->temporaryPassword();

        $user = DB::transaction(function () use ($actor, $data, $temporaryPassword): User {
            $user = User::query()->forceCreate([
                'college_id' => $actor->college_id,
                'role' => $data['role'],
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $temporaryPassword,
                'is_active' => true,
                'must_change_password' => true,
            ]);

            $this->recordEvent($actor, $user, 'created', ['role' => $user->role]);

            return $user;
        });

        return $this->noStoreJson([
            'user_id' => $user->id,
            'temporary_password' => $temporaryPassword,
        ], 201);
    }

    public function update(UpdateUserRequest $request, int $user): RedirectResponse
    {
        $actor = $request->user();
        $data = $request->validated();

        DB::transaction(function () use ($actor, $data, $user): void {
            College::query()->whereKey($actor->college_id)->lockForUpdate()->firstOrFail();
            $target = User::query()
                ->where('college_id', $actor->college_id)
                ->lockForUpdate()
                ->findOrFail($user);

            $isActive = (bool) $data['is_active'];
            $role = UserRole::from($data['role']);

            if ($target->is($actor) && (! $isActive || $role->value !== $actor->role)) {
                throw ValidationException::withMessages([
                    'role' => 'ไม่สามารถปิดใช้งานหรือเปลี่ยนบทบาทบัญชีที่กำลังใช้งานอยู่ได้',
                ]);
            }

            if ($target->role === UserRole::Admin->value
                && $target->is_active
                && (! $isActive || $role !== UserRole::Admin)
                && User::query()
                    ->where('college_id', $actor->college_id)
                    ->where('role', UserRole::Admin->value)
                    ->where('is_active', true)
                    ->count() <= 1) {
                throw ValidationException::withMessages([
                    'role' => 'ต้องมีผู้ดูแลระบบที่เปิดใช้งานอย่างน้อยหนึ่งบัญชีในหน่วยงาน',
                ]);
            }

            $before = ['role' => $target->role, 'is_active' => $target->is_active];
            $target->forceFill([
                'name' => $data['name'],
                'role' => $role->value,
                'is_active' => $isActive,
            ])->save();

            $this->recordEvent($actor, $target, 'updated', [
                'before' => $before,
                'after' => ['role' => $target->role, 'is_active' => $target->is_active],
            ]);
        });

        return back()->with('success', 'บันทึกข้อมูลบัญชีแล้ว');
    }

    public function resetPassword(ResetUserPasswordRequest $request, int $user): JsonResponse
    {
        $actor = $request->user();
        $target = User::query()
            ->where('college_id', $actor->college_id)
            ->findOrFail($user);

        if ($target->is($actor)) {
            throw ValidationException::withMessages([
                'current_password' => 'ใช้เมนูเปลี่ยนรหัสผ่านของฉันสำหรับบัญชีปัจจุบัน',
            ]);
        }

        $temporaryPassword = $this->temporaryPassword();

        DB::transaction(function () use ($actor, $target, $temporaryPassword): void {
            $target = User::query()
                ->where('college_id', $actor->college_id)
                ->lockForUpdate()
                ->findOrFail($target->id);
            $target->forceFill([
                'password' => $temporaryPassword,
                'must_change_password' => true,
                'remember_token' => Str::random(60),
            ])->save();

            $this->recordEvent($actor, $target, 'password_reset');
        });

        return $this->noStoreJson(['temporary_password' => $temporaryPassword]);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function recordEvent(User $actor, User $subject, string $event, array $details = []): void
    {
        DB::table('user_access_events')->insert([
            'actor_user_id' => $actor->id,
            'subject_user_id' => $subject->id,
            'event' => $event,
            'details' => $details === [] ? null : json_encode($details, JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
    }

    private function temporaryPassword(): string
    {
        do {
            $password = Str::password(24, true, true, true, true);
        } while (! preg_match('/[a-z]/', $password)
            || ! preg_match('/[A-Z]/', $password)
            || ! preg_match('/[0-9]/', $password));

        return $password;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function noStoreJson(array $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status, [
            'Cache-Control' => 'no-store, private',
            'Pragma' => 'no-cache',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }
}
