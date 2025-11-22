<?php

// scripts/tinker_reset_link.php
// Usage in Tinker:
// 1) Start tinker: php artisan tinker
// 2) Require this file: require 'scripts/tinker_reset_link.php';
// 3) Call: sendResetLink('user@example.com');

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

if (! function_exists('sendResetLink')) {
    function sendResetLink(string $email)
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            echo "User not found: $email\n";

            return false;
        }

        echo "Sending password reset link to: $email\n";

        $status = Password::sendResetLink(['email' => $email]);

        if ($status === Password::RESET_LINK_SENT) {
            echo "RESET_LINK_SENT. Check mailer / logs.\n";
            Log::info('Tinker: password reset link sent', ['email' => $email, 'user_id' => $user->id]);

            return true;
        }

        echo "Failed to send reset link. Status: $status\n";
        Log::warning('Tinker: failed to send password reset link', ['email' => $email, 'status' => $status]);

        return false;
    }
}

// If $email variable is already set in the tinker environment, auto-run
if (isset($email) && is_string($email)) {
    sendResetLink($email);
}
