<?php

namespace Alphasky\Api\Services;

use Alphasky\Api\Models\DeviceToken;
use Alphasky\Api\Models\PushNotification;
use Alphasky\Api\Models\PushNotificationRecipient;
use Carbon\Carbon;
use Exception;
use Kreait\Firebase\Exception\Messaging\InvalidMessage;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    public function sendToAll(array $notification): array
    {
        $tokens = DeviceToken::query()->active()->get();

        if ($tokens->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No active device tokens found',
                'sent_count' => 0,
                'failed_count' => 0,
            ];
        }

        // Save notification to database
        $pushNotification = $this->saveNotificationToDatabase($notification, 'all');

        return $this->sendToDeviceTokens($tokens, $notification, $pushNotification);
    }

    public function sendToPlatform(string $platform, array $notification): array
    {
        $tokens = DeviceToken::query()
            ->active()
            ->forPlatform($platform)
            ->get();

        if ($tokens->isEmpty()) {
            return [
                'success' => false,
                'message' => "No active {$platform} device tokens found",
                'sent_count' => 0,
                'failed_count' => 0,
            ];
        }

        // Save notification to database
        $pushNotification = $this->saveNotificationToDatabase($notification, 'platform', $platform);

        return $this->sendToDeviceTokens($tokens, $notification, $pushNotification);
    }

    public function sendToUserType(string $userType, array $notification): array
    {
        $tokens = DeviceToken::query()
            ->active()
            ->where('user_type', $userType)
            ->get();

        if ($tokens->isEmpty()) {
            return [
                'success' => false,
                'message' => "No active device tokens found for user type: {$userType}",
                'sent_count' => 0,
                'failed_count' => 0,
            ];
        }

        // Save notification to database
        $pushNotification = $this->saveNotificationToDatabase($notification, 'user_type', $userType);

        return $this->sendToDeviceTokens($tokens, $notification, $pushNotification);
    }

    public function sendToUser(string $userType, int $userId, array $notification): array
    {
        $tokens = DeviceToken::query()
            ->active()
            ->forUser($userType, $userId)
            ->get();

        if ($tokens->isEmpty()) {
            return [
                'success' => false,
                'message' => "No active device tokens found for user: {$userType}#{$userId}",
                'sent_count' => 0,
                'failed_count' => 0,
            ];
        }

        // Save notification to database
        $pushNotification = $this->saveNotificationToDatabase($notification, 'user', $userId);

        return $this->sendToDeviceTokens($tokens, $notification, $pushNotification);
    }

    public function sendToDeviceTokens($deviceTokens, array $notification, PushNotification $pushNotification): array
    {
        $tokens = $deviceTokens->pluck('token')->toArray();

        if (! app()->bound('firebase.messaging')) {
            $pushNotification->markAsFailed('FCM configuration is incomplete');

            return [
                'success' => false,
                'message' => 'Firebase messaging service is not configured',
                'sent_count' => 0,
                'failed_count' => count($tokens),
            ];
        }

        // Create recipient records for tracking
        foreach ($deviceTokens as $deviceToken) {
            PushNotificationRecipient::createForUser(
                $pushNotification->id,
                $deviceToken->user_type ?? 'unknown',
                $deviceToken->user_id ?? 0,
                $deviceToken->token,
                $deviceToken->platform
            );
        }

        $sentCount = 0;
        $failedCount = 0;
        $invalidTokens = [];

        foreach ($deviceTokens as $deviceToken) {
            $result = $this->sendToSingleToken($deviceToken->token, $notification, $deviceToken, $pushNotification);
            if ($result['success']) {
                $sentCount++;
            } else {
                $failedCount++;
                if ($result['invalid_token']) {
                    $invalidTokens[] = $deviceToken->token;
                }
            }
        }

        // Remove invalid tokens
        if (! empty($invalidTokens)) {
            DeviceToken::query()->whereIn('token', $invalidTokens)->delete();
        }

        // Update notification status
        $pushNotification->markAsSent($sentCount, $failedCount);

        return [
            'success' => $sentCount > 0,
            'message' => $sentCount > 0
                ? "Successfully sent to {$sentCount} devices"
                : 'Failed to send to any devices',
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'removed_invalid_tokens' => count($invalidTokens),
        ];
    }

    protected function sendToSingleToken(string $token, array $notification, $deviceToken = null, ?PushNotification $pushNotification = null): array
    {
        try {
            /** @var \Kreait\Firebase\Contract\Messaging $messaging */
            $messaging = app('firebase.messaging');

            $message = $this->buildFirebaseMessage($token, $notification);
            $response = $messaging->send($message);

            if ($deviceToken && $pushNotification) {
                $recipient = PushNotificationRecipient::query()
                    ->where('push_notification_id', $pushNotification->id)
                    ->where('device_token', $token)
                    ->first();

                if ($recipient) {
                    $recipient->update([
                        'status' => 'delivered',
                        'delivered_at' => Carbon::now(),
                        'fcm_response' => $response,
                    ]);
                }
            }

            return [
                'success' => true,
                'invalid_token' => false,
            ];
        } catch (NotFound|InvalidMessage $e) {
            if ($deviceToken && $pushNotification) {
                $recipient = PushNotificationRecipient::query()
                    ->where('push_notification_id', $pushNotification->id)
                    ->where('device_token', $token)
                    ->first();

                if ($recipient) {
                    $recipient->markAsFailed($e->getMessage(), $e->errors() ?? []);
                }
            }

            Log::warning('Firebase token is invalid or unknown', [
                'token' => substr($token, 0, 20) . '...',
                'notification' => $notification,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'invalid_token' => true,
            ];
        } catch (Exception $e) {
            if ($deviceToken && $pushNotification) {
                $recipient = PushNotificationRecipient::query()
                    ->where('push_notification_id', $pushNotification->id)
                    ->where('device_token', $token)
                    ->first();

                if ($recipient) {
                    $recipient->markAsFailed($e->getMessage(), []);
                }
            }

            Log::error('Firebase send error: ' . $e->getMessage(), [
                'token' => substr($token, 0, 20) . '...',
                'notification' => $notification,
            ]);

            return [
                'success' => false,
                'invalid_token' => false,
            ];
        }
    }

    protected function buildFirebaseMessage(string $token, array $notification): CloudMessage
    {
        $apnsPayload = [
            'payload' => [
                'aps' => [
                    'sound' => 'default',
                    'badge' => 1,
                ],
            ],
        ];

        if (! empty($notification['action_url'])) {
            $apnsPayload['payload']['aps']['category'] = 'OPEN_URL';
        }

        $message = CloudMessage::new()
            ->withNotification(
                empty($notification['image_url'])
                    ? Notification::create($notification['title'], $notification['message'])
                    : Notification::create($notification['title'], $notification['message'])->withImageUrl($notification['image_url'])
            )
            ->withData([
                'title' => (string) $notification['title'],
                'message' => (string) $notification['message'],
                'action_url' => (string) ($notification['action_url'] ?? ''),
                'image_url' => (string) ($notification['image_url'] ?? ''),
                'type' => (string) ($notification['type'] ?? 'general'),
                'sent_at' => Carbon::now()->toISOString(),
            ])
            ->withToken($token)
            ->withAndroidConfig(AndroidConfig::fromArray([
                'priority' => 'high',
                'notification' => [
                    'click_action' => (string) ($notification['action_url'] ?? ''),
                    'sound' => 'default',
                ],
            ]))
            ->withApnsConfig(ApnsConfig::fromArray($apnsPayload));

        return $message;
    }

    public function validateNotification(array $notification): array
    {
        $errors = [];

        if (empty($notification['title'])) {
            $errors[] = 'Title is required';
        } elseif (strlen($notification['title']) > 100) {
            $errors[] = 'Title must not exceed 100 characters';
        }

        if (empty($notification['message'])) {
            $errors[] = 'Message is required';
        } elseif (strlen($notification['message']) > 500) {
            $errors[] = 'Message must not exceed 500 characters';
        }

        if (! empty($notification['action_url']) && ! filter_var($notification['action_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Action URL must be a valid URL';
        }

        if (! empty($notification['image_url']) && ! filter_var($notification['image_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Image URL must be a valid URL';
        }

        return $errors;
    }

    public function getDeviceTokensCount(): array
    {
        return [
            'total' => DeviceToken::query()->active()->count(),
            'android' => DeviceToken::query()->active()->forPlatform('android')->count(),
            'ios' => DeviceToken::query()->active()->forPlatform('ios')->count(),
            'members' => DeviceToken::query()->active()->where('user_type', 'member')->count(),
        ];
    }

    protected function saveNotificationToDatabase(array $notification, string $targetType, ?string $targetValue = null): PushNotification
    {
        return PushNotification::createFromRequest([
            'title' => $notification['title'],
            'message' => $notification['message'],
            'type' => $notification['type'] ?? 'general',
            'target_type' => $targetType,
            'target_value' => $targetValue,
            'action_url' => $notification['action_url'] ?? null,
            'image_url' => $notification['image_url'] ?? null,
            'data' => $notification['data'] ?? null,
        ], auth()->id());
    }
}
