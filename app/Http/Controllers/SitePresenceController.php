<?php

namespace App\Http\Controllers;

use App\Services\SitePresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SitePresenceController extends Controller
{
    public function ping(Request $request, SitePresenceService $presence): JsonResponse
    {
        $visitorId = trim((string) $request->input('visitor_id', ''));
        if ($visitorId === '' || strlen($visitorId) > 128) {
            $visitorId = (string) $request->session()->getId();
        }

        $presence->touch($visitorId);

        return response()->json(['success' => true]);
    }
}
