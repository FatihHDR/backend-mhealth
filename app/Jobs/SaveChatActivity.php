<?php

namespace App\Jobs;

use App\Models\ChatActivity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SaveChatActivity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $session;
    public ?string $userId;
    public ?string $publicId;

    /**
     * Create a new job instance.
     */
    public function __construct(array $session, ?string $userId = null, ?string $publicId = null)
    {
        $this->session = $session;
        $this->userId = $userId;
        $this->publicId = $publicId;
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Log session data untuk debugging replyTo
            Log::debug('SaveChatActivity job started', [
                'session_id' => $this->session['id'] ?? null,
                'message_count' => count($this->session['messages'] ?? []),
                'last_message_replyTo' => isset($this->session['messages']) && count($this->session['messages']) >= 2
                    ? $this->session['messages'][count($this->session['messages']) - 2]['replyTo'] ?? null
                    : null,
            ]);

            // Determine target session to update or create
            $target = null;

            // session['id'] is the session_id (primary key in chat_activity table)
            $sessionId = $this->session['id'] ?? null;

            // Only find by session_id (primary key) - no fallback to public_id
            // This ensures new sessions are created when FE wants a new chat
            if (! empty($sessionId)) {
                $target = ChatActivity::find($sessionId);
            }

            // NOTE: We intentionally do NOT fallback to public_id lookup
            // The session_id must match for us to update an existing session

            if ($target) {
                // Update existing session's chat_activity_data and title
                $target->chat_activity_data = $this->session;
                if (isset($this->session['title'])) {
                    $target->title = (string) $this->session['title'];
                }
                // ensure public_id / user_id set
                if ($this->publicId) {
                    $target->public_id = $this->publicId;
                }
                if ($this->userId) {
                    $target->user_id = $this->userId;
                }
                $target->save();
            } else {
                // Create new session row with session_id as the primary key
                $sessionId = $this->session['id'] ?? null;
                $newSession = new ChatActivity();
                
                // Set the primary key explicitly if provided
                if ($sessionId) {
                    $newSession->id = $sessionId;
                }
                
                $newSession->title = isset($this->session['title']) ? (string) $this->session['title'] : '';
                $newSession->chat_activity_data = $this->session;
                $newSession->public_id = $this->publicId;
                $newSession->user_id = $this->userId;
                $newSession->share_slug = \Illuminate\Support\Str::random(16);
                $newSession->save();
            }
        } catch (\Throwable $e) {
            Log::error('SaveChatActivity job failed', ['error' => $e->getMessage(), 'session' => $this->session]);
        }
    }
}
