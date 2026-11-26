<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PasswordRevealController;
use App\Http\Controllers\PhysicianController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfilePhotoController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitController;
use App\Http\Middleware\LogUserActivity;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware(['auth', 'active', 'auth.session', LogUserActivity::class])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile-photos/{user}', ProfilePhotoController::class)->name('profile.photo');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
    Route::get('/patients/{patient}', [PatientController::class, 'show'])->name('patients.show');

    Route::middleware('role:Receptionist,Administrator')->group(function () {
        Route::get('/patients-create', [PatientController::class, 'create'])->name('patients.create');
        Route::post('/patients', [PatientController::class, 'store'])->name('patients.store');
        Route::get('/patients/{patient}/edit', [PatientController::class, 'edit'])->name('patients.edit');
        Route::put('/patients/{patient}', [PatientController::class, 'update'])->name('patients.update');
    });

    Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules.index');
    Route::get('/appointments', [AppointmentController::class, 'index'])
        ->middleware('role:Receptionist,Physician,Administrator')
        ->name('appointments.index');
    Route::middleware('role:Receptionist,Administrator')->group(function () {
        Route::get('/appointments/availability', [AppointmentController::class, 'availability'])->name('appointments.availability');
        Route::get('/appointments/create', [AppointmentController::class, 'create'])->name('appointments.create');
        Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
        Route::get('/appointments/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show');
        Route::patch('/appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->name('appointments.reschedule');
        Route::patch('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
        Route::patch('/appointments/{appointment}/no-show', [AppointmentController::class, 'noShow'])->name('appointments.no-show');
        Route::post('/appointments/{appointment}/verify', [AppointmentController::class, 'verify'])->name('appointments.verify');
        Route::post('/appointments/{appointment}/resend-confirmation', [AppointmentController::class, 'resendConfirmation'])->name('appointments.resend-confirmation');
        Route::post('/appointments/{appointment}/arrive', [AppointmentController::class, 'arrive'])->name('appointments.arrive');
        Route::get('/walk-ins/create', [VisitController::class, 'createWalkIn'])->name('walkins.create');
        Route::post('/walk-ins', [VisitController::class, 'storeWalkIn'])->name('walkins.store');
    });

    Route::get('/visits', [VisitController::class, 'index'])->name('visits.index');
    Route::get('/visits/{visit}', [VisitController::class, 'show'])->name('visits.show');
    Route::post('/visits/{visit}/vital-signs', [VisitController::class, 'vitalSigns'])->middleware('role:Nurse')->name('visits.vital-signs');
    Route::post('/visits/{visit}/consultation', [VisitController::class, 'consultation'])->middleware('role:Physician')->name('visits.consultation');

    Route::middleware('role:Administrator')->group(function () {
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::delete('/patients/{patient}', [PatientController::class, 'destroy'])->name('patients.destroy');
        Route::post('/users/{user}/password/reveal', PasswordRevealController::class)
            ->middleware('throttle:10,1')->name('users.password.reveal');
        Route::resource('physicians', PhysicianController::class)->except(['show','destroy']);
        Route::patch('/physicians/{physician}/deactivate', [PhysicianController::class, 'deactivate'])->name('physicians.deactivate');
        Route::post('/schedules', [ScheduleController::class, 'store'])->name('schedules.store');
        Route::put('/schedules/{schedule}', [ScheduleController::class, 'update'])->name('schedules.update');
        Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::resource('users', UserController::class);
        Route::patch('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
    });
});
