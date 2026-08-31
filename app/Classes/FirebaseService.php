<?php

namespace App\Classes;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use RuntimeException;
use Throwable;

class FirebaseService
{
    protected ?Messaging $messaging = null;

    protected function messaging(): Messaging
    {
        if ($this->messaging) {
            return $this->messaging;
        }

        $credentials = env('FIREBASE_CREDENTIALS');

        if (! $credentials || ! is_file(base_path($credentials))) {
            throw new RuntimeException('Firebase service account file is missing. Check FIREBASE_CREDENTIALS in .env.');
        }

        $factory = (new Factory)
            ->withServiceAccount(base_path($credentials));

        return $this->messaging = $factory->createMessaging();
    }

    /**
     * @param  array<string, string|int|float|bool|null>  $data
     */
    public function sendNotification(?string $token, string $title, string $body, ?string $url = null, array $data = []): mixed
    {
        if (! filled($token)) {
            return null;
        }

        $payloadData = array_filter([
            'title' => $title,
            'body' => $body,
            'url' => $url,
            ...$data,
        ], fn ($value): bool => $value !== null);

        $message = CloudMessage::withTarget('token', $token)
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData(array_map('strval', $payloadData))
            ->withWebPushConfig([
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'icon' => asset('icons/icon-192.png'),
                    'badge' => asset('icons/icon-192.png'),
                ],
                'fcm_options' => array_filter([
                    'link' => $url,
                ]),
            ]);

        return $this->messaging()->send($message);
    }

    /**
     * @param  array<string, string|int|float|bool|null>  $data
     */
    public function sendToUser(User $user, string $title, string $body, ?string $url = null, array $data = []): bool
    {
        if (! filled($user->fcm_token)) {
            return false;
        }

        try {
            $this->sendNotification($user->fcm_token, $title, $body, $url, $data);

            return true;
        } catch (Throwable $exception) {
            Log::warning('Failed to send Firebase push notification.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'exception' => $exception,
            ]);

            return false;
        }
    }
}
