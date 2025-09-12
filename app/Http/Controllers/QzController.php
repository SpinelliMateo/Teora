<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QzController extends Controller
{
    public function sign(Request $request)
    {
        $toSign = $request->input('toSign');
        if (!$toSign) {
            return response()->json(['error' => 'toSign is required'], 400);
        }

        try {
            $path = 'qz/private-key.pem';

            if (!Storage::exists($path)) {
                return response()->json(['error' => 'private key not found'], 500);
            }

            $privatePem = Storage::get($path);
            $pkey = openssl_pkey_get_private($privatePem);
            if (!$pkey) {
                return response()->json(['error' => 'invalid private key'], 500);
            }

            // Use SHA256 by default; change to SHA512 if desired
            openssl_sign($toSign, $signature, $pkey, OPENSSL_ALGO_SHA256);
            openssl_free_key($pkey);

            return response()->json(['signature' => base64_encode($signature)]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
