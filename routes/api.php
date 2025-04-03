<?php

use Illuminate\Http\Request;
use App\Models\RMBTransaction;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AppConfigController;
use App\Http\Controllers\BankDetailsController;
use App\Http\Controllers\RewardWalletController;
use App\Http\Controllers\SystemConfigController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\RMBTransactionController;
use App\Http\Controllers\SafeSubAccountController;
use App\Http\Controllers\TupaySubAccountController;
use App\Http\Controllers\RMBPaymentMethodController;
use App\Http\Controllers\SafeVerificationController;
use App\Http\Controllers\WalletTransactionController;
use App\Http\Controllers\BettingTransactionController;
use App\Http\Controllers\RMBWalletTransactionController;
use App\Http\Controllers\UtilityBillTransactionController;
use App\Http\Controllers\TupaySubAccountTransactionController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['middleware' => ['cors', 'json.response', 'throttle:ip']], function () {

    // Register
    Route::post('/register', [AuthController::class, 'register']);
    // Login
    Route::post('/login', [AuthController::class, 'login']);
    // email verification code
    Route::post('/resend-email-otp', [AuthController::class, 'resendActivationOtp']);
    // verify email verification code
    Route::post('/verify-email-otp', [AuthController::class, 'verifyEmailOtp']);
    // forgot password
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    // reset password
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // Route::get('/transaction/get/{id}', [RMBTransactionController::class, 'getTransaction']);


    Route::post('/admin-login', [AuthController::class, 'adminLogin']);


    // Safehook

    Route::post('/safe-hook', [TupaySubAccountTransactionController::class, 'webhook']);

    // version
    Route::get('/app/config/version', [AppConfigController::class, 'appVersion']);

    Route::get('/account/update-hook', [SafeSubAccountController::class, 'updateHook']);







    // MIDDLEWARE FOR AUTH APIS
    Route::group(['middleware' => ['auth:api', 'throttle:user']], function () {


        Route::group(['middleware' => ['throttle:money']], function () {

            // RMB
            Route::post('/transaction/create', [RMBTransactionController::class, 'makeTransaction']);

            Route::post('/buy-red-airtime', [UtilityBillTransactionController::class, 'buyVTAirtime']);

            Route::post('/buy-red-data', [UtilityBillTransactionController::class, 'buyVTData']);

            Route::post('/buy-red-cable', [UtilityBillTransactionController::class, 'buyVTCable']);

            Route::post('/buy-red-electricity', [UtilityBillTransactionController::class, 'buyVTElectricity']);

            Route::post('/fund-bet-account', [BettingTransactionController::class, 'fundBettingAccount']);

            Route::post('/withdraw-wallet', [WalletTransactionController::class, 'withdraw']);

            // reward
            Route::get('/rewards/claim', [RewardWalletController::class, 'claim']);

            // rmb-wallet
            Route::post('/rmb-convert', [RMBWalletTransactionController::class, 'convert']);

            Route::post('/rmb-topup', [RMBWalletTransactionController::class, 'topup']);

            // verification
            Route::post('/verification/initiate', [SafeVerificationController::class, 'initiate']);


            // AUTH
            Route::patch('/auth/pin/update', [AuthController::class, 'updatePIN']);
            Route::patch('/auth/pin/reset', [AuthController::class, 'resetPIN']);
        });

        // home
        Route::get('/home', [HomeController::class, 'index']);


        Route::post('/transaction/get', [RMBTransactionController::class, 'getTransaction']);

        // UTILITY
        Route::get('/get-utility-list', [UtilityBillTransactionController::class, 'utilityList']);
        Route::get('/get-tv-list', [UtilityBillTransactionController::class, 'getTvList']);
        Route::get('/get-electric-list', [UtilityBillTransactionController::class, 'getElectricList']);
        Route::get('/get-electric-types', [UtilityBillTransactionController::class, 'getElectricTypes']);
        Route::get('/get-network-list', [UtilityBillTransactionController::class, 'getNetworks']);
        Route::get('/get-data-list/{product}', [UtilityBillTransactionController::class, 'getRedDataList']);

        Route::get('/get-red-data-list/{product}', [UtilityBillTransactionController::class, 'getVTDataList']);

        Route::get('/get-red-tv-list/{product}', [UtilityBillTransactionController::class, 'getVTTvPackages']);

        Route::post('/verify-red-meter', [UtilityBillTransactionController::class, 'verifyVTMeter']);
        Route::post('/verify-red-decoder', [UtilityBillTransactionController::class, 'verifyVTDecoder']);


        // Betting
        Route::get('/get-bet-platforms', [BettingTransactionController::class, 'getBettingPlatforms']);
        Route::post('/verify-bet-account', [BettingTransactionController::class, 'verifyBettingAccount']);


        // Banks
        // /get-banks
        Route::get('/get-banks', [BankDetailsController::class, 'oldToRedBillerBanks']);
        Route::post('/add-red-bank-account', [BankDetailsController::class, 'verifyCreateRedBillerAccount']);
        Route::get('/bank-account/remove/{id}', [BankDetailsController::class, 'removeBank']);



        // transactions
        Route::get('/transactions', [HomeController::class, 'myTransactions']);

        // board
        Route::get('/board-data', [HomeController::class, 'boardData']);

        // profile
        Route::post('/profile/update', [AccountController::class, 'updateProfile']);


        // verification
        Route::post('/verification/validate', [SafeVerificationController::class, 'validateVerification']);

        // fund account
        Route::post('/fund-account/initiate', [SafeSubAccountController::class, 'initiate']);
        Route::post('/fund-account/create', [SafeSubAccountController::class, 'createSubAccount']);
        Route::get('/fund-account', [TupaySubAccountController::class, 'index']);

        // leaderboard
        Route::get('/rmb-transaction/leaderboard', [RMBTransactionController::class, 'leaderboard']);

        // reward
        Route::get('/rewards', [RewardWalletController::class, 'index']);
    });

    // Admin
    Route::name('admin.')->prefix('admin')->group(function () {

        Route::post('/login', [AuthController::class, 'adminLogin']);

        Route::group(['middleware' => ['auth:api']], function () {
            // home
            Route::get('/home', [HomeController::class, 'adminIndex']);

            // Transactions
            Route::get('/transactions', [HomeController::class, 'allPendingTransactions']);
            Route::get('/old-transactions', [HomeController::class, 'oldTransactions']);
            Route::get('/transactions/users/{id}', [HomeController::class, 'allUserTransactions']);

            // RMB Review
            Route::post('/rmb-transaction/fail', [RMBTransactionController::class, 'fail']);
            Route::post('/rmb-transaction/approve', [RMBTransactionController::class, 'approve']);

            // Single User
            Route::get('/users/{id}', [AccountController::class, 'getUser']);

            Route::get(
                '/users',
                [AccountController::class, 'usersList']
            );

            Route::post('/rmb/method/rate/update', [RMBPaymentMethodController::class, 'updateRates']);

            Route::post('/rmb/conversion/rate/update', [SystemConfigController::class, 'updateConversionRates']);

            Route::post('/board/update', [SystemConfigController::class, 'updateBoardData']);


            // Push Notifications
            Route::post('/notifications/push/send', [HomeController::class, 'sendPushToUsers']);
        });
    });
});
