<?php

namespace App\Http\Controllers;

use App\Models\GarageType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Public pages that visitors can open without logging in.
 */
class PageController extends Controller
{
    /**
     * Home page with the hero, garage types with prices, and how it works.
     */
    public function home(): View
    {
        $garageTypes = GarageType::withCount('garages')->orderBy('code')->get();

        return view('home', compact('garageTypes'));
    }

    /**
     * Features page. Visitors see a locked preview, users see their own menu.
     */
    public function features(): View|RedirectResponse
    {
        $user = auth()->user();

        // Admins work from the dashboard, which already shows their overview and links.
        if ($user?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('features', [
            'groups' => $this->featureGroups($user),
            'locked' => $user === null,
        ]);
    }

    /**
     * Feature cards grouped by role. Guests see both groups behind the overlay.
     *
     * @return array<int, array{title: string, items: array<int, array<string, string>>}>
     */
    private function featureGroups(?User $user): array
    {
        $renter = [
            'title' => 'For renters',
            'items' => [
                [
                    'title' => 'New rental',
                    'text' => 'Enter your vehicle, pick the dates, see the total cost, and upload your payment proof.',
                    'route' => 'renter.rentals.create',
                ],
                [
                    'title' => 'Rental history',
                    'text' => 'Review your past and running rentals together with their payment proofs.',
                    'route' => 'renter.rentals.index',
                ],
            ],
        ];

        $admin = [
            'title' => 'For admins',
            'items' => [
                [
                    'title' => 'Create garages',
                    'text' => 'Add garages in batches and set the daily and electricity prices per type.',
                    'route' => 'admin.garages.create',
                ],
                [
                    'title' => 'Verify renters',
                    'text' => 'Check the NIK of new registrations and approve or reject them.',
                    'route' => 'admin.renters.index',
                ],
                [
                    'title' => 'Extend rentals',
                    'text' => 'Extend the end date of rentals that are active today and record the extra payment.',
                    'route' => 'admin.rentals.index',
                ],
                [
                    'title' => 'Reports',
                    'text' => 'See rentals and revenue per month, per day, per garage type, and per renter.',
                    'route' => 'admin.reports.index',
                ],
            ],
        ];

        return match (true) {
            $user?->isRenter() => [$renter],
            default => [$renter, $admin],
        };
    }
}