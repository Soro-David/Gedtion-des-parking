<?php

namespace App\Http\Controllers\Caissier;

use App\Http\Controllers\Controller;
use App\Models\Versement;
use Inertia\Inertia;

class VersementController extends Controller
{
    /**
     * Liste des versements reçus pour ce caissier.
     */
    public function index()
    {
        $user = auth()->user();

        $versements = Versement::with('admin')
            ->where('caissier_id', $user->id)
            ->latest()
            ->get()
            ->map(fn($v) => [
                'id'               => $v->id,
                'collected_amount' => $v->collected_amount,
                'previous_debt'    => $v->previous_debt,
                'total_due'        => $v->total_due,
                'paid_amount'      => $v->paid_amount,
                'remaining_debt'   => $v->remaining_debt,
                'note'             => $v->note,
                'admin_name'       => $v->admin->name ?? '—',
                'created_at'       => $v->created_at->toDateTimeString(),
            ]);

        return Inertia::render('Caissier/Versement/Index', [
            'versements' => $versements,
        ]);
    }
}
