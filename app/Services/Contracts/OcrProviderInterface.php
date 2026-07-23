<?php

namespace App\Services\Contracts;

use Illuminate\Http\UploadedFile;

/**
 * Interface OcrProviderInterface
 *
 * Contrat pour les services de reconnaissance de plaques d'immatriculation.
 */
interface OcrProviderInterface
{
    /**
     * Extrait le numéro de plaque d'immatriculation depuis un fichier image ou un chemin d'accès.
     *
     * @param  UploadedFile|string  $image  Fichier uploadé ou chemin d'accès absolu.
     * @return string|null La plaque d'immatriculation en majuscules ou null si non trouvée.
     */
    public function extract(UploadedFile|string $image): ?string;
}
