<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SiteBuildCompleted;
use App\Mail\BuildNotification;
use App\Models\Parameter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Listener that sends email notifications when a build completes.
 */
class SendBuildNotification
{
    /**
     * Handle the event.
     */
    public function handle(SiteBuildCompleted $event): void
    {
        $buildLogger = Log::channel('build');

        try {
            $user = $event->history->user;
            $userEmail = $user?->email;

            if (!$userEmail) {
                $buildLogger->info('No user email for build notification', [
                    'site_name' => $event->site->site_name,
                    'history_id' => $event->history->id,
                ]);
                return;
            }

            $devEmail = Parameter::getValue('dev_email', env('DEV_EMAIL', 'dev@example.com'));

            Mail::to($devEmail)->send(new BuildNotification(
                $event->site->site_name,
                $event->status,
                $event->history->created_at->format('Y-m-d H:i:s'),
                $event->history->output_log ?? 'No output available',
                $userEmail
            ));

            $buildLogger->info('Build notification email sent', [
                'site_name' => $event->site->site_name,
                'status' => $event->status,
                'to' => $devEmail,
            ]);
        } catch (\Exception $e) {
            // Log but don't fail - notification is not critical
            $buildLogger->error('Failed to send build notification email', [
                'site_name' => $event->site->site_name,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
