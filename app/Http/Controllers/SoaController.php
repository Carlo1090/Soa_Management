<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use Illuminate\Support\Facades\Log;
use App\Jobs\SoaDelay;

class SoaController extends Controller
{
    public function soaGeneration()
    {
        $accounts = $this->getAccountsForSOA();

        return view('soa.index', [
            'accounts' => $accounts
        ]);
    }

    public function generateAllSOAs()
    {
        $accounts = $this->getAccountsForSOA();

        //for delay
        $delaySeconds = 0;

        foreach ($accounts as $account) {
            Log::info("Queueing SOA for Account ID: {$account->id}");

            // Dispatch SoaDelay job with progressive delay
            SoaDelay::dispatch($account)
                ->delay(now()->addSeconds($delaySeconds));

            //implement random delays
            $delaySeconds += rand(5, 10);
        }

        return redirect()
            ->route('soa.index')
            ->with('status', count($accounts) . ' SOA jobs queued with 5-10 second delays.');
    }

    private function getAccountsForSOA()
    {
        return Account::whereDay('start_date', 20)->get();
        // return Account::whereDay('start_date', \Carbon\Carbon::now()->addDays(10)->day)->get();
    }
}



// namespace App\Http\Controllers;

// use Illuminate\Http\Request;
// use App\Models\Account;
// use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Facades\Mail;

// class SoaController extends Controller
// {
//     public function soaGeneration()
//     {
//         $accounts = $this->getAccountsForSOA();

//         return view('soa.index', [
//             'accounts' => $accounts
//         ]);
//     }

//     public function generateAllSOAs()
//     {
//         $accounts = $this->getAccountsForSOA();

//         // Initialize delay counter
//         $delaySeconds = 0;

//         foreach ($accounts as $account) {
//             Log::info("Generating SOA for Account ID: {$account->id}, Account Number: {$account->account_number}");

//             $mail = new \App\Mail\SoaManagement($account);

//             // Using `Mail::later` with progressive delays
//             // First email: 0 seconds delay
//             // Second email: 5-10 seconds delay
//             // Third email: 10-20 seconds delay, etc.
//             Mail::to($account->customer->email)
//                 ->later(now()->addSeconds($delaySeconds), $mail);

//             Log::info("Email scheduled with {$delaySeconds} seconds delay");

//             // Add random delay between 5-10 seconds for next email
//             $delaySeconds += rand(5, 10);
//         }

//         return redirect()
//             ->route('soa.index')
//             ->with('status', count($accounts) . ' SOAs have been queued with 5-10 second delays between each.');
//     }

//     private function getAccountsForSOA()
//     {
//         // Test with fixed day 20
//         return Account::whereDay('start_date', 20)->get();

//         // Or use dynamic 10 days from now (comment/uncomment as needed)
//         // return Account::whereDay('start_date', \Carbon\Carbon::now()->addDays(10)->day)->get();
//     }
// }
