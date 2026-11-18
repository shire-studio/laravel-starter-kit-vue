<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CurrentTeamDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Teams\AcceptInvitationController;
use App\Http\Controllers\Teams\DeclineInvitationController;
use App\Http\Controllers\Teams\LeaveTeamController;
use App\Http\Controllers\Teams\SwitchTeamController;
use App\Http\Controllers\Teams\TeamController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\Teams\TeamMemberController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome'))->name('home');

Route::get('.well-known/passkey-endpoints', fn () => response()->json([
    'enroll' => route('password.edit'),
    'manage' => route('password.edit'),
]))->name('well-known.passkeys');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', CurrentTeamDashboardController::class)->name('dashboard');

    Route::get('{current_team:slug}/dashboard', DashboardController::class)
        ->middleware(EnsureTeamMembership::class)
        ->name('team.dashboard');

    Route::get('settings/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::post('settings/teams', [TeamController::class, 'store'])->name('teams.store');
    Route::get('settings/teams/{team:slug}', [TeamController::class, 'edit'])->can('view', 'team')
        ->name('teams.edit');
    Route::patch('settings/teams/{team:slug}', [TeamController::class, 'update'])->can('update', 'team')
        ->name('teams.update');
    Route::delete('settings/teams/{team:slug}', [TeamController::class, 'destroy'])->can('delete', 'team')
        ->name('teams.destroy');
    Route::post('settings/teams/{team:slug}/switch', SwitchTeamController::class)->can('view', 'team')
        ->name('teams.switch');
    Route::delete('settings/teams/{team:slug}/leave', LeaveTeamController::class)->can('leave', 'team')
        ->name('teams.leave');

    Route::patch('settings/teams/{team:slug}/members/{user}', [TeamMemberController::class, 'update'])->can('updateMember', 'team')
        ->can('manageMember', ['team', 'user'])
        ->name('teams.members.update');
    Route::delete('settings/teams/{team:slug}/members/{user}', [TeamMemberController::class, 'destroy'])->can('removeMember', 'team')
        ->can('manageMember', ['team', 'user'])
        ->name('teams.members.destroy');

    Route::post('settings/teams/{team:slug}/invitations', [TeamInvitationController::class, 'store'])->can('inviteMember', 'team')
        ->name('teams.invitations.store');
    Route::delete('settings/teams/{team:slug}/invitations/{invitation:code}', [TeamInvitationController::class, 'destroy'])->can('cancelInvitation', 'team')
        ->name('teams.invitations.destroy');

    Route::post('invitations/{invitation:code}/accept', AcceptInvitationController::class)->name('invitations.accept');
    Route::delete('invitations/{invitation:code}', DeclineInvitationController::class)->name('invitations.decline');
});

Route::middleware('auth')->group(function (): void {
    Route::delete('user', [ProfileController::class, 'destroy'])
        ->middleware([HandlePrecognitiveRequests::class, 'throttle:6,1'])
        ->name('user.destroy');

    Route::redirect('settings', '/settings/profile');
    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('user-profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])
        ->middleware(HandlePrecognitiveRequests::class)
        ->name('user-profile.update');

    Route::get('settings/password', [SecurityController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    Route::get('settings/appearance', fn () => Inertia::render('settings/Appearance'))->name('appearance.edit');

    Route::redirect('settings/two-factor', '/settings/password');
});

Route::middleware('guest')->group(function (): void {
    Route::resource('register', RegisterController::class)
        ->only(['index', 'store'])
        ->middlewareFor('store', HandlePrecognitiveRequests::class)
        ->names(['index' => 'register', 'store' => 'register.store']);

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware(HandlePrecognitiveRequests::class)
        ->name('password.store');

    Route::resource('forgot-password', PasswordResetLinkController::class)
        ->only(['index', 'store'])
        ->middlewareFor('store', HandlePrecognitiveRequests::class)
        ->names(['index' => 'password.request', 'store' => 'password.email']);

    Route::resource('login', LoginController::class)
        ->only(['index', 'store'])
        ->middlewareFor('store', HandlePrecognitiveRequests::class)
        ->names(['index' => 'login', 'store' => 'login.store']);
});

Route::middleware('auth')->group(function (): void {
    Route::get('verify-email', [EmailVerificationController::class, 'index'])
        ->name('verification.notice');
    Route::post('email/verification-notification', EmailVerificationNotificationController::class)
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('verify-email/{id}/{hash}', [EmailVerificationController::class, 'update'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('logout', LogoutController::class)->name('logout');
});
