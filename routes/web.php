<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OnboardingController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Public Onboarding Routes
Route::get('/onboarding/{token}', [OnboardingController::class, 'show'])->name('onboarding.show');
Route::post('/onboarding/{token}', [OnboardingController::class, 'submit'])->name('onboarding.submit');


Route::get('/login', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return view('auth.login');
})->name('login');

Route::post('/login', function (\Illuminate\Http\Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        return redirect()->intended('dashboard');
    }

    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ])->onlyInput('email');
})->name('login.post');

Route::post('/logout', function (\Illuminate\Http\Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TemplateController;

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    // Employees
    Route::resource('employees', EmployeeController::class);
    Route::patch('employees/{employee}/status', [EmployeeController::class, 'updateStatus'])->name('employees.status');
    Route::post('employees/invite', [EmployeeController::class, 'invite'])->name('employees.invite');
    Route::post('employees/{employee}/approve', [EmployeeController::class, 'approve'])->name('employees.approve');
    Route::post('employees/{employee}/disapprove', [EmployeeController::class, 'disapprove'])->name('employees.disapprove');

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    
    // Bank Management
    Route::post('/settings/banks', [SettingController::class, 'storeBank'])->name('settings.banks.store');
    Route::put('/settings/banks/{bank}', [SettingController::class, 'updateBank'])->name('settings.banks.update');
    Route::delete('/settings/banks/{bank}', [SettingController::class, 'destroyBank'])->name('settings.banks.destroy');
    
    // SMTP Management
    Route::post('/settings/smtp', [SettingController::class, 'storeSmtp'])->name('settings.smtp.store');
    Route::put('/settings/smtp/{smtp}', [SettingController::class, 'updateSmtp'])->name('settings.smtp.update');
    Route::delete('/settings/smtp/{smtp}', [SettingController::class, 'destroySmtp'])->name('settings.smtp.destroy');
    Route::patch('/settings/smtp/{smtp}/default', [SettingController::class, 'setDefaultSmtp'])->name('settings.smtp.default');

    // Policy Management
    Route::post('/settings/policies', [SettingController::class, 'storePolicy'])->name('settings.policies.store');
    Route::put('/settings/policies/{policy}', [SettingController::class, 'updatePolicy'])->name('settings.policies.update');
    Route::delete('/settings/policies/{policy}', [SettingController::class, 'destroyPolicy'])->name('settings.policies.destroy');
    Route::patch('/settings/policies/{policy}/toggle', [SettingController::class, 'togglePolicyVisibility'])->name('settings.policies.toggle');
    Route::patch('/settings/policies/{policy}/reorder', [SettingController::class, 'reorderPolicy'])->name('settings.policies.reorder');

    // General Settings
    Route::post('/settings/general', [SettingController::class, 'updateGeneralSettings'])->name('settings.general.update');
    
    // Email Testing
    Route::post('/settings/test-email', [SettingController::class, 'sendTestEmail'])->name('settings.test-email');

    // User Management
    Route::resource('users', UserController::class);
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');

    // Templates & Forms
    Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
    Route::post('/templates/email', [TemplateController::class, 'storeEmailTemplate'])->name('templates.email.store');
    Route::put('/templates/{id}/update', [TemplateController::class, 'updateEmailTemplate'])->name('templates.email.update');
    Route::put('/templates/forms/{id}/update', [TemplateController::class, 'updateFormTemplate'])->name('templates.forms.update');
    Route::post('/templates/toggle-status', [TemplateController::class, 'toggleStatus'])->name('templates.toggle-status');
// Maintenance Routes
Route::get('/fix-storage', function() {
    try {
        if (file_exists(public_path('storage'))) {
            // If it's a directory (not a link), we might need to delete it
            // but let's try to just link first
        }
        \Illuminate\Support\Facades\Artisan::call('storage:link');
        return "Storage link fixed! <a href='/'>Go Back</a>";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});

    // Activity Logs
    Route::get('/activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'index'])->name('activity-logs.index');
});
