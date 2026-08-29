<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\MembershipController as AdminMembershipController;
use App\Http\Controllers\Admin\TransactionController as AdminTransactionController;
use App\Http\Controllers\Member\DashboardController as MemberDashboardController;
use App\Http\Controllers\Member\ProjectController as MemberProjectController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Legacy redirect: /dashboard -> role based
Route::get('/dashboard', function () {
    $user = auth()->user();
    if (! $user) return redirect()->route('login');
    if ($user->hasAnyRole(['superadmin','admin'])) return redirect()->route('admin.dashboard');
    return redirect()->route('member.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Admin routes
Route::middleware(['auth', 'verified', 'role:superadmin|admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/memberships', [AdminMembershipController::class, 'index'])->name('memberships.index');
    Route::get('/transactions', [AdminTransactionController::class, 'index'])->name('transactions.index');
    // Placeholder for production menus (coming soon)
    Route::get('/paket', [AdminMembershipController::class, 'index'])->name('paket.index');
    Route::get('/templates', fn() => Inertia::render('Admin/ComingSoon', ['title' => 'Template PRD']))->name('templates.index');
    Route::get('/tickets', fn() => Inertia::render('Admin/ComingSoon', ['title' => 'Tiket Support']))->name('tickets.index');
    Route::get('/settings', fn() => Inertia::render('Admin/ComingSoon', ['title' => 'Pengaturan']))->name('settings.index');
});

// Member routes
Route::middleware(['auth', 'verified'])->prefix('member')->name('member.')->group(function () {
    Route::get('/dashboard', [MemberDashboardController::class, 'index'])->name('dashboard');
    Route::get('/projects', [MemberProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/create', fn() => Inertia::render('Member/Projects/Create'))->name('projects.create');
    Route::get('/profile', fn() => redirect()->route('profile.edit'))->name('profile');
    Route::get('/billing', fn() => Inertia::render('Member/Billing/Index'))->name('billing.index');
    Route::get('/topup', fn() => Inertia::render('Member/TopUp/Index'))->name('topup.index');
    Route::get('/usage', fn() => Inertia::render('Member/Usage/Index'))->name('usage.index');
    Route::get('/tickets', fn() => Inertia::render('Member/Tickets/Index'))->name('tickets.index');
    Route::get('/settings', fn() => Inertia::render('Member/Settings/Index'))->name('settings.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
