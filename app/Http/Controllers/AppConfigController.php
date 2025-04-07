<?php

namespace App\Http\Controllers;

use App\Models\AppConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppConfigController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function store(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'value' => 'required',
        ]);
        if ($validator->fails()) {
            // get summary
            $errors = $validator->errors();
            $errorMessages = [];

            foreach ($errors->all() as $message) {
                $errorMessages[] = $message;
            }

            $summary = implode(" \n", $errorMessages);

            return response(
                [
                    'error' => true,
                    'message' => $summary
                ],
                422
            );
        }


        $user = auth('api')->user();
        $config = AppConfig::create([
            'name' => $request->name,
            'value' => $request->value,
            'updated_by' => $user->id,
        ]);

        return response(["data" => $config, "message" => "Config Saved Successfully"], 200);
    }

    public function update(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'value' => 'required',
        ]);
        if ($validator->fails()) {
            // get summary
            $errors = $validator->errors();
            $errorMessages = [];

            foreach ($errors->all() as $message) {
                $errorMessages[] = $message;
            }

            $summary = implode(" \n", $errorMessages);

            return response(
                [
                    'error' => true,
                    'message' => $summary
                ],
                422
            );
        }

        $config = AppConfig::where("name", $request->name)->first();

        if (!$config) {
            return response(
                [
                    'error' => true,
                    'message' => "Config not found"
                ],
                422
            );
        }


        $user = auth('api')->user();
        $config = $config->update([
            'value' => $request->value,
            'updated_by' => $user->id,
        ]);

        return response(["data" => AppConfig::all(), "message" => "Config Updated Successfully"], 200);
    }


    public function appVersion()
    {
        $versionInfo = [
            "android" => [
                "version" => [
                    "minimum" => "1.0.0",
                    "latest" => "1.0.0",
                ],
                "download_url" => "https://play.google.com/store/apps/details?id=ng.tupay.tupay",
                "status" => [
                    "active" => true,
                    "message" => [
                        "en" => "Effortless Cross-Border Payments, Right at Your Fingertips.",
                        "es" => "Effortless Cross-Border Payments, Right at Your Fingertips.",
                    ],
                ],
            ],
            "iOS" => [
                "version" => [
                    "minimum" => "1.0.0",
                    "latest" => "1.0.0",
                ],
                "download_url" => "https://apps.apple.com/us/app/tupay/id6741597358?platform=iphone",
                "status" => [
                    "active" => true,
                    "message" => [
                        "en" => "Effortless Cross-Border Payments, Right at Your Fingertips.",
                        "es" => "Effortless Cross-Border Payments, Right at Your Fingertips.",
                    ],
                ],
            ],
            "macOS" => [
                "version" => [
                    "minimum" => "1.0.0",
                    "latest" => "1.0.0",
                ],
                "download_url" => "https://tupay.ng",
                "status" => [
                    "active" => false,
                    "message" => [
                        "en" => "Effortless Cross-Border Payments, Right at Your Fingertips.",
                        "es" => "Effortless Cross-Border Payments, Right at Your Fingertips.",
                    ],
                ],
            ],
            "windows" => [
                "version" => [
                    "minimum" => "1.0.0",
                    "latest" => "1.0.0",
                ],
                "download_url" => "https://tupay.ng",
                "status" => [
                    "active" => true,
                    "message" => [
                        "en" => "Effortless Cross-Border Payments, Right at Your Fingertips.",
                        "es" => "Effortless Cross-Border Payments, Right at Your Fingertips.",
                    ],
                ],
            ],
            "linux" => [
                "version" => [
                    "minimum" => "1.0.0",
                    "latest" => "1.0.0",
                ],
                "download_url" => "https://tupay.ng",
                "status" => [
                    "active" => false,
                    "message" => [
                        "en" => "Effortless Cross-Border Payments, Right at Your Fingertips.",
                        "es" => "Effortless Cross-Border Payments, Right at Your Fingertips.",
                    ],
                ],
            ],
        ];

        return response($versionInfo, 200);
    }
}
