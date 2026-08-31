<?php

namespace App\Http\Controllers;

use App\Classes\FirebaseService;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushNotificationController extends Controller
{
    public function storeToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:4096'],
        ]);

        $request->user()->forceFill([
            'fcm_token' => $validated['token'],
        ])->save();

        return response()->json([
            'message' => 'Token saved',
        ]);
    }

    public function send(FirebaseService $firebase): JsonResponse
    {
        $sent = 0;
        $skipped = 0;

        User::query()
            ->whereNotNull('fcm_token')
            ->cursor()
            ->each(function (User $user) use ($firebase, &$sent, &$skipped): void {
                $wasSent = $firebase->sendToUser(
                    $user,
                    'SBC Alumni Link',
                    'New announcement!',
                    route('home'),
                    ['kind' => 'test_notification']
                );

                $wasSent ? $sent++ : $skipped++;
            });

        return response()->json([
            'message' => 'Notification dispatch finished.',
            'sent' => $sent,
            'skipped' => $skipped,
        ]);
    }
}
