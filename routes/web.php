<?php

use App\Enums\TeamFeature;
use App\Http\Controllers\Absence\AbsenceController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\EmailChangeController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordRequestController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Calendar\AssignmentController;
use App\Http\Controllers\Calendar\CalendarController;
use App\Http\Controllers\Calendar\ScheduleExportController;
use App\Http\Controllers\Calendar\WeekLockController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\Position\PositionController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\Rozpis\RozpisController;
use App\Http\Controllers\Rozpis\RozpisExportController;
use App\Http\Controllers\Rozpis\RozpisPublishController;
use App\Http\Controllers\Settings\EmailController;
use App\Http\Controllers\Settings\NameController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Team\TeamSettingController;
use App\Http\Controllers\Team\TeamSwitchController;
use App\Http\Middleware\SetCurrentTeam;
use Illuminate\Support\Facades\Route;
use Laravel\Pennant\Middleware\EnsureFeaturesAreActive;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

/*
| Guests may read these, but they still render the authenticated navigation, whose entries are
| gated on team-scoped permissions — with no team in the registrar every `@can` there is false and
| the dropdown silently loses Správa kina and Pozície. `optional` establishes the team when there
| is one instead of demanding it.
*/
Route::middleware(SetCurrentTeam::class.':optional')->group(function () {
    // The landing page for a signed-out visitor. A signed-in user lands on their calendar
    // instead - `/` is never a dead end, it always resolves to whatever "home" means for them.
    Route::get('/', fn () => auth()->check() ? to_route('calendar.index') : view('welcome.welcome'))
        ->name('welcome.index');

    Route::get('/pomoc', [HelpController::class, 'index'])->name('help');
    Route::view('/ochrana-osobnych-udajov', 'privacy.index')->name('privacy');
});

Route::get('/overenie-emailu/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');

Route::get('/email-overeny', [EmailVerificationController::class, 'verified'])
    ->name('verification.verified');

Route::prefix('/zmena-emailu/{id}/{token}')
    ->middleware(['signed', 'throttle:6,1'])
    ->controller(EmailChangeController::class)
    ->name('email-change.')
    ->group(function () {
        Route::get('/', 'show')->name('show');
        Route::post('/', 'approve')->name('approve');
        Route::delete('/', 'decline')->name('decline');
        Route::get('/overit', 'verify')->name('verify');
    });

Route::post('/overenie-emailu/znova', [EmailVerificationController::class, 'resendGuest'])
    ->middleware('throttle:mail-recipient')
    ->name('verification.resend_guest');

Route::middleware('auth')->group(function () {
    Route::get('/overenie-emailu', [EmailVerificationController::class, 'notice'])->name('verification.notice');

    Route::post('/overenie-emailu', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

Route::middleware('guest')->group(function () {
    Route::get('/prihlasenie', [LoginController::class, 'index'])->name('login');
    Route::post('/prihlasenie', [LoginController::class, 'authenticate'])
        ->name('login.auth')
        ->middleware('throttle:guest-form');

    Route::get('/registracia', [RegisterController::class, 'index'])->name('register');
    Route::post('/registracia', [RegisterController::class, 'store'])
        ->name('register.store')
        ->middleware('throttle:guest-form');

    Route::prefix('/zabudnute-heslo')->group(function () {
        Route::get('/', [PasswordRequestController::class, 'index'])->name('password.index');
        Route::post('/', [PasswordRequestController::class, 'send'])
            ->name('password.send')
            ->middleware('throttle:mail-recipient');

        Route::get('/{token}', [PasswordResetController::class, 'show'])->name('password.reset');

        // Throttled like every other pre-auth POST: the token is long enough that guessing it is
        // not the threat, but this was the one unthrottled way in.
        Route::post('/store', [PasswordResetController::class, 'store'])
            ->name('password.store')
            ->middleware('throttle:guest-form');
    });
});

Route::post('/odhlasenie', LogoutController::class)->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Inside a cinema
|--------------------------------------------------------------------------
| `tenant` = authenticated + active + a current team established. Everything below
| queries team-owned tables, and BelongsToTeam scopes off what SetCurrentTeam puts in the
| registrar, so nothing here may sit outside that group.
*/

Route::middleware('tenant')->group(function () {
    // A week is identified by any date inside it — never a week id, there is no weeks table.
    Route::get('/tyzdne', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/week/{date}', [CalendarController::class, 'show'])->name('calendar.show');

    Route::post('/zapis', [AssignmentController::class, 'store'])->name('assignments.store');
    Route::delete('/zapis/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');

    Route::prefix('/dovolenka')->controller(AbsenceController::class)->group(function () {
        Route::get('/', 'index')->name('absences.index');
        Route::post('/', 'store')->name('absences.store');
        Route::patch('/{absence}', 'end')->name('absences.end');
        Route::delete('/{absence}', 'destroy')->name('absences.destroy');
    });

    Route::prefix('/nastavenia')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/', [SettingsController::class, 'update'])->name('settings.update');
        Route::put('/meno', [NameController::class, 'update'])->name('settings.name.update');
        Route::put('/email', [EmailController::class, 'update'])
            ->name('settings.email.update')
            ->middleware('throttle:6,1');
        Route::delete('/email', [EmailController::class, 'destroy'])->name('settings.email.destroy');
        Route::put('/heslo', [PasswordController::class, 'update'])
            ->name('settings.password.update')
            ->middleware('throttle:6,1');
    });

    Route::post('/kino/{team}', TeamSwitchController::class)->name('teams.switch');

    /*
    |----------------------------------------------------------------------
    | Admin
    |----------------------------------------------------------------------
    | Authorized in the controller, not by `role:admin` middleware. There is no single "admin"
    | role any more: Manager and HeadManager both reach these, gated by the permission each one
    | actually holds.
    */
    Route::prefix('/admin/pouzivatelia')->name('admin.users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });

    Route::post('/week/{date}/lock', [WeekLockController::class, 'store'])->name('weeks.lock');
    Route::delete('/week/{date}/lock', [WeekLockController::class, 'destroy'])->name('weeks.unlock');
    Route::get('/week/{date}/export', ScheduleExportController::class)->name('schedule.export');

    Route::prefix('/profil')->controller(ProfileController::class)->group(function () {
        Route::get('/', 'index')->name('profile.index');
        Route::get('/{user}', 'show')->name('profile.show');
    });

    Route::get('/sprava-kina', [TeamSettingController::class, 'edit'])->name('team.settings.edit');
    Route::put('/sprava-kina', [TeamSettingController::class, 'update'])->name('team.settings.update');

    // Rozpis: per-cinema module (404 when off), authorized in the controllers.
    Route::middleware(EnsureFeaturesAreActive::using(TeamFeature::Rozpis))->group(function () {
        Route::get('/admin/pozicie', [PositionController::class, 'index'])->name('positions.index');

        Route::get('/tyzden/{date}/rozpis', [RozpisController::class, 'show'])->name('rozpis.show');
        Route::post('/tyzden/{date}/rozpis/kopirovat', [RozpisController::class, 'copy'])->name('rozpis.copy');
        Route::post('/tyzden/{date}/rozpis/kopirovat-tyzden', [RozpisController::class, 'copyWeek'])->name('rozpis.copy-week');

        Route::get('/tyzden/{date}/rozpis/zmeny', [RozpisController::class, 'published'])->name('rozpis.published');

        Route::post('/tyzden/{date}/rozpis/zverejnit', [RozpisPublishController::class, 'store'])->name('rozpis.publish');
        Route::delete('/tyzden/{date}/rozpis/zverejnit', [RozpisPublishController::class, 'destroy'])->name('rozpis.unpublish');

        Route::get('/tyzden/{date}/rozpis/export', RozpisExportController::class)->name('rozpis.export');
    });
});
