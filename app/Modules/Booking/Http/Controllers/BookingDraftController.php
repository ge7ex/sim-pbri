<?php

namespace App\Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Booking\Http\Requests\StoreBookingDraftRequest;
use App\Modules\Booking\Models\BookingDraft;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class BookingDraftController extends Controller
{
    public function store(StoreBookingDraftRequest $request): RedirectResponse
    {
        return DB::transaction(function () use ($request): RedirectResponse {
            User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $owned = BookingDraft::query()->where('college_id', $request->user()->college_id)->where('user_id', $request->user()->id);
            if ($owned->count() >= 10) {
                throw ValidationException::withMessages(['drafts' => 'มีร่างคำขอครบ 10 รายการแล้ว ลบร่างที่ไม่ใช้ก่อนบันทึกร่างใหม่']);
            }

            $draft = BookingDraft::query()->create([
                'college_id' => $request->user()->college_id,
                'user_id' => $request->user()->id,
                'payload' => $request->validated(),
            ]);

            return redirect()->route('bookings.create', ['draft' => $draft->id])->with('success', 'บันทึกร่างคำขอแล้ว');
        });
    }

    public function update(StoreBookingDraftRequest $request, int $draft): RedirectResponse
    {
        $owned = $this->ownedDraft($request, $draft);
        $owned->update(['payload' => $request->validated()]);

        return redirect()->route('bookings.create', ['draft' => $owned->id])->with('success', 'บันทึกร่างคำขอแล้ว');
    }

    public function destroy(Request $request, int $draft): RedirectResponse
    {
        $this->ownedDraft($request, $draft)->delete();

        return redirect()->route('bookings.create')->with('success', 'ลบร่างคำขอแล้ว');
    }

    private function ownedDraft(Request $request, int $id): BookingDraft
    {
        return BookingDraft::query()
            ->where('college_id', $request->user()->college_id)
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);
    }
}
