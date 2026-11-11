<?php

use App\Http\Controllers\Api\Admin\AdminAuthorController;
use App\Http\Controllers\Api\Admin\AdminBookTocController;
use App\Http\Controllers\Api\Admin\AdminBroadcastMessageController;
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Admin\AdminEventController;
use App\Http\Controllers\Api\Admin\AdminOrganizationController;
use App\Http\Controllers\Api\AdminBookReviewController;
use App\Http\Controllers\Api\AiAssistantController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Author\AuthorAuthController;
use App\Http\Controllers\Api\Author\AuthorBookController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\BroadcastMessageController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\EventsController;
use App\Http\Controllers\Api\LibraryController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\ReaderController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SharingController;
use App\Http\Controllers\Api\StorageController;
use App\Http\Controllers\Api\VipPlanController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::get('/storage/{disk}/{path}', [StorageController::class, 'serve'])
        ->name('storage.serve')
        ->where('path', '.*');

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/google-login', [AuthController::class, 'googleLogin']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::get('/email/verify/{id}', [EmailVerificationController::class, 'verify'])
        ->name('verification.verify');

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);

    Route::get('/books', [BookController::class, 'index']);
    Route::get('/books/{book}', [BookController::class, 'show']);
    Route::get('/books/{book}/access', [BookController::class, 'access']);
    Route::post('/books/{book}/views', [BookController::class, 'recordView'])
        ->middleware('throttle:120,1');
    Route::get('/stats', [BookController::class, 'stats']);
    Route::get('/authors', [BookController::class, 'authors']);

    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{course}', [CourseController::class, 'show']);

    Route::get('/vip-plans', [VipPlanController::class, 'index']);

    Route::get('/broadcast-messages', [BroadcastMessageController::class, 'index']);

    Route::get('/events', [EventsController::class, 'index']);
    Route::get('/events/{event}', [EventsController::class, 'show']);

    Route::get('/quizzes', [QuizController::class, 'index']);
    Route::get('/quizzes/{quiz}', [QuizController::class, 'show']);

    Route::get('/reader/{book}', [ReaderController::class, 'read']);
    Route::get('/reader/{book}/stream', [ReaderController::class, 'stream']);

    Route::get('/books/{book}/reviews', [ReviewController::class, 'index']);
    Route::post('/contact', [MessageController::class, 'contact']);

    Route::post('/webhooks/stripe', [PaymentWebhookController::class, 'handle']);

    Route::post('/author/login', [AuthorAuthController::class, 'login']);

    Route::get('/organization', [OrganizationController::class, 'index']);
    Route::get('/organization/members/{member}', [OrganizationController::class, 'show']);

    Route::middleware(['auth:sanctum', 'user'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/me', [AuthController::class, 'updateProfile']);
        Route::put('/me/reader-preferences', [AuthController::class, 'updateReaderPreferences']);
        Route::post('/email/verification-notification', [EmailVerificationController::class, 'sendNotification']);

        Route::post('/books/{book}/purchase', [PaymentController::class, 'purchase']);
        Route::post('/courses/{course}/purchase', [PaymentController::class, 'purchaseCourse']);
        Route::post('/vip/{vipPlan}/purchase', [PaymentController::class, 'purchaseVip']);
        Route::post('/payments/bakong', [PaymentController::class, 'purchaseDigital']);
        Route::get('/payments', [PaymentController::class, 'index']);
        Route::get('/owned-items', [PaymentController::class, 'ownedItems']);
        Route::get('/payments/{payment}', [PaymentController::class, 'show']);
        Route::post('/payments/{payment}/bakong/check', [PaymentController::class, 'checkBakong']);

        Route::get('/library', [LibraryController::class, 'index']);
        Route::get('/library/{book}', [LibraryController::class, 'show']);

        Route::post('/reader/{book}/progress', [ReaderController::class, 'updateProgress']);
        Route::get('/reader/{book}/progress', [ReaderController::class, 'getProgress']);
        Route::get('/reader/{book}/bookmarks', [ReaderController::class, 'indexBookmarks']);
        Route::post('/reader/{book}/bookmarks', [ReaderController::class, 'storeBookmark']);
        Route::delete('/reader/bookmarks/{bookmark}', [ReaderController::class, 'destroyBookmark']);

        Route::get('/ai/options', [AiAssistantController::class, 'options']);
        Route::post('/reader/{book}/ai', [AiAssistantController::class, 'complete'])->middleware('throttle:ai');

        Route::post('/books/{book}/share', [SharingController::class, 'generate']);
        Route::post('/redeem', [SharingController::class, 'redeem']);
        Route::get('/sharing', [SharingController::class, 'index']);
        Route::delete('/sharing/{sharedAccess}', [SharingController::class, 'revoke']);

        Route::post('/books/{book}/reviews', [ReviewController::class, 'store']);
        Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);

        Route::get('/my/messages', [MessageController::class, 'myMessages']);
        Route::get('/my/messages/unread-count', [MessageController::class, 'unreadCount']);
        Route::post('/my/messages/read', [MessageController::class, 'markRead']);
        Route::get('/conversations', [MessageController::class, 'conversations']);
        Route::get('/conversations/{conversation}/messages', [MessageController::class, 'conversationMessages']);
        Route::post('/messages', [MessageController::class, 'store']);
        Route::patch('/messages/{message}/read', [MessageController::class, 'readMessage']);

        // Quizzes and event registration (member-side).
        Route::post('/quizzes/{quiz}/attempt', [QuizController::class, 'attempt']);
        Route::get('/quizzes/{quiz}/attempts', [QuizController::class, 'myAttempts']);
        Route::post('/events/{event}/register', [EventsController::class, 'register']);

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    });

    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('/admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);

        Route::get('/books', [BookController::class, 'adminIndex']);
        Route::get('/books/{book}', [BookController::class, 'adminShow']);
        Route::post('/books', [BookController::class, 'store']);
        Route::put('/books/{book}', [BookController::class, 'update']);
        Route::delete('/books/{book}', [BookController::class, 'destroy']);

        // Review workflow. The review endpoints live on their own controller;
        // the /books aliases exist because the frontend calls them on the
        // book resource itself.
        Route::get('/book-reviews', [AdminBookReviewController::class, 'index']);
        Route::get('/book-reviews/{book}', [AdminBookReviewController::class, 'show']);
        Route::post('/book-reviews/{book}', [AdminBookReviewController::class, 'review']);
        Route::post('/book-reviews/{book}/publish', [AdminBookReviewController::class, 'publish']);
        Route::post('/book-reviews/{book}/unpublish', [AdminBookReviewController::class, 'unpublish']);
        Route::get('/book-reviews/{book}/file', [AdminBookReviewController::class, 'file']);

        Route::post('/books/{book}/approve', [AdminBookReviewController::class, 'approve']);
        Route::post('/books/{book}/reject', [AdminBookReviewController::class, 'reject']);
        Route::post('/books/{book}/publish', [AdminBookReviewController::class, 'publish']);
        Route::post('/books/{book}/unpublish', [AdminBookReviewController::class, 'unpublish']);

        Route::get('/books/{book}/toc', [AdminBookTocController::class, 'show']);
        Route::put('/books/{book}/toc', [AdminBookTocController::class, 'update']);
        Route::delete('/books/{book}/toc', [AdminBookTocController::class, 'destroy']);
        Route::post('/books/{book}/toc/extract', [AdminBookTocController::class, 'extract']);
        Route::post('/books/{book}/toc/items', [AdminBookTocController::class, 'store']);
        Route::put('/books/{book}/toc/items/{tocItem}', [AdminBookTocController::class, 'update']);
        Route::delete('/books/{book}/toc/items/{tocItem}', [AdminBookTocController::class, 'destroy']);
        Route::post('/books/{book}/toc/reorder', [AdminBookTocController::class, 'reorder']);

        Route::get('/authors', [AdminAuthorController::class, 'index']);
        Route::post('/authors', [AdminAuthorController::class, 'store']);
        Route::put('/authors/{author}', [AdminAuthorController::class, 'update']);
        Route::delete('/authors/{author}', [AdminAuthorController::class, 'destroy']);
        Route::get('/authors/{author}', [AdminAuthorController::class, 'show']);
        Route::patch('/authors/{author}/status', [AdminAuthorController::class, 'updateStatus']);
        Route::post('/authors/{author}/reset-password', [AdminAuthorController::class, 'resetPassword']);
        Route::post('/authors/{author}/verify-email', [AdminAuthorController::class, 'verifyEmail']);

        Route::get('/categories', [CategoryController::class, 'adminIndex']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        Route::get('/users', [AdminController::class, 'users']);
        Route::patch('/users/{user}/role', [AdminController::class, 'updateUserRole']);
        Route::delete('/users/{user}', [AdminController::class, 'destroyUser']);

        Route::get('/vip-users', [AdminController::class, 'vipUsers']);
        Route::get('/vip-test-cards', [AdminController::class, 'vipTestCards']);
        Route::post('/vip-test-cards/{purchase}/revoke', [AdminController::class, 'revokeVipTestCard']);

        Route::get('/quizzes', [QuizController::class, 'adminIndex']);
        Route::get('/quizzes/{quiz}', [QuizController::class, 'adminShow']);
        Route::post('/quizzes', [QuizController::class, 'store']);
        Route::put('/quizzes/{quiz}', [QuizController::class, 'update']);
        Route::delete('/quizzes/{quiz}', [QuizController::class, 'destroy']);
        Route::get('/events', [AdminEventController::class, 'index']);
        Route::post('/events', [AdminEventController::class, 'store']);
        Route::get('/events/{event}', [AdminEventController::class, 'show']);
        Route::put('/events/{event}', [AdminEventController::class, 'update']);
        Route::delete('/events/{event}', [AdminEventController::class, 'destroy']);
        Route::get('/events/{event}/registrations', [AdminEventController::class, 'registrations']);
        Route::delete('/events/{event}/registrations/{registration}', [AdminEventController::class, 'destroyRegistration']);

        Route::apiResource('broadcast-messages', AdminBroadcastMessageController::class)->except(['show']);

        Route::get('/vip-plans', [VipPlanController::class, 'adminIndex']);
        Route::post('/vip-plans', [VipPlanController::class, 'store']);
        Route::put('/vip-plans/{vipPlan}', [VipPlanController::class, 'update']);
        Route::delete('/vip-plans/{vipPlan}', [VipPlanController::class, 'destroy']);

        Route::get('/payments', [AdminController::class, 'payments']);
        Route::post('/payments/{payment}/approve', [AdminController::class, 'approvePayment']);
        Route::get('/sharing-keys', [AdminController::class, 'sharingKeys']);

        Route::get('/reviews', [ReviewController::class, 'adminIndex']);
        Route::patch('/reviews/{review}', [ReviewController::class, 'updateStatus']);
        Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);

        Route::get('/messages', [MessageController::class, 'adminIndex']);
        Route::get('/messages/unread-count', [MessageController::class, 'adminUnreadCount']);
        Route::post('/messages/read', [MessageController::class, 'adminMarkRead']);
        Route::post('/messages', [MessageController::class, 'adminStore']);
        Route::post('/messages/send', [MessageController::class, 'adminSend']);
        Route::put('/messages/{message}', [MessageController::class, 'adminUpdate']);
        Route::post('/messages/{message}/reply', [MessageController::class, 'adminReply']);
        Route::delete('/messages/{message}', [MessageController::class, 'adminDestroy']);

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

        Route::get('/organization/structure', [AdminOrganizationController::class, 'structure']);
        Route::post('/organization/departments', [AdminOrganizationController::class, 'storeDepartment']);
        Route::put('/organization/departments/{department}', [AdminOrganizationController::class, 'updateDepartment']);
        Route::delete('/organization/departments/{department}', [AdminOrganizationController::class, 'destroyDepartment']);
        Route::post('/organization/positions', [AdminOrganizationController::class, 'storePosition']);
        Route::put('/organization/positions/{position}', [AdminOrganizationController::class, 'updatePosition']);
        Route::delete('/organization/positions/{position}', [AdminOrganizationController::class, 'destroyPosition']);
        Route::post('/organization/members', [AdminOrganizationController::class, 'storeMember']);
        Route::put('/organization/members/{member}', [AdminOrganizationController::class, 'updateMember']);
        Route::delete('/organization/members/{member}', [AdminOrganizationController::class, 'destroyMember']);
        Route::post('/organization/partners', [AdminOrganizationController::class, 'storePartner']);
        Route::put('/organization/partners/{partner}', [AdminOrganizationController::class, 'updatePartner']);
        Route::delete('/organization/partners/{partner}', [AdminOrganizationController::class, 'destroyPartner']);
        Route::post('/organization/reorder-departments', [AdminOrganizationController::class, 'reorderDepartments']);
        Route::post('/organization/reorder-members', [AdminOrganizationController::class, 'reorderMembers']);
        Route::post('/organization/seed-initial', [AdminOrganizationController::class, 'seedInitial']);
    });

    Route::middleware(['auth:sanctum', 'author'])->prefix('author')->group(function () {
        Route::post('/logout', [AuthorAuthController::class, 'logout']);
        Route::get('/me', [AuthorAuthController::class, 'me']);
        Route::put('/me', [AuthorAuthController::class, 'updateProfile']);
        Route::post('/change-password', [AuthorAuthController::class, 'changePassword']);

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

        Route::get('/stats', [AuthorBookController::class, 'stats']);
        Route::get('/books', [AuthorBookController::class, 'index']);
        Route::post('/books', [AuthorBookController::class, 'store']);
        Route::get('/books/{book}', [AuthorBookController::class, 'show']);
        Route::put('/books/{book}', [AuthorBookController::class, 'update']);
        Route::post('/books/{book}/submit', [AuthorBookController::class, 'submit']);
        Route::post('/books/{book}/submit-review', [AuthorBookController::class, 'submit']);
        Route::delete('/books/{book}', [AuthorBookController::class, 'destroy']);
    });
});
