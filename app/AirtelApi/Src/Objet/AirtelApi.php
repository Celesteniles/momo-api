<?php

namespace App\AirtelApi\Src\Objet;

use Webpatser\Uuid\Uuid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class AirtelApi
{
    /**
     * Recuperation du Token OAuth2 pour l'API Airtel Money
     */
    private function getAccessToken()
    {
        $clientId = config('airtel.credentials.client_id');
        $clientSecret = config('airtel.credentials.client_secret');

        // Construction de l'URL OAuth2
        $url = config('airtel.endpoints.token_uri');

        // Corps de la requête selon la documentation
        $payload = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => config('airtel.credentials.grant_type')
        ];

        try {
            // Utilisation de Laravel HTTP Client pour l'authentification
            $response = Http::withHeaders([
                'Content-Type' => config('airtel.headers.content_type'),
                'Accept' => config('airtel.headers.accept')
            ])->post($url, $payload);

            // Si la requête a échoué, logger l'erreur et retourner null
            if (!$response->successful()) {
                Log::error('Token request failed', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return null;
            }

            // Récupérer les données JSON
            $responseData = $response->json();

            if (!empty($responseData['access_token'])) {
                return $responseData['access_token'];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Exception in getAccessToken', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }


    /**
     * Méthode pour initier un paiement (collection)
     */
    public function collection($phone, $amount, $transid = null)
    {
        $payload = [
            'reference' => 'Testing transaction',
            'subscriber' => [
                'country' => config('airtel.location.country'),
                'currency' => config('airtel.location.currency'),
                'msisdn' => $this->formatPhoneNumber($phone)
            ],
            'transaction' => [
                'amount' => round($amount, 2),
                'country' => config('airtel.location.country'),
                'currency' => config('airtel.location.currency'),
                'id' => $transid ?? Uuid::generate(4)->string
            ]
        ];

        return $this->processTransaction(
            config("airtel.endpoints.collection_uri"),
            $payload,
            'collection'
        );
    }

    /**
     * Méthode pour initier un décaissement (disbursement)
     */
    public function disbursement($phone, $amount, $transid = null)
    {
        $referenceId = $transid ?? Uuid::generate(4)->string;

        $payload = [
            'payee' => [
                'msisdn' => $this->formatPhoneNumber($phone)
            ],
            'reference' => 'REF-' . $referenceId,
            'pin' => config('airtel.credentials.encrypted_pin'),
            'transaction' => [
                'amount' => round($amount, 2),
                'id' => $referenceId
            ]
        ];

        return $this->processTransaction(
            config("airtel.endpoints.disbursement_uri"),
            $payload,
            'disbursement'
        );
    }

    /**
     * Traite une transaction Airtel Money (logique commune)
     */
    private function processTransaction(string $url, array $payload, string $transactionType): string
    {
        $environnement = config('airtel.environnement'); // testing, staging, production
        // Obtenir le token d'accès
        $token = self::getAccessToken();

        if (!$token) {
            return $this->formatErrorResponse(
                'Impossible d\'obtenir un token d\'accès depuis Airtel Money'
            );
        }

        try {
            // Si c'est en testing, simuler la requête sans attaquer Airtel Money
            if ($environnement === "testing") {
                // Créer une réponse simulée
                $simulatedResponseData = [
                    "data" => [
                        "transaction" => [
                            "reference_id" => "null",
                            "airtel_money_id" => "auto-generated-random-unique-id-" . rand(1000, 9999),
                            "id" => $payload['transaction']['id'] ?? "random-unique-id-" . rand(1000, 9999),
                            "status" => "TS"
                        ]
                    ],
                    "status" => [
                        "response_code" => "DP00900001001",
                        "code" => "200",
                        "success" => true,
                        "result_code" => "ESB000010",
                        "message" => "Trans. ID:CI" . date('ymd') . "." . date('Hi') . ".C00021 Vous avez envoye " .
                            ($payload['transaction']['amount'] ?? '2500.00') . "CFA a " .
                            ($payload['subscriber']['msisdn'] ?? '055009720') .
                            ""
                    ]
                ];

                // Créer un objet de réponse HTTP simulé
                $response = new \Illuminate\Http\Client\Response(
                    new \GuzzleHttp\Psr7\Response(
                        200,
                        ['Content-Type' => 'application/json'],
                        json_encode($simulatedResponseData)
                    )
                );
            } else {
                // Effectuer la requête HTTP
                $response = Http::withHeaders($this->getRequestHeaders($token))
                    ->post($url, $payload);
            }

            Log::debug("Airtel Money {$transactionType} Response:", [
                'response' => $response->body()
            ]);

            // Traiter la réponse
            return $this->handleResponse($response, $payload['transaction']['id'] ?? null);
        } catch (\Exception $e) {
            Log::error("Exception in Airtel Money {$transactionType} process", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->formatErrorResponse(
                'Exception lors du traitement: ' . $e->getMessage(),
                'EXCEPTION'
            );
        }
    }

    /**
     * Gère la réponse de l'API Airtel Money
     */
    private function handleResponse($response, $referenceId): string
    {
        $responseData = $response->json();

        // Vérifier si on a une réponse JSON valide
        if (!$responseData) {
            return $this->formatErrorResponse(
                'Réponse invalide de l\'API Airtel Money',
                'INVALID_RESPONSE'
            );
        }

        // Extraire les informations du statut
        $statusInfo = $this->extractStatusInfo($responseData);

        // Vérifier le succès selon la structure de la réponse Airtel
        if (!$statusInfo['is_success']) {
            return $this->formatErrorResponse(
                $statusInfo['message'],
                $statusInfo['code'],
                $responseData
            );
        }

        // Transaction réussie
        $transactionId = $responseData['data']['transaction']['id'] ?? $referenceId;

        return json_encode([
            'error' => false,
            'data' => $response->body(),
            'message' => $statusInfo['message'],
            'code' => $statusInfo['code'],
            'status' => $response->status(),
            'transaction_id' => $transactionId
        ]);
    }

    /**
     * Extrait les informations de statut de la réponse
     */
    private function extractStatusInfo(array $responseData): array
    {
        $status = $responseData['status'] ?? [];

        $isSuccess = $status['success'] ?? false;
        $responseCode = $status['response_code'] ?? 'unknown_error';
        $originalMessage = $status['message'] ?? 'Erreur inconnue';

        // Obtenir le message personnalisé
        $customMessage = AirtelResponseHandler::getMessage($responseCode, $originalMessage);

        return [
            'is_success' => $isSuccess,
            'code' => $responseCode,
            'message' => $customMessage,
            'original_message' => $originalMessage
        ];
    }

    /**
     * Formate le numéro de téléphone
     */
    private function formatPhoneNumber(string $phone): string
    {
        $phoneNumber = preg_replace('/\D/', '', $phone);

        // Supprime l'indicatif pays s'il est présent (242 pour le Congo)
        if (str_starts_with($phoneNumber, '242')) {
            $phoneNumber = substr($phoneNumber, 3);
        }

        return $phoneNumber;
    }

    /**
     * Obtient les en-têtes pour la requête HTTP
     */
    private function getRequestHeaders(string $token): array
    {
        return [
            'Content-Type' => config('airtel.headers.content_type'),
            'X-Country' => config('airtel.location.country'),
            'X-Currency' => config('airtel.location.currency'),
            'Authorization' => 'Bearer ' . $token,
        ];
    }

    /**
     * Formate une réponse d'erreur
     */
    private function formatErrorResponse(string $message, string $code = 'ERROR', array $detail = null): string
    {
        $response = [
            'error' => true,
            'message' => $message,
            'code' => $code,
        ];

        if ($detail !== null) {
            $response['detail'] = $detail;
        }

        return json_encode($response);
    }

    /**
     * Methode pour vérifier l'état de la transaction
     */
    public function checkTransactionStatus($transactionId, $target = "collection")
    {
        $environnement = config('airtel.environnement'); // testing, staging, production

        // Generer un Token d'accès
        $token = self::getAccessToken();
        if (!$token) {
            return json_encode([
                'status'  => 'error',
                'message' => 'Impossible d\'obtenir un token'
            ]);
        }

        $country = config('airtel.location.country');
        $currency = config('airtel.location.currency');

        $url = config("airtel.endpoints." . $target . "_pay_status") . $transactionId;

        try {
            // Si c'est en testing, simuler la requête
            if ($environnement === "testing") {
                // Créer une réponse simulée
                $simulatedResponseData = [
                    "data" => [
                        "transaction" => [
                            "reference_id" => "null",
                            "airtel_money_id" => "auto-generated-random-unique-id-" . rand(1000, 9999),
                            "id" => "auto-generated-random-unique-id-" . rand(1000, 9999),
                            "status" => "TS"
                        ]
                    ],
                    "status" => [
                        "response_code" => "DP00900001001",
                        "code" => "200",
                        "success" => true,
                        "result_code" => "ESB000010",
                        "message" => "Trans. ID:CI" . date('ymd') . "." . date('Hi') . ".C00021 Vous avez envoye"
                    ]
                ];

                // Créer un objet de réponse HTTP simulé
                $response = new \Illuminate\Http\Client\Response(
                    new \GuzzleHttp\Psr7\Response(
                        200,
                        ['Content-Type' => 'application/json'],
                        json_encode($simulatedResponseData)
                    )
                );
            } else {
                // Utilisation de Laravel HTTP Client pour la requête GET
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => config('airtel.headers.content_type'),
                    'Accept' => config('airtel.headers.accept'),
                    'X-Country' => $country,
                    'X-Currency' => $currency
                ])->get($url);
            }

            Log::info('Airtel Money status check response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            // return $response->body();
            return json_encode([
                'error'   => false,
                'data'    => $response->body(),
                'message' => 'Transaction reussie',
                'code'    => 'SUCCESS',
                'detail'  => $response->body()
            ]);
        } catch (\Exception $e) {
            Log::error('Exception in checkTransactionStatus', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return json_encode([
                'status'  => 'error',
                'message' => 'Exception lors de la vérification: ' . $e->getMessage()
            ]);
        }
    }
}
