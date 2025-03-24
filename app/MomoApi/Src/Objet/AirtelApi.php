<?php

namespace App\MomoApi\Src\Objet;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class AirtelApi
{
    public function collection($phone, $amount)
    {
        $endpoint = "https://www.tstcgb.com/postswitch/epay000.php";
        $data = [
            "merchantID" => "<MERCHANT_ID>",
            "merchantPWD" => "<MERCHANT_PASS>",
            "transID" => rand(10000000, 999999999),
            "amount" => $amount,
            "action" => "getID",
            "msisdn" => "242" . $phone,
            "callbackUrl" => "<URL_CALLBACK>"
        ];
        $response = Http::asForm()->post($endpoint, $data);
        if ($response->status() == 200) {
            $body = (string) $response->body();
            return ["code" => "200", "msg" => "Votre transaction va être traitée", "data"=>$body];
        }
        Log::channel("momoapi")->info("Paiement Airtel Money " . $response->body());
        return ["code" => "189", "msg" => "La transaction ne peut pas être traitée"];
    }
}
