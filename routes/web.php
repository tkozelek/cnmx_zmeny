<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\BugReportController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\ForgottenPasswordController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

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

// Register form
Route::get('/registracia', [UserController::class, 'create'])->name('register')->middleware('guest');
Route::get('/prihlasenie', [UserController::class, 'login'])->name('login')->middleware('guest');

Route::post('/registracia', [UserController::class, 'store'])->name('register.store')->middleware(['throttle:6,1']);
Route::post('/prihlasenie', [UserController::class, 'authenticate'])->name('login.auth')->middleware(['throttle:6,1']);

Route::get('/pomoc', [HelpController::class, 'index'])->name('help');

Route::get('/logout', [UserController::class, 'logout'])->middleware(['auth']);

// Display weeks -> Auth
Route::middleware(['allowed'])->controller(CalendarController::class)->group(function () {
    Route::get('/', 'index')->name('calendar.index');
    Route::get('/week/{week}', 'show')->name('calendar.show');
    Route::post('/adduser', 'addUser');
});

// Admin stuff
Route::middleware(['role:3'])->prefix('admin')->group(function () {
    Route::get('/pouzivatelia', [AdminController::class, 'index'])->name('admin.users.index');

    Route::get('/{user}/edit', [AdminController::class, 'edit'])->name('admin.users.edit');
    Route::put('/{user}', [UserController::class, 'update'])->name('admin.users.update');
    Route::delete('/{user}/destroy', [UserController::class, 'destroy'])->name('admin.users.destroy');

    Route::get('/{week}/lock', [CalendarController::class, 'lock'])->name('admin.calendar.lock');
    Route::get('/{week}/export', [CalendarController::class, 'export'])->name('admin.calendar.export');
    Route::post('/{day}/{user}/destroy', [CalendarController::class, 'destroy'])->name('admin.calendar.userdestroy');

    Route::post('/pouzivatelia/vytvorit', [UserController::class, 'add'])->name('admin.pouzivatelia.add');
});

Route::prefix('/profil')->controller(ProfileController::class)->middleware('role:3')->group(function () {
    Route::get('/', 'index')->name('profile.index');
    Route::get('/{user}', 'show')->name('profile.show');
});

Route::middleware(['role:3'])->name('files.')->group(function () {
    Route::post('/uploads', [FileUploadController::class, 'store'])->name('store');
    Route::delete('/upload/{file}/destroy', [FileUploadController::class, 'destroy'])->name('destroy');
    Route::get('/upload/{file}/download', [FileUploadController::class, 'download'])->name('download');
    Route::get('/upload/{file}/show', [FileUploadController::class, 'show'])->name('show');
});

Route::middleware(['allowed'])->group(function () {
    Route::prefix('/nastavenia')->controller(SettingsController::class)->group(function () {
        Route::get('/', 'index')->name('settings.index');
        Route::get('/heslo', 'editPassword')->name('settings.password');
    });

    Route::prefix('/dovolenka')->controller(HolidayController::class)->group(function () {
        Route::get('/', 'index')->name('holiday.index');
        Route::post('/save', 'store')->name('holiday.store');
        Route::patch('/{holiday}/end', 'end')->name('holiday.end');
        Route::delete('/{holiday}/destroy', 'destroy')->name('holiday.destroy');
    });

    Route::prefix('/users')->controller(UserController::class)->group(function () {
        Route::get('/change-password', 'changePassword')->name('changePassword');
        Route::post('/change-password', 'changePasswordSave')->name('postChangePassword');
    });

    Route::prefix('/nahlasenie-chyby')->controller(BugReportController::class)->group(function () {
        Route::get('/', 'index')->name('bugreport.index');
        Route::post('/store', 'store')->name('bugreport.store');
        Route::get('/{bug}/destroy', 'destroy')->name('bugreport.destroy');
    });

    Route::get('/upload/{file}/download', [FileUploadController::class, 'download'])->name('files.download');
});

Route::middleware(['guest'])->prefix('/zabudnute-heslo')->group(function () {
    Route::get('/', [ForgottenPasswordController::class, 'index'])->name('password.index');
    Route::post('/send', [ForgottenPasswordController::class, 'send'])->name('password.send');
    Route::get('/{token}', [ForgottenPasswordController::class, 'show'])->name('password.reset');
    Route::post('/store', [ForgottenPasswordController::class, 'store'])->name('password.store');
});
