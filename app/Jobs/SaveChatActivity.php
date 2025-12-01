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
            // Determine target session to update or create
            $target = null;

            // If session includes an id, try primary key then public_id lookup
            $incomingId = $this->session['id'] ?? null;
            if (! empty($incomingId)) {
                $target = ChatActivity::find($incomingId);
                if (! $target) {
                    $target = ChatActivity::where('public_id', $incomingId)->first();
                }
            }

            // Fallback: if job has publicId (generated for anonymous sessions), try to find it
            if (! $target && ! empty($this->publicId)) {
                $target = ChatActivity::where('public_id', $this->publicId)->first();
            }

            // If user is authenticated and we still didn't find a session, do NOT auto-attach
            // to an arbitrary previous session — create a new one instead. The frontend should
            // provide session id/public_id when it's continuing an existing session.

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
                // Create new session row (this happens when frontend indicates a new session)
                ChatActivity::create([
                    'title' => isset($this->session['title']) ? (string) $this->session['title'] : '',
                    'chat_activity_data' => $this->session,
                    'public_id' => $this->publicId,
                    'user_id' => $this->userId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('SaveChatActivity job failed', ['error' => $e->getMessage(), 'session' => $this->session]);
        }
    }
}
