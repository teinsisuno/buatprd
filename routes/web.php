<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\MembershipController as AdminMembershipController;
use App\Http\Controllers\Admin\TransactionController as AdminTransactionController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\AiProviderController as AdminAiProviderController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\Member\DashboardController as MemberDashboardController;
use App\Http\Controllers\Member\ProjectController as MemberProjectController;
use App\Http\Controllers\Member\WizardController as MemberWizardController;
use App\Http\Controllers\Member\BillingController as MemberBillingController;
use App\Http\Controllers\Member\TopUpController as MemberTopUpController;
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
    Route::post('/memberships', [AdminMembershipController::class, 'store'])->name('memberships.store');
    Route::put('/memberships/{tier}', [AdminMembershipController::class, 'update'])->name('memberships.update');
    Route::delete('/memberships/{tier}', [AdminMembershipController::class, 'destroy'])->name('memberships.destroy');
    Route::post('/memberships/reorder', [AdminMembershipController::class, 'reorder'])->name('memberships.reorder');

    Route::get('/transactions', [AdminTransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{transaction}', [AdminTransactionController::class, 'show'])->name('transactions.show');
    Route::post('/transactions/{transaction}/approve', [AdminTransactionController::class, 'approve'])->name('transactions.approve');
    Route::post('/transactions/{transaction}/reject', [AdminTransactionController::class, 'reject'])->name('transactions.reject');
    Route::get('/transactions/{transaction}/proof', [AdminTransactionController::class, 'proof'])->name('transactions.proof');
    Route::get('/transactions/{transaction}/invoice', [InvoiceController::class, 'adminInvoice'])->name('transactions.invoice');

    Route::get('/coupons', [AdminCouponController::class, 'index'])->name('coupons.index');
    Route::post('/coupons', [AdminCouponController::class, 'store'])->name('coupons.store');
    Route::put('/coupons/{coupon}', [AdminCouponController::class, 'update'])->name('coupons.update');
    Route::delete('/coupons/{coupon}', [AdminCouponController::class, 'destroy'])->name('coupons.destroy');

    // AI Providers — multi provider
    Route::get('/ai-providers', [AdminAiProviderController::class, 'index'])->name('ai-providers.index');
    Route::post('/ai-providers', [AdminAiProviderController::class, 'store'])->name('ai-providers.store');
    Route::put('/ai-providers/{aiProvider}', [AdminAiProviderController::class, 'update'])->name('ai-providers.update');
    Route::delete('/ai-providers/{aiProvider}', [AdminAiProviderController::class, 'destroy'])->name('ai-providers.destroy');
    Route::post('/ai-providers/{aiProvider}/default', [AdminAiProviderController::class, 'setDefault'])->name('ai-providers.default');
    Route::post('/ai-providers/{aiProvider}/toggle', [AdminAiProviderController::class, 'toggle'])->name('ai-providers.toggle');
    Route::post('/ai-providers/{aiProvider}/fetch', [AdminAiProviderController::class, 'fetchModels'])->name('ai-providers.fetch');
    Route::post('/ai-providers/{aiProvider}/test', [AdminAiProviderController::class, 'test'])->name('ai-providers.test');
    Route::post('/ai-providers/fetch-preview', [AdminAiProviderController::class, 'fetchPreview'])->name('ai-providers.fetchPreview');

    // Settings (general, payment, security) — superadmin
    Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [AdminSettingController::class, 'update'])->name('settings.update');

    // Alias /paket to same membership management
    Route::get('/paket', [AdminMembershipController::class, 'index'])->name('paket.index');
    Route::get('/templates', fn() => Inertia::render('Admin/ComingSoon', ['title' => 'Template PRD']))->name('templates.index');
    Route::get('/tickets', fn() => Inertia::render('Admin/ComingSoon', ['title' => 'Tiket Support']))->name('tickets.index');
});

// Member routes
Route::middleware(['auth', 'verified'])->prefix('member')->name('member.')->group(function () {
    Route::get('/dashboard', [MemberDashboardController::class, 'index'])->name('dashboard');
    Route::get('/projects', [MemberProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/create', fn() => Inertia::render('Member/Projects/Create'))->name('projects.create');
    Route::post('/projects', [MemberProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [MemberProjectController::class, 'show'])->name('projects.show');
    Route::delete('/projects/{project}', [MemberProjectController::class, 'destroy'])->name('projects.destroy');

    // Wizard L1-3
    Route::get('/projects/{project}/wizard/{step}', [MemberWizardController::class, 'show'])->name('wizard.show');
    Route::post('/projects/{project}/wizard/{step}/chat', [MemberWizardController::class, 'chat'])->middleware('throttle:10,1')->name('wizard.chat');
    Route::put('/projects/{project}/wizard/{step}/final', [MemberWizardController::class, 'saveFinal'])->name('wizard.final');
    Route::get('/projects/{project}/wizard-zip', [MemberWizardController::class, 'downloadZip'])->name('wizard.zip');

    Route::get('/profile', fn() => redirect()->route('profile.edit'))->name('profile');

    // Billing & Subscription
    Route::get('/billing', [MemberBillingController::class, 'index'])->name('billing.index');
    Route::get('/billing/checkout/{tier}', [MemberBillingController::class, 'checkout'])->name('billing.checkout');
    Route::post('/billing/checkout/{tier}', [MemberBillingController::class, 'store'])->name('billing.store');
    Route::get('/billing/invoice/{transaction}', [MemberBillingController::class, 'invoice'])->name('billing.invoice');
    Route::get('/transactions/{transaction}/proof', [InvoiceController::class, 'proof'])->name('transactions.proof');
    Route::get('/transactions/{transaction}/invoice', [InvoiceController::class, 'memberInvoice'])->name('transactions.invoice');

    // AI models available for wizard
    Route::get('/ai/models', function (\App\Services\AiService $ai) {
        return response()->json($ai->availableModels());
    })->name('ai.models');

    // TopUp
    Route::get('/topup', [MemberTopUpController::class, 'index'])->name('topup.index');
    Route::post('/topup', [MemberTopUpController::class, 'store'])->name('topup.store');

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
