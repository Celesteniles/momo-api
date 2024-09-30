<?php

namespace App\MomoApi\Src\Objet;

use Webpatser\Uuid\Uuid;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class MomoApi
{
    public function getEncodedBasicString($target): string
    {
        $user_key = "";
        $api_key = "";

        if ($target == "collection") {
            $user_key = config('momoapi.headers.api_keys.user_id')[0];
            $api_key = config('momoapi.headers.api_keys.api_key')[0];
        }

        if ($target == "disbursement") {
            $user_key = config('momoapi.headers.api_keys.user_id')[1];
            $api_key = config('momoapi.headers.api_keys.api_key')[1];
        }

        return base64_encode($user_key . ":" . $api_key);
    }

    public function momoLoginToken($target)
    {
        $encoded = self::getEncodedBasicString($target);
        $endpoint = config('momoapi.endpoints.token_uri');
        $subscription_key = "";

        if ($target == "collection") {
            $subscription_key = config('momoapi.headers.token.ocp_apim_subscription_key')[0];
            $endpoint = str_replace(":target", "collection", $endpoint);
        }
        if ($target == "disbursement") {
            $subscription_key = config('momoapi.headers.token.ocp_apim_subscription_key')[1];
            $endpoint = str_replace(":target", "disbursement", $endpoint);
        }

        $headers = [
            "Authorization" => "Basic " . $encoded,
            "Ocp-Apim-Subscription-Key" => $subscription_key,
            "Content-Type" => "application/json"
        ];

        $response = Http::withHeaders($headers)->post($endpoint);
        // return $response->body();
        if ($response->status() == 200) {
            $data = json_decode($response->body());
            $token = $data->access_token;
            return $token;
        }
        Log::channel("momoapi")->debug(json_decode($response->body()));
        return null;
    }

    public function collection()
    {
        $target = "collection";
        $token = self::momoLoginToken($target);

        if ($token == null) {
            Log::channel("momoapi")->error("erreur lors de la récupération du token");
            return ["code" => "180", "msg" => "Une erreur est survenue lors du traitement"];
        }

        $uuid = Uuid::generate(4)->string;
        $subscription_key = config('momoapi.headers.token.ocp_apim_subscription_key')[0];


        $headers = [
            "Authorization" => "Bearer " . $token,
            "Ocp-Apim-Subscription-Key" => $subscription_key,
            "Content-Type" => "application/json",
            "X-Reference-Id" => $uuid,
            "X-Target-Environment" => config('momoapi.headers.token.x_target_environment'),
            "Accept" => "application/json"
            // "X-Callback-Url" => config("momoapi.headers.token.x_callback_url")
        ];

        $transid = rand(10000000, 999999999);

        $params = [
            "amount" => 50,
            "currency" => "XAF",
            "externalId" => $transid,
            "payer" => [
                "partyIdType" => "MSISDN",
                "partyId" => "242067230202"
            ],
            "payerMessage" => "Recharge Nokipay",
            "payeeNote" => "Momo_NOKIPAY"
        ];

        $endpoint = config('momoapi.endpoints.pay_uri');

        $endpoint = str_replace(":target", "collection", $endpoint);
        $endpoint = str_replace(":action", "requesttopay", $endpoint);


        $response = Http::asJson()->withHeaders($headers)->post($endpoint, $params);

        if ($response->status() >= 200 && $response->status() < 210) {
            Log::channel("momoapi")->debug("Success : " . $response->status() . " | Corps : " . $response->body());
            return "Tout s'est bien passé";
        }

        Log::channel("momoapi")->error("Erreur : " . $response->status() . " | Corps : " . $response->body());
        return "Une erreur est survenue lors du traitement";
    }


    public function disbursement()
    {
        $target = "disbursement";
        $token = self::momoLoginToken($target);

        if ($token == null) {
            Log::channel("momoapi")->error("erreur lors de la récupération du token");
            return ["code" => "180", "msg" => "Une erreur est survenue lors du traitement"];
        }

        $uuid = Uuid::generate(4)->string;
        $subscription_key = config('momoapi.headers.token.ocp_apim_subscription_key')[1];

        $headers = [
            "Authorization" => "Bearer " . $token,
            "Ocp-Apim-Subscription-Key" => $subscription_key,
            "Content-Type" => "application/json",
            "X-Reference-Id" => $uuid,
            "X-Target-Environment" => config('momoapi.headers.token.x_target_environment'),
            "Accept" => "application/json"
            // "X-Callback-Url" => config("momoapi.headers.token.x_callback_url")
        ];

        $transid = rand(10000000, 999999999);


        $params = [
            "amount" => 100,
            "currency" => "XAF",
            "externalId" => $transid,
            "payee" => [
                "partyIdType" => "MSISDN",
                "partyId" => "242067230202"
            ],
            "payerMessage" => "Cash Nokipay",
            "payeeNote" => "Momo_NOKIPAY"
        ];


        $endpoint = config('momoapi.endpoints.pay_uri');

        $endpoint = str_replace(":target", "disbursement", $endpoint);
        $endpoint = str_replace(":action", "deposit", $endpoint);

        $response = Http::asJson()->withHeaders($headers)->post($endpoint, $params);

        if ($response->status() >= 200 && $response->status() < 210) {
            Log::channel("momoapi")->debug("Success : " . $response->status() . " | Corps : " . $response->body());
            return "Tout s'est bien passé";
        }

        Log::channel("momoapi")->error("Erreur : " . $response->status() . " | Corps : " . $response->body());
        return "Une erreur est survenue lors du traitement";
    }
}
