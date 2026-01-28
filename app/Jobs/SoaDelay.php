<?php
namespace App\Jobs;

use App\Mail\SoaManagement;
use App\Models\Account;
use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;

class SoaDelay implements ShouldQueue
{
    use Queueable, SerializesModels, InteractsWithQueue;

    /**
     * The Account model instance.
     * Using SerializesModels trait allows Laravel to automatically
     * serialize the model when queueing and re-retrieve it from the database
     * when the job is processed.
     */
    protected $account;

    /**
     * The number of times the job may be attempted.
     * If the job fails, it will automatically retry up to 3 times.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     * Exponential backoff: 10 seconds, then 30 seconds, then 60 seconds.
     * This prevents immediate retries when there are temporary issues.
     */
    public $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     *
     * @param Account $account - The account model instance
     * We pass the entire Account model instead of just the ID for convenience.
     */
    public function __construct(Account $account)
    {
        $this->account = $account;
    }

    /**
     * Execute the job.
     * This method is automatically called by Laravel's queue worker
     * when it's time to process this job.
     *
     * @return void
     */
    public function handle(): void
    {
        // Eager load the customer relationship if not already loaded
        // This prevents an additional database query when accessing $this->account->customer
        $this->account->loadMissing('customer');

        // Validate that the account has a customer with an email
        if (!$this->account->customer || !$this->account->customer->email) {
            // Mark the job as failed if validation fails
            $this->fail(new \Exception("Invalid customer or email for account: {$this->account->id}"));
            return;
        }

        // Send the SOA email
        Mail::to($this->account->customer->email)
            ->send(new SoaManagement($this->account));
    }

    /**
     * Handle a job failure.
     * This method is automatically called if the job fails after all retry attempts.
     *
     * @param \Throwable $exception - The exception that caused the failure
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        // This is where you could add failure handling logic
        // For example: send a notification to admin, mark the account for manual review, etc.
    }
}
