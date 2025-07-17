<?php

namespace App\AirtelApi\Src\Objet;

class AirtelResponseHandler
{
    /**
     * Mapping des codes de réponse Airtel vers des messages personnalisés
     */
    private static array $responseCodes = [
        // Codes de succès
        'DP00900001001' => 'Transaction effectuée avec succès',
        'DP00900001000' => 'Paiement traité avec succès',

        // Codes d'erreur - Fonds insuffisants
        'DP00900001007' => 'Fonds insuffisants. Veuillez recharger votre compte et réessayer.',
        '0000900' => 'Fonds insuffisants. Veuillez recharger votre compte et réessayer.',

        // Codes d'erreur - Numéro invalide
        'DP00900001002' => 'Numéro de téléphone invalide ou non enregistré sur Airtel Money.',
        'DP00900001003' => 'Numéro de téléphone non valide.',

        // Codes d'erreur - Montant
        'DP00900001004' => 'Montant invalide. Vérifiez le montant et réessayez.',
        'DP00900001005' => 'Montant en dehors des limites autorisées.',

        // Codes d'erreur - Système
        'DP00900001006' => 'Service temporairement indisponible. Veuillez réessayer plus tard.',
        'DP00900001008' => 'Transaction en cours de traitement. Veuillez patienter.',
        'DP00900001009' => 'Transaction échouée. Veuillez réessayer.',

        // Codes d'erreur - Authentification
        'DP00900001010' => 'Erreur d\'authentification. Vérifiez vos identifiants.',
        'DP00900001011' => 'PIN incorrect. Veuillez vérifier votre PIN.',

        // Codes d'erreur - Limite
        'DP00900001012' => 'Limite de transaction quotidienne atteinte.',
        'DP00900001013' => 'Limite de transaction mensuelle atteinte.',

        // Code générique
        'unknown_error' => 'Une erreur inattendue s\'est produite. Veuillez réessayer.',
    ];

    /**
     * Obtient le message personnalisé pour un code de réponse donné
     */
    public static function getMessage(string $responseCode, string $fallbackMessage = null): string
    {
        return self::$responseCodes[$responseCode] ?? $fallbackMessage ?? self::$responseCodes['unknown_error'];
    }

    /**
     * Vérifie si un code de réponse indique un succès
     */
    public static function isSuccessCode(string $responseCode): bool
    {
        $successCodes = ['DP00900001001', 'DP00900001000'];
        return in_array($responseCode, $successCodes);
    }

    /**
     * Ajoute ou met à jour un code de réponse
     */
    public static function addResponseCode(string $code, string $message): void
    {
        self::$responseCodes[$code] = $message;
    }

    /**
     * Obtient tous les codes de réponse
     */
    public static function getAllCodes(): array
    {
        return self::$responseCodes;
    }
}
