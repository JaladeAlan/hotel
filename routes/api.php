    <?php

    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\AuthController;
    use App\Http\Controllers\PurchaseController;
    use App\Http\Controllers\DepositController;
    use App\Http\Controllers\UserController;
    use App\Http\Controllers\WithdrawalController;
    use App\Http\Controllers\TransactionController;
    use App\Http\Controllers\WebhookController;
    use App\Http\Controllers\NotificationController; 
    use App\Http\Controllers\BookingsController;
    use App\Http\Controllers\RoomController;
    use App\Http\Controllers\ReviewController;

    // Public routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Email verification routes
    Route::post('/email/verify/code', [AuthController::class, 'verifyEmailCode']);
    Route::post('/email/resend-verification', [AuthController::class, 'resendVerificationEmail']);

    // Password reset routes
    Route::prefix('password')->group(function () {
        Route::post('/reset/code', [AuthController::class, 'sendPasswordResetCode']);
        Route::post('/reset/verify', [AuthController::class, 'verifyResetCode']);
        Route::post('/reset', [AuthController::class, 'resetPassword']);
    });

    // Deposit callback
    Route::get('/deposit/callback', [DepositController::class, 'handleDepositCallback'])->name('deposit.callback');
   
    // Monnify deposit callback
    Route::post('/monnify/deposit/callback', [DepositController::class, 'handleMonnifyCallback']);

    // Webhooks (No Authentication)
    Route::post('/paystack/webhook', [WithdrawalController::class, 'handlePaystackCallback']);
  
    // Protected routes - requires JWT authentication
    Route::middleware(['jwt.auth'])->group(function () {

        // Authentication
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::post('/bookings', [BookingsController::class, 'booking']);
        Route::get('/bookings', [BookingsController::class, 'listAllBookings']);
        Route::post('/bookings/{id}/cancel', [BookingsController::class, 'cancelBooking']);
        Route::post('/bookings/filter', [RoomController::class, 'filterBookings']);  

        Route::post('/staff/room/checkin', [BookingsController::class, 'staffCreateBookingAndCheckIn']);
        Route::post('/staff/room/checkout', [BookingsController::class, 'staffCheckOutByRoom']);

        Route::post('/bookings/{id}/checkin', [BookingController::class, 'checkIn']);
        Route::post('/bookings/{id}/checkout', [BookingController::class, 'checkOut']);

        Route::put('/bookings/{id}/extend', [BookingController::class, 'extendStay']);

        // User profile & balance
        Route::get('/user/profile', [UserController::class, 'getProfile']);
        Route::get('/user/balance', fn() => response()->json(['balance' => auth()->user()->balance]));

        // Bank details
        Route::put('/user/bankdetails', [UserController::class, 'updateBankDetails']);

        // Deposits & Withdrawals
        Route::get('/manual', [DepositController::class, 'getManualFundingDetails']);
        Route::post('/deposit', [DepositController::class, 'initiateDeposit']);
        Route::post('/withdraw', [WithdrawalController::class, 'initiateWithdrawal']);
        Route::post('/withdrawal/request', [WithdrawalController::class, 'requestWithdrawal']);
        Route::get('/withdrawals/{reference}', [WithdrawalController::class, 'getWithdrawalStatus']);
        Route::get('/withdrawal/retry', [WithdrawalController::class, 'retryPendingWithdrawals']);

        // Transaction PIN
        Route::post('/pin/set', [UserController::class, 'setTransactionPin']);
        Route::post('/pin/update', [UserController::class, 'updateTransactionPin']);

        // Referral System
        Route::get('/user/referrals', [UserController::class, 'getReferralStats']);

        // User notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'getNotifications']);
            Route::get('/unread', [NotificationController::class, 'getUnreadNotifications']);
            Route::post('/read', [NotificationController::class, 'markAllAsRead']);
        });

        // Room operations
        Route::get('/rooms', [RoomController::class, 'index']);          
        Route::get('/rooms/facilities', [RoomController::class, 'facilities']);   
        Route::post('/rooms', [RoomController::class, 'storeRoom']);       
        Route::get('/rooms/{id}', [RoomController::class, 'show']);         
        Route::put('/rooms/{id}', [RoomController::class, 'update']);       
        Route::delete('/rooms/{id}', [RoomController::class, 'destroy']);   

        Route::post('/bookings/{id}/checkin', [BookingsController::class, 'checkIn']);
        Route::post('/bookings/{id}/checkout', [BookingsController::class, 'checkOut']);

        // Room-specific features
        Route::post('/rooms/check-availability', [RoomController::class, 'checkAvailability']); 
        Route::post('/rooms/filter', [RoomController::class, 'filter']);
       
        Route::get('/user/reviews', [RoomController::class, 'showUserReviews']);       
        Route::post('/reviews', [ReviewController::class, 'store']);
        Route::get('/my-reviews', [ReviewController::class, 'userReviews']);

    Route::middleware(['jwt.auth', \App\Http\Middleware\AdminMiddleware::class])->group(function () {
        Route::post('/admin/manual', [DepositController::class, 'manualDeposit']);
        Route::get('/admin/deposits', [DepositController::class, 'getPendingManualDeposits']);
        Route::post('/admin/deposits/approve', [DepositController::class, 'approveManualDeposit']);
        Route::post('/admin/deposits/reject', [DepositController::class, 'rejectManualDeposit']);
    });
    
    });

    
