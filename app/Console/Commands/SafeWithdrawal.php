<?php

namespace App\Console\Commands;

use Exception;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\Wallet;
use App\Models\SystemConfig;
use App\Models\WalletTransaction;
use App\Services\FCMService;
use App\Services\SafeHavenService;

class SafeWithdrawal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'safewithdrawal:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update safe withdrawal and transfer statuses from SafeHaven API';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Implement lock to prevent overlap
        if ($this->lockExists()) {
            Log::info('Withdrawal update job is already running. Exiting.');
            return 0;
        }

        $this->createLock();

        try {
            Log::info("Starting withdrawal and transfer updates");

            $this->updateWithdrawals();
            $this->updateTransfers();

            Log::info("Withdrawal and transfer updates completed");
        } catch (Exception $e) {
            Log::error('Error during withdrawal and transfer updates: ' . $e->getMessage());
        } finally {
            $this->removeLock();
        }

        return 0;
    }

    private function updateWithdrawals()
    {
        $oneHourAgo = Carbon::now()->subHour(360);
        // $pendingTrxs = WalletTransactions::where("trx_status", "pending")->where("status", 4)->get()->sortBy('created_at');
        $pendingTrxs = WalletTransaction::whereRaw('LOWER(trx_status) IN (?, ?)', ['pending', 'processing'])->where('status', 4)->where('created_at', '>=', $oneHourAgo)->whereIn('server', ['safehaven', 'safe_haven', 'savehaven', 'save_haven'])->get()->sortBy('created_at');



        foreach ($pendingTrxs as $trx) {
            $response = $this->checkTransactionStatus($trx->transaction_id);

            $this->processTransactionResponse($trx, $response, 'withdrawal', 'trx_status');
        }
    }

    private function checkTransactionStatus($transactionId)
    {
        $body = [
            "paymentReference" => $transactionId
        ];

        SafeHavenService::refreshAccess();
        $refresh = SystemConfig::where('name', 'safehaven_refresh')->first();
        $token = SystemConfig::where('name', 'safehaven_token')->first();
        $assertion = SystemConfig::where('name', 'safehaven_assertion')->first();

        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token->value, 'Content-Type' => 'application/json', 'ClientID' => env('SAFEHAVEN_ID')])->post(env("SAFEHAVEN_BASE_URL") . '/transfers', $body);


        $request = env("SAFEHAVEN_BASE_URL") . '/transfers/status';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token->value,
            'ClientID' => env('SAFEHAVEN_ID'),
            'Content-Type' => 'application/json'
        ])->post($request, $body);


        Log::channel('haven_log')->info($response);
        // Log::info($response);

        return $response->json();
    }

    private function processTransactionResponse($trx, $data, $type, $statusField)
    {
        Log::info("here");
        if ($data['statusCode'] == 200 && isset($data['data']) && isset($data['data']['isReversed']) && isset($data['message']) && isset($data['data']['responseMessage']) && isset($data['data']['status']) && isset($data['data']['responseCode'])) {
            Log::info("start");
            $status = ($data['data']['isReversed']) || ($data['responseCode'] == '00') || ($data['data']['responseMessage'] === "Format error" && $data['data']['status'] === "Failed" && $data['data']['responseCode'] === "30") ? strtolower($data['message']) : "Processing";

            $trx->update([
                "status" => $data['data']['isReversed'] || ($data['data']['responseMessage'] === "Format error" && $data['data']['status'] === "Failed" && $data['data']['responseCode'] === "30") ? 2 : ($data['responseCode'] == '00' ? 1 : 4),
                $statusField => $status
            ]);

            if ($data['data']['isReversed'] || ($data['data']['responseMessage'] === "Format error" && $data['data']['status'] === "Failed" && $data['data']['responseCode'] === "30")) {
                $wallet = Wallet::where("user_id", $trx->user_id)->first();
                $wallet->update([
                    'balance' =>  $wallet->balance + $trx->amount + ($trx->charge ?? 0),
                ]);


                $this->notifyUsers($trx->user_id, $trx->amount, $type);
            }
        }
        // else if(isset($data['message']) && isset($data['responseCode']) && isset($data['statusCode']) &&  $data['message'] === "Unable to locate record" && $data['responseCode'] === "25" && $data['statusCode'] === 400  ){
        //         $trx->update([
        //             "status" => 2 ,
        //             $statusField => $data['message']
        //         ]);

        //         $wallet = Wallet::where("user_id", $trx->user_id)->first();
        //         $wallet->update([
        //           'balance' =>  $wallet->balance + $trx->amount + ($trx->charge ?? 0),
        //         ]);
        //     }
    }

    private function notifyUsers($userId, $amount, $type)
    {
        $user = User::where("id", $userId)->first();
        $title = ucfirst($type) . ' Reversed';
        $body = "There is a new {$type} reversal of NGN{$amount} to a SpaceTrade user";

        FCMService::sendToAdmins([
            'title' => $title,
            'body' => $body,
        ]);

        FCMService::sendToID(
            $user->id,
            [
                'title' => 'Transaction Failed',
                'body' => "Your {$type} has failed and your funds of NGN{$amount} have been reversed. Apologies for this inconvenience. Please check the app for more details.",
            ]
        );
    }

    private function lockExists()
    {
        return cache()->has('withdrawal-update-lock');
    }

    private function createLock()
    {
        cache()->put('withdrawal-update-lock', true, 3600); // Lock for 1 hour
    }

    private function removeLock()
    {
        cache()->forget('withdrawal-update-lock');
    }
}
