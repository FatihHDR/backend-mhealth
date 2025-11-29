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
            ChatActivity::create([
                'title' => isset($this->session['title']) ? (string) $this->session['title'] : '',
                'chat_activity_data' => $this->session,
                'public_id' => $this->publicId,
                'user_id' => $this->userId,
            ]);
        } catch (\Throwable $e) {
            Log::error('SaveChatActivity job failed', ['error' => $e->getMessage(), 'session' => $this->session]);
        }
    }
}
