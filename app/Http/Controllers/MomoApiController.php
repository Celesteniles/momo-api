<?php

namespace App\Http\Controllers;

use App\MomoApi\Src\Facades\MomoApi;
use Webpatser\Uuid\Uuid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class MomoApiController extends Controller
{
    public function collection(Request $request)
    {
        $rules = [
            "phone" => ["required", "string", "digits:12"],
            "amount" => ["required", "numeric"]
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()){
            return response()->json([
                "message" => "Erreur de validation",
                "errors" => collect($validator->errors())->flatten()
            ], 500);
        }

        try{
            $phone = $request->phone;
            $amount = $request->amount;

            MomoApi::collection($phone, $amount);

            return response()->json([
                "message" => "Votre demande va être traité !"
            ]);
        } catch(\Exception $e){
            return response()->json([
                "message" => "Quelque chose ne s'est pas bien passée."
            ], 500);
        }
    }

    public function disbursement(Request $request)
    {
        $rules = [
            "phone" => ["required", "string", "digits:12"],
            "amount" => ["required", "numeric"]
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()){
            return response()->json([
                "message" => "Erreur de validation",
                "errors" => collect($validator->errors())->flatten()
            ], 500);
        }

        try{
            $phone = $request->phone;
            $amount = $request->amount;

            MomoApi::disbursement($phone, $amount);

            return response()->json([
                "message" => "Votre demande va être traité !"
            ]);
        } catch(\Exception $e){
            return response()->json([
                "message" => "Quelque chose ne s'est pas bien passée."
            ], 500);
        }
    }
}
