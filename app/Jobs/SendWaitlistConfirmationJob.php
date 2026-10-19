<?php

namespace App\Jobs;

use App\Mail\WaitlistConfirmationMail;
use App\Models\WaitlistEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendWaitlistConfirmationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public WaitlistEntry $waitlistEntry
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->waitlistEntry->email)->send(
            new WaitlistConfirmationMail($this->waitlistEntry)
        );

        Log::info("Waitlist confirmation email sent to: {$this->waitlistEntry->email}");
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error("Failed to send waitlist confirmation to {$this->waitlistEntry->email}: " . ($exception ? $exception->getMessage() : 'Unknown error'));
    }
}
