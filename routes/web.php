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
Route::get('/onboarding-completed', [OnboardingController::class, 'completed'])->name('onboarding.completed');


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
        $user = Auth::user();

        if ($user->two_factor_enabled) {
            Auth::logout(); // Log out immediately to prevent access without 2FA
            
            $user->generateTwoFactorCode();
            
            // Send Email (using Queue if possible, but for now synchronous or sync queue)
            try {
                \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\TwoFactorCodeMail($user->two_factor_code));
            } catch (\Exception $e) {
                // Log error but proceed to show the form (user can click resend)
                // Log::error($e->getMessage());
            }

            $request->session()->put('user_2fa_id', $user->id);
            return redirect()->route('verify.index');
        }

        $request->session()->regenerate();
        return redirect()->intended('dashboard');
    }

    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ])->onlyInput('email');
})->name('login.post');

Route::post('verify/resend', [App\Http\Controllers\TwoFactorController::class, 'resend'])->name('verify.resend');
Route::resource('verify', App\Http\Controllers\TwoFactorController::class)->only(['index', 'store']);

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
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\TeamController;

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'module:dashboard,read'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    // Employees
    Route::middleware('module:employees,read')->group(function () {
        Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
        Route::get('employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::get('employees/{employee}/letters/{letter}/download', [EmployeeController::class, 'downloadLetter'])->name('employees.letters.download');
    });
    Route::middleware('module:employees,create')->group(function () {
        Route::post('employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::post('employees/invite', [EmployeeController::class, 'invite'])->name('employees.invite');
        Route::post('employees/import', [EmployeeController::class, 'importCsv'])->name('employees.import');
    });
    Route::middleware('module:employees,edit')->group(function () {
        Route::put('employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::patch('employees/{employee}/shift-timing', [EmployeeController::class, 'updateShiftTiming'])->name('employees.shift-timing.update');
        Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
        Route::patch('employees/{employee}/status', [EmployeeController::class, 'updateStatus'])->name('employees.status');
        Route::post('employees/{employee}/approve', [EmployeeController::class, 'approve'])->name('employees.approve');
        Route::post('employees/{employee}/disapprove', [EmployeeController::class, 'disapprove'])->name('employees.disapprove');
        Route::post('employees/{employee}/letters', [EmployeeController::class, 'generateLetter'])->name('employees.letters.generate');
    });

    // Team Management
    Route::middleware('module:team_management,read')->group(function () {
        Route::get('/my-team', [TeamController::class, 'index'])->name('team.index');
        Route::get('/my-team/{employee}', [TeamController::class, 'show'])->name('team.show');
    });
    Route::middleware('module:team_management,edit')->group(function () {
        Route::post('/my-team/{employee}/reviews', [TeamController::class, 'storeReview'])->name('team.reviews.store');
    });

    // Leave Management
    Route::middleware('module:leave_management,read')->group(function () {
        Route::get('/leaves', [LeaveController::class, 'index'])->name('leaves.index');
        Route::post('/leaves/{leaveRequest}/cancel', [LeaveController::class, 'cancel'])->name('leaves.cancel');
    });
    Route::middleware('module:leave_management,create')->group(function () {
        Route::post('/leaves', [LeaveController::class, 'store'])->name('leaves.store');
    });
    Route::middleware('module:leave_management,edit')->group(function () {
        Route::post('/leaves/{leaveRequest}/approve', [LeaveController::class, 'approve'])->name('leaves.approve');
        Route::post('/leaves/{leaveRequest}/reject', [LeaveController::class, 'reject'])->name('leaves.reject');
        Route::post('/leave-types', [LeaveController::class, 'storeType'])->name('leave-types.store');
        Route::put('/leave-types/{leaveType}', [LeaveController::class, 'updateType'])->name('leave-types.update');
        Route::delete('/leave-types/{leaveType}', [LeaveController::class, 'destroyType'])->name('leave-types.destroy');
    });

    // Attendance Management
    Route::middleware('module:attendance_management,read')->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    });
    Route::middleware('module:attendance_management,create')->group(function () {
        Route::post('/attendance/import', [AttendanceController::class, 'import'])->name('attendance.import');
    });

    // Payroll Management
    Route::middleware('module:payroll_management,read')->group(function () {
        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::get('/payroll/{payrollRun}/employees/{employee}/payslip', [PayrollController::class, 'downloadPayslip'])->name('payroll.payslip.download');
        Route::get('/payroll/{payrollRun}/payslips.zip', [PayrollController::class, 'downloadPayslipZip'])->name('payroll.payslips.zip.download');
        Route::get('/payroll/{payrollRun}/ift.xlsx', [PayrollController::class, 'downloadIftWorkbook'])->name('payroll.ift.download');
        Route::get('/payroll/{payrollRun}/ibft.xlsx', [PayrollController::class, 'downloadIbftWorkbook'])->name('payroll.ibft.download');
    });
    Route::middleware('module:payroll_management,create')->group(function () {
        Route::post('/payroll/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
        Route::get('/payroll/payout-preview', [PayrollController::class, 'payoutPreview'])->name('payroll.payout-preview');
    });
    Route::middleware('module:payroll_management,edit')->group(function () {
        Route::post('/payroll/adjustments', [PayrollController::class, 'updateAdjustments'])->name('payroll.adjustments.update');
        Route::post('/payroll/adjustments/autosave', [PayrollController::class, 'autosaveAdjustment'])->name('payroll.adjustments.autosave');
        Route::post('/payroll/{payrollRun}/finalize', [PayrollController::class, 'finalize'])->name('payroll.finalize');
        Route::delete('/payroll/{payrollRun}', [PayrollController::class, 'destroy'])->name('payroll.destroy');
    });

    // Announcements
    Route::middleware('module:announcements,read')->group(function () {
        Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    });
    Route::middleware('module:announcements,create')->group(function () {
        Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    });
    Route::middleware('module:announcements,edit')->group(function () {
        Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
        Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
    });

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->middleware('module:settings,read')->name('settings.index');
    
    // Bank Management
    Route::post('/settings/banks', [SettingController::class, 'storeBank'])->middleware('module:settings,edit')->name('settings.banks.store');
    Route::put('/settings/banks/{bank}', [SettingController::class, 'updateBank'])->middleware('module:settings,edit')->name('settings.banks.update');
    Route::delete('/settings/banks/{bank}', [SettingController::class, 'destroyBank'])->middleware('module:settings,edit')->name('settings.banks.destroy');
    
    // SMTP Management
    Route::post('/settings/smtp', [SettingController::class, 'storeSmtp'])->middleware('module:settings,edit')->name('settings.smtp.store');
    Route::put('/settings/smtp/{smtp}', [SettingController::class, 'updateSmtp'])->middleware('module:settings,edit')->name('settings.smtp.update');
    Route::delete('/settings/smtp/{smtp}', [SettingController::class, 'destroySmtp'])->middleware('module:settings,edit')->name('settings.smtp.destroy');
    Route::patch('/settings/smtp/{smtp}/default', [SettingController::class, 'setDefaultSmtp'])->middleware('module:settings,edit')->name('settings.smtp.default');

    // Policy Management
    Route::post('/settings/policies', [SettingController::class, 'storePolicy'])->middleware('module:settings,edit')->name('settings.policies.store');
    Route::put('/settings/policies/{policy}', [SettingController::class, 'updatePolicy'])->middleware('module:settings,edit')->name('settings.policies.update');
    Route::delete('/settings/policies/{policy}', [SettingController::class, 'destroyPolicy'])->middleware('module:settings,edit')->name('settings.policies.destroy');
    Route::patch('/settings/policies/{policy}/toggle', [SettingController::class, 'togglePolicyVisibility'])->middleware('module:settings,edit')->name('settings.policies.toggle');
    Route::patch('/settings/policies/{policy}/reorder', [SettingController::class, 'reorderPolicy'])->middleware('module:settings,edit')->name('settings.policies.reorder');

    // General Settings
    Route::post('/settings/general', [SettingController::class, 'updateGeneralSettings'])->middleware('module:settings,edit')->name('settings.general.update');
    Route::post('/settings/tax-formulas', [SettingController::class, 'updateTaxFormulas'])->middleware('module:settings,edit')->name('settings.tax-formulas.update');
    Route::get('/settings/tax-formulas/example', [SettingController::class, 'taxFormulaExample'])->middleware('module:settings,read')->name('settings.tax-formulas.example');
    
    // Email Testing
    Route::post('/settings/test-email', [SettingController::class, 'sendTestEmail'])->middleware('module:settings,edit')->name('settings.test-email');

    // User Management
    Route::get('users', [UserController::class, 'index'])->middleware('module:user_management,read')->name('users.index');
    Route::post('users', [UserController::class, 'store'])->middleware('module:user_management,edit')->name('users.store');
    Route::put('users/{user}', [UserController::class, 'update'])->middleware('module:user_management,edit')->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('module:user_management,edit')->name('users.destroy');
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->middleware('module:user_management,edit')->name('users.reset-password');
    Route::post('roles', [UserController::class, 'storeRole'])->middleware('module:user_management,edit')->name('roles.store');
    Route::put('roles/{role}', [UserController::class, 'updateRole'])->middleware('module:user_management,edit')->name('roles.update');
    Route::delete('roles/{role}', [UserController::class, 'destroyRole'])->middleware('module:user_management,edit')->name('roles.destroy');

    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::post('/profile/two-factor', [ProfileController::class, 'toggleTwoFactor'])->name('profile.two-factor.toggle');

    // Templates & Forms
    Route::get('/templates', [TemplateController::class, 'index'])->middleware('module:templates,read')->name('templates.index');
    Route::post('/templates/email', [TemplateController::class, 'storeEmailTemplate'])->middleware('module:templates,edit')->name('templates.email.store');
    Route::put('/templates/{id}/update', [TemplateController::class, 'updateEmailTemplate'])->middleware('module:templates,edit')->name('templates.email.update');
    Route::put('/templates/forms/{id}/update', [TemplateController::class, 'updateFormTemplate'])->middleware('module:templates,edit')->name('templates.forms.update');
    Route::post('/templates/toggle-status', [TemplateController::class, 'toggleStatus'])->middleware('module:templates,edit')->name('templates.toggle-status');
// Maintenance Routes
Route::get('/migrate-storage', function() {
    $results = [];
    $oldDir = storage_path('app/public');
    $newDir = public_path('storage');
    
    if (!file_exists($newDir)) {
        mkdir($newDir, 0755, true);
        $results[] = "Created direct public storage directory.";
    } elseif (is_link($newDir)) {
        unlink($newDir);
        mkdir($newDir, 0755, true);
        $results[] = "Deleted old broken symlink and created real directory.";
    }

    $results[] = "Moving files from $oldDir to $newDir...";
    
    // Recursive copy/move
    $moveFiles = function($src, $dst) use (&$moveFiles, &$results) {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $moveFiles($src . '/' . $file, $dst . '/' . $file);
                } else {
                    if (copy($src . '/' . $file, $dst . '/' . $file)) {
                        $results[] = "Copied: $file";
                    } else {
                        $results[] = "FAILED: $file";
                    }
                }
            }
        }
        closedir($dir);
    };

    if (is_dir($oldDir)) {
        $moveFiles($oldDir, $newDir);
        $results[] = "\nMigration complete!";
    } else {
        $results[] = "Old storage directory not found.";
    }
    
    return "<pre>" . implode("\n", $results) . "\n\n<a href='/'>Go Home</a></pre>";
});

    // Activity Logs
    Route::get('/activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'index'])->middleware('module:activity_logs,read')->name('activity-logs.index');
});
