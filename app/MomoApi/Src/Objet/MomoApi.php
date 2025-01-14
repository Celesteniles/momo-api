<?php

namespace App\MomoApi\Src\Objet;

use Webpatser\Uuid\Uuid;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class MomoApi
{
    private const TARGET_COLLECTION = 'collection';
    private const TARGET_DISBURSEMENT = 'disbursement';

    private const CONTENT_TYPE_JSON = 'application/json';

    private const ACTIONS = [
        self::TARGET_COLLECTION => 'requesttopay',
        self::TARGET_DISBURSEMENT => 'transfer'
    ];

    /**
     * Get the encoded Basic Auth string for API authentication
     */
    private function getEncodedBasicString(string $target): string
    {
        $userKeys = config('momoapi.headers.api_keys.user_id');
        $apiKeys = config('momoapi.headers.api_keys.api_key');

        $index = ($target === self::TARGET_COLLECTION) ? 0 : 1;

        return base64_encode($userKeys[$index] . ':' . $apiKeys[$index]);
    }

    /**
     * Get authentication token for MTN MOMO API
     */
    public function momoLoginToken(string $target): ?string
    {
        try {
            $encoded = $this->getEncodedBasicString($target);
            $endpoint = $this->buildTokenEndpoint($target);
            $subscriptionKey = $this->getSubscriptionKey($target);

            $response = $this->makeTokenRequest($encoded, $subscriptionKey, $endpoint);

            return $this->handleTokenResponse($response);
        } catch (\Exception $e) {
            Log::channel('momoapi')->error('Token generation error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Handle collection operation
     */
    public function collection(): array
    {
        $token = $this->momoLoginToken(self::TARGET_COLLECTION);

        if ($token === null) {
            Log::channel('momoapi')->error('Token retrieval failed');
            return ['code' => '180', 'msg' => 'Une erreur est survenue lors du traitement'];
        }

        return $this->processPaymentRequest(
            self::TARGET_COLLECTION,
            $token,
            '242067230202',
            'payer',
            200
        );
    }

    /**
     * Handle disbursement operation
     */
    public function disbursement(): array
    {
        $token = $this->momoLoginToken(self::TARGET_DISBURSEMENT);

        if ($token === null) {
            Log::channel('momoapi')->error('Token retrieval failed');
            return ['code' => '180', 'msg' => 'Une erreur est survenue lors du traitement'];
        }

        return $this->processPaymentRequest(
            self::TARGET_DISBURSEMENT,
            $token,
            '242067230202',
            'payee',
            200
        );
    }

    /**
     * Process payment request for both collection and disbursement
     */
    private function processPaymentRequest(
        string $target,
        string $token,
        string $partyId,
        string $partyType,
        int $amount
    ): array {
        try {
            $uuid = Uuid::generate(4)->string;
            $subscriptionKey = $this->getSubscriptionKey($target);
            $headers = $this->buildRequestHeaders($token, $subscriptionKey, $uuid);

            $params = $this->buildRequestParams($target, $partyId, $partyType, $amount);
            $endpoint = $this->buildPaymentEndpoint($target);

            $response = Http::asJson()->withHeaders($headers)->post($endpoint, $params);

            return $this->handlePaymentResponse($response, $target);
        } catch (\Exception $e) {
            Log::channel('momoapi')->error(sprintf(
                '%s request failed with error: %s',
                ucfirst($target),
                $e->getMessage()
            ));
            return ['code' => '180', 'msg' => 'Une erreur est survenue lors du traitement'];
        }
    }

    /**
     * Build common request headers
     */
    private function buildRequestHeaders(string $token, string $subscriptionKey, string $uuid): array
    {
        return [
            'Authorization' => 'Bearer ' . $token,
            'Ocp-Apim-Subscription-Key' => $subscriptionKey,
            'Content-Type' => self::CONTENT_TYPE_JSON,
            'X-Reference-Id' => $uuid,
            'X-Target-Environment' => config('momoapi.headers.token.x_target_environment'),
            'Accept' => self::CONTENT_TYPE_JSON
        ];
    }

    /**
     * Build request parameters for payment
     */
    private function buildRequestParams(string $target, string $partyId, string $partyType, int $amount): array
    {
        $transid = mt_rand(10000000, 999999999);

        return [
            'amount' => $amount,
            'currency' => 'XAF',
            'externalId' => $transid,
            $partyType => [
                'partyIdType' => 'MSISDN',
                'partyId' => $partyId
            ],
            'payerMessage' => $target === self::TARGET_COLLECTION ? 'Recharge Nokipay' : 'Cash Nokipay',
            'payeeNote' => 'Momo_NOKIPAY'
        ];
    }

    /**
     * Build payment endpoint URL
     */
    private function buildPaymentEndpoint(string $target): string
    {
        $endpoint = config('momoapi.endpoints.pay_uri');
        return str_replace(
            [':target', ':action'],
            [$target, self::ACTIONS[$target]],
            $endpoint
        );
    }

    /**
     * Handle payment response
     */
    private function handlePaymentResponse(Response $response, string $target): array
    {
        if ($response->status() >= 200 && $response->status() < 210) {
            Log::channel('momoapi')->info(sprintf(
                '%s request successful. Status: %d | Body: %s',
                ucfirst($target),
                $response->status(),
                $response->body()
            ));
            return ['code' => '200', 'msg' => 'Tout s\'est bien passé'];
        }

        Log::channel('momoapi')->error(sprintf(
            '%s request failed. Status: %d | Body: %s',
            ucfirst($target),
            $response->status(),
            $response->body()
        ));
        return ['code' => '180', 'msg' => 'Une erreur est survenue lors du traitement'];
    }

    /**
     * Build token endpoint URL
     */
    private function buildTokenEndpoint(string $target): string
    {
        $endpoint = config('momoapi.endpoints.token_uri');
        return str_replace(':target', $target, $endpoint);
    }

    /**
     * Get subscription key based on target
     */
    private function getSubscriptionKey(string $target): string
    {
        $keys = config('momoapi.headers.token.ocp_apim_subscription_key');
        return $keys[($target === self::TARGET_COLLECTION) ? 0 : 1];
    }

    /**
     * Make token request to API
     */
    private function makeTokenRequest(string $encoded, string $subscriptionKey, string $endpoint): Response
    {
        $headers = [
            'Authorization' => 'Basic ' . $encoded,
            'Ocp-Apim-Subscription-Key' => $subscriptionKey,
            'Content-Type' => self::CONTENT_TYPE_JSON
        ];

        return Http::withHeaders($headers)->post($endpoint);
    }

    /**
     * Handle token response
     */
    private function handleTokenResponse(Response $response): ?string
    {
        if ($response->status() === 200) {
            $data = json_decode($response->body());
            return $data->access_token;
        }

        Log::channel('momoapi')->error(sprintf(
            'Token request failed. Status: %d | Body: %s',
            $response->status(),
            $response->body()
        ));

        return null;
    }

    /**
     * Get account balance
     */
    public function getAccountBalance(): array
    {
        $token = $this->momoLoginToken(self::TARGET_DISBURSEMENT);

        if ($token === null) {
            Log::channel('momoapi')->error('Token retrieval failed');
            return ['code' => '180', 'msg' => 'Une erreur est survenue lors du traitement'];
        }

        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => self::CONTENT_TYPE_JSON,
            'Ocp-Apim-Subscription-Key' => config('momoapi.headers.token.ocp_apim_subscription_key')[1],
            'X-Target-Environment' => config('momoapi.headers.token.x_target_environment'),
            'Accept' => self::CONTENT_TYPE_JSON
        ];

        $endpoint = config('momoapi.endpoints.account_balance');

        $response = Http::asJson()->withHeaders($headers)->get($endpoint);

        if ($response->status() === 200) {
            $account = json_decode($response->body());
            return ['code' => '200', 'msg' => 'Tout s\'est bien passé', 'data' => $account];
        }

        Log::channel('momoapi')->error(sprintf(
            'Account balance request failed. Status: %d | Body: %s',
            $response->status(),
            $response->body()
        ));
        return ['code' => '180', 'msg' => 'Une erreur est survenue lors du traitement'];
    }
}
