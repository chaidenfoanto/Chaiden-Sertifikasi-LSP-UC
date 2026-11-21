<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Lets the admin approve or reject pending renter registrations.
 */
class RenterVerificationController extends Controller
{
    /**
     * List renters waiting for verification, oldest first.
     */
    public function index(): View
    {
        $renters = User::where('role', User::ROLE_RENTER)
            ->where('status', User::STATUS_PENDING)
            ->orderBy('created_at')
            ->get();

        return view('admin.renters.index', compact('renters'));
    }

    /**
     * Approve a pending renter.
     */
    public function approve(User $user): RedirectResponse
    {
        return $this->changeStatus($user, User::STATUS_APPROVED, 'approved');
    }

    /**
     * Reject a pending renter.
     */
    public function reject(User $user): RedirectResponse
    {
        return $this->changeStatus($user, User::STATUS_REJECTED, 'rejected');
    }

    /**
     * Move a pending renter to the given status.
     */
    private function changeStatus(User $user, string $status, string $label): RedirectResponse
    {
        abort_unless($user->isRenter() && $user->status === User::STATUS_PENDING, 404);

        $user->update(['status' => $status]);

        return redirect()
            ->route('admin.renters.index')
            ->with('success', "{$user->full_name} has been {$label}.");
    }
}