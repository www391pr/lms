<?php


namespace App\Services;

use App\Models\FcmToken;
use App\Models\User;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FcmService
{
    private Messaging $messaging;

    // Laravel will inject the Messaging instance automatically
    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    /**
     * Send to all devices of a single user.
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): int
    {
        $tokens = $user->fcmTokens()->where('active', true)->pluck('token')->all();

        if (empty($tokens)) {
            return 0;
        }

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * Send to raw array of tokens.
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): int
    {
        $notification = Notification::create($title, $body);

        $message = CloudMessage::new()
            ->withNotification($notification)
            ->withData($this->stringifyValues($data));

        $report = $this->messaging->sendMulticast($message, $tokens);

        // disable invalid tokens
        foreach ($report->failures()->getItems() as $failure) {
            $token = $failure->target()->value();
            FcmToken::where('token', $token)->update(['active' => false]);
        }

        return $report->successes()->count();
    }

    /**
     * Send to a Firebase topic.
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): void
    {
        $notification = Notification::create($title, $body);

        $message = CloudMessage::new()
            ->withNotification($notification)
            ->withData($this->stringifyValues($data))
            ->withChangedTarget('topic', $topic);

        $this->messaging->send($message);
    }

    private function stringifyValues(array $data): array
    {
        return collect($data)->map(fn($v) => is_scalar($v) ? (string)$v : json_encode($v))->all();
    }
}
