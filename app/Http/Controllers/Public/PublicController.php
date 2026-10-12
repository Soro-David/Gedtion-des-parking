<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Parking;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicController extends Controller
{
    /**
     * Page d'accueil publique avec les zones de parking.
     */
    public function accueil()
    {
        $parkings = Parking::where('is_active', true)
            ->withCount('activeSessions')
            ->get()
            ->map(function ($parking) {
                $occupied  = $parking->active_sessions_count ?? 0;
                $available = max(0, $parking->capacity - $occupied);
                $pct       = $parking->capacity > 0
                    ? round($occupied / $parking->capacity * 100)
                    : 0;

                $saturation = match (true) {
                    $pct >= 90 => 'saturé',
                    $pct >= 60 => 'modéré',
                    default    => 'disponible',
                };

                return [
                    'id'          => $parking->id,
                    'name'        => $parking->name,
                    'address'     => $parking->address,
                    'latitude'    => $parking->latitude,
                    'longitude'   => $parking->longitude,
                    'capacity'    => $parking->capacity,
                    'occupied'    => $occupied,
                    'available'   => $available,
                    'saturation'  => $saturation,
                    'pct'         => $pct,
                    'image'       => $parking->image ? (filter_var($parking->image, FILTER_VALIDATE_URL) ? $parking->image : asset('storage/' . $parking->image)) : null,
                    'reference'   => $parking->reference,
                ];
            });

        return Inertia::render('Public/Accueil', [
            'parkings' => $parkings,
        ]);
    }

    /**
     * Page "À propos".
     */
    public function aPropos()
    {
        return Inertia::render('Public/APropos');
    }

    /**
     * Page "Contact".
     */
    public function contact()
    {
        return Inertia::render('Public/Contact');
    }

    /**
     * Page "Politique de confidentialité".
     */
    public function politiqueConfidentialite()
    {
        return Inertia::render('Public/PolitiqueConfidentialite');
    }

    /**
     * Traitement du formulaire de contact.
     */
    public function sendContact(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:3000',
        ]);

        // TODO: envoyer un email avec Mail::to(config('mail.from.address'))->send(new ContactMail($validated));
        // Pour l'instant, on retourne juste un succès 
        return back()->with('success', 'Votre message a bien été envoyé. Nous vous répondrons dans les plus brefs délais.');
    }

    /**
     * API pour obtenir les parkings actifs pour les visiteurs.
     */
    public function accueilApi()
    {
        $parkings = Parking::where('is_active', true)
            ->withCount('activeSessions')
            ->get()
            ->map(function ($parking) {
                $occupied  = $parking->active_sessions_count ?? 0;
                $available = max(0, $parking->capacity - $occupied);
                $pct       = $parking->capacity > 0
                    ? round($occupied / $parking->capacity * 100)
                    : 0;

                $saturation = match (true) {
                    $pct >= 90 => 'saturé',
                    $pct >= 60 => 'modéré',
                    default    => 'disponible',
                };

                return [
                    'id'          => $parking->id,
                    'name'        => $parking->name,
                    'address'     => $parking->address,
                    'latitude'    => floatval($parking->latitude),
                    'longitude'   => floatval($parking->longitude),
                    'capacity'    => intval($parking->capacity),
                    'occupied'    => intval($occupied),
                    'available'   => intval($available),
                    'saturation'  => $saturation,
                    'pct'         => $pct,
                    'price'       => floatval($parking->price),
                    'image'       => $parking->image ? (filter_var($parking->image, FILTER_VALIDATE_URL) ? $parking->image : asset('storage/' . $parking->image)) : null,
                    'reference'   => $parking->reference,
                    'description' => 'Parking sécurisé ouvert 24h/24, 7j/7 avec contrôle d\'accès et caméras de surveillance.',
                    'opening_hours' => '24h/24, 7j/7',
                ];
            });

        return response()->json($parkings);
    }
}
