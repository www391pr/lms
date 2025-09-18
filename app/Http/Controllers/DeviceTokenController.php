<?php

// app/Http/Controllers/DeviceTokenController.php
namespace App\Http\Controllers;

use App\Models\FcmToken;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'token'       => ['required','string','max:1024'],
            'device_type' => ['nullable', Rule::in(['android','ios','web'])],
            'device_id'   => ['nullable','string','max:255'],
            'app_version' => ['nullable','string','max:50'],
        ]);

        $user = $request->user();

        // upsert by token (no duplicates)
        $token = FcmToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id'     => $user->id,
                'device_type' => $data['device_type'] ?? null,
                'device_id'   => $data['device_id'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'active'      => true,
                'last_used_at'=> now(),
            ]
        );

        return response()->json([
            'message' => 'Device token registered',
            'token'   => $token,
        ], 201);
    }

    public function destroy(Request $request, string $token)
    {
        $deleted = FcmToken::where('token', $token)
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json([
            'message' => $deleted ? 'Device token removed' : 'Token not found',
        ]);
    }

    // quick sanity check endpoint
    public function sendTest(Request $request, FcmService $fcm)
    {
        $request->validate([
            'title' => 'required|string|max:120',
            'body'  => 'required|string|max:220',
            'data'  => 'array',
        ]);

        $user = $request->user();
        $count = $fcm->sendToUser(
            user: $user,
            title: $request->string('title'),
            body:  $request->string('body'),
            data:  $request->input('data', [])
        );

        return response()->json([
            'message' => "Sent to {$count} device(s)",
        ]);
    }
}
