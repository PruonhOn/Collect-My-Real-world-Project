<?php

use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Email verification
|--------------------------------------------------------------------------
|
| The link in the notification points at the API rather than the SPA, because
| only the API can validate the signature and change the row. It then redirects
| to the SPA's sign-in screen with the outcome in a query string.
|
| The route name `verification.verify` is what Laravel's VerifyEmail
| notification looks up to build the URL.
|
*/
Route::get('email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');
