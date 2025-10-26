<?php

use Illuminate\Support\Facades\Route;
use Prasso\Church\Http\Controllers\Auth\AuthController;
use Prasso\Church\Http\Controllers\EventController;
use Prasso\Church\Http\Controllers\FinancialController;
use Prasso\Church\Http\Controllers\GroupController;
use Prasso\Church\Http\Controllers\PledgeController;
use Prasso\Church\Http\Controllers\PrayerRequestController;
use Prasso\Church\Http\Controllers\VolunteerController;
use Prasso\Church\Http\Controllers\GroupReportController;
use Prasso\Church\Http\Controllers\VolunteerReportController;
use Prasso\Church\Http\Controllers\VolunteerSkillController;
use Prasso\Church\Http\Controllers\AvailabilityController;
use Prasso\Church\Http\Controllers\Api\PastoralCareController;
use Prasso\Church\Http\Controllers\Api\PrayerRequestController as ApiPrayerRequestController;
use Prasso\Church\Http\Controllers\Api\PastoralVisitController as ApiPastoralVisitController;
use Prasso\Church\Http\Controllers\Api\AttendanceController as ApiAttendanceController;
use Prasso\Church\Http\Controllers\Api\AttendanceGroupController as ApiAttendanceGroupController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/user', [AuthController::class, 'user']);
    });
    
    // Financial routes
    Route::prefix('financial')->group(function () {
        // Transactions
        Route::get('/history', [FinancialController::class, 'getGivingHistory']);
        Route::get('/summary', [FinancialController::class, 'getFinancialSummary']);
        Route::get('/statement/{year?}', [FinancialController::class, 'generateContributionStatement']);
        Route::post('/transactions', [FinancialController::class, 'recordTransaction']);
        
        // Pledges
        Route::apiResource('pledges', PledgeController::class);
        Route::post('pledges/{pledge}/payments', [PledgeController::class, 'recordPayment']);
        Route::get('pledges/{pledge}/transactions', [PledgeController::class, 'transactions']);
    });
    
    // Event management routes
    Route::prefix('events')->group(function () {
        // Event management
        Route::get('/', [EventController::class, 'index']);
        Route::post('/', [EventController::class, 'store']);
        Route::get('/stats/attendance', [EventController::class, 'attendanceStats']);
        
        // Specific event operations
        Route::prefix('{event}')->group(function () {
            Route::get('/', [EventController::class, 'show']);
            Route::put('/', [EventController::class, 'update']);
            Route::delete('/', [EventController::class, 'destroy']);
            
            // Event occurrences
            Route::get('/occurrences', [EventController::class, 'occurrences']);
            
            // Member attendance
            Route::get('/members/{member}/attendance', [EventController::class, 'memberAttendance']);
        });
        
        // Occurrence operations
        Route::prefix('occurrences/{occurrence}')->group(function () {
            Route::get('/attendance', [EventController::class, 'attendance']);
            Route::post('/attendance', [EventController::class, 'recordAttendance']);
            Route::post('/check-in', [EventController::class, 'checkIn']);
            Route::post('/check-out', [EventController::class, 'checkOut']);
        });
    });
    
    // Group management routes
    Route::prefix('groups')->group(function () {
        Route::get('/', [GroupController::class, 'index']);
        Route::post('/', [GroupController::class, 'store']);
        Route::get('/{group}', [GroupController::class, 'show']);
        Route::put('/{group}', [GroupController::class, 'update']);
        Route::delete('/{group}', [GroupController::class, 'destroy']);
        
        // Group membership
        Route::post('/{group}/members', [GroupController::class, 'addMember']);
        Route::put('/{group}/members/{member}', [GroupController::class, 'updateMember']);
        Route::delete('/{group}/members/{member}', [GroupController::class, 'removeMember']);
        
        // Group leadership
        Route::post('/{group}/leaders/{member}', [GroupController::class, 'addLeader']);
        Route::delete('/{group}/leaders/{member}', [GroupController::class, 'removeLeader']);
        
        // Group prayer requests
        Route::get('/{group}/prayer-requests', [PrayerRequestController::class, 'groupPrayerRequests']);
    });
    
    // Prayer request routes
    Route::apiResource('prayer-requests', PrayerRequestController::class)->except(['index']);
    Route::get('/prayer-requests', [PrayerRequestController::class, 'index']);
    Route::post('/prayer-requests/{prayerRequest}/pray', [PrayerRequestController::class, 'incrementPrayerCount']);
    
    // Group report routes
    Route::prefix('reports/groups')->group(function () {
        Route::get('/membership-stats', [GroupReportController::class, 'membershipStats']);
        Route::get('/growth', [GroupReportController::class, 'growthOverTime']);
        Route::get('/engagement', [GroupReportController::class, 'engagementMetrics']);
        Route::get('/demographics', [GroupReportController::class, 'demographics']);
        Route::get('/{group}/report', [GroupReportController::class, 'groupReport']);
    });
    
    // Volunteer report routes
    Route::prefix('reports/volunteers')->group(function () {
        Route::get('/assignment-stats', [VolunteerReportController::class, 'assignmentStats']);
        Route::get('/hours-by-position', [VolunteerReportController::class, 'hoursByPosition']);
        Route::get('/hours-over-time', [VolunteerReportController::class, 'hoursOverTime']);
        Route::get('/demographics', [VolunteerReportController::class, 'demographics']);
        Route::get('/top-volunteers', [VolunteerReportController::class, 'topVolunteers']);
        Route::get('/positions/{position}/report', [VolunteerReportController::class, 'positionReport']);
    });
    
    // Volunteer management routes
    Route::prefix('volunteers')->group(function () {
        // Volunteer Positions
        Route::get('/positions', [VolunteerController::class, 'indexPositions']);
        Route::post('/positions', [VolunteerController::class, 'storePosition']);
        Route::get('/positions/{position}', [VolunteerController::class, 'showPosition']);
        Route::put('/positions/{position}', [VolunteerController::class, 'updatePosition']);
        Route::delete('/positions/{position}', [VolunteerController::class, 'destroyPosition']);
        
        // Position Assignments
        Route::post('/positions/{position}/assign', [VolunteerController::class, 'assignMember']);
        Route::delete('/positions/{position}/unassign/{member}', [VolunteerController::class, 'unassignMember']);
        
        // Volunteer Assignments
        Route::get('/assignments', [VolunteerController::class, 'indexAssignments']);
        Route::put('/assignments/{assignment}', [VolunteerController::class, 'updateAssignment']);
        
        // Skills Management
        Route::prefix('skills')->group(function () {
            Route::get('/', [VolunteerSkillController::class, 'index']);
            Route::post('/', [VolunteerSkillController::class, 'store']);
            Route::put('/{skill}', [VolunteerSkillController::class, 'update']);
            Route::delete('/{skill}', [VolunteerSkillController::class, 'destroy']);
            
            // Member Skills
            Route::post('/members/{member}/add', [VolunteerSkillController::class, 'addMemberSkill']);
            Route::put('/members/{member}/update/{skill}', [VolunteerSkillController::class, 'updateMemberSkill']);
            Route::delete('/members/{member}/remove/{skill}', [VolunteerSkillController::class, 'removeMemberSkill']);
            
            // Position Skills
            Route::post('/positions/{position}/add', [VolunteerSkillController::class, 'addPositionSkill']);
            Route::put('/positions/{position}/update/{skill}', [VolunteerSkillController::class, 'updatePositionSkill']);
            Route::delete('/positions/{position}/remove/{skill}', [VolunteerSkillController::class, 'removePositionSkill']);
            
            // Search
            Route::post('/find-members', [VolunteerSkillController::class, 'findMembersWithSkills']);
        });
        
        // Availability Management
        Route::prefix('availability')->group(function () {
            Route::get('/', [AvailabilityController::class, 'index']);
            Route::post('/', [AvailabilityController::class, 'store']);
            Route::get('/{id}', [AvailabilityController::class, 'show']);
            Route::put('/{id}', [AvailabilityController::class, 'update']);
            Route::delete('/{id}', [AvailabilityController::class, 'destroy']);
            Route::get('/member/{memberId}', [AvailabilityController::class, 'getByMember']);
            Route::post('/members/{member}', [AvailabilityController::class, 'store']);
            
            // Check availability
            Route::post('/check', [AvailabilityController::class, 'findAvailableVolunteers']);
            Route::get('/members/{member}/check', [AvailabilityController::class, 'checkMemberAvailability']);
        });
    });
    
    // Pastoral Care Module Routes
    Route::prefix('pastoral-care')->name('pastoral-care.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [PastoralCareController::class, 'dashboard']);
        
        // Prayer Requests
        Route::apiResource('prayer-requests', ApiPrayerRequestController::class);
        Route::post('prayer-requests/{prayerRequest}/pray', [ApiPrayerRequestController::class, 'pray']);
        
        // Pastoral Visits
        Route::apiResource('visits', ApiPastoralVisitController::class);
        Route::post('visits/{visit}/start', [ApiPastoralVisitController::class, 'start']);
        Route::post('visits/{visit}/complete', [ApiPastoralVisitController::class, 'complete']);
        Route::get('visits/calendar/events', [ApiPastoralVisitController::class, 'calendar']);
        
        // Member-specific routes
        Route::prefix('members/{member}')->group(function () {
            Route::get('prayer-requests', [ApiPrayerRequestController::class, 'index']);
            Route::get('visits', [ApiPastoralVisitController::class, 'index']);
        });
        
        // Family-specific routes
        Route::prefix('families/{family}')->group(function () {
            Route::get('prayer-requests', [ApiPrayerRequestController::class, 'index']);
            Route::get('visits', [ApiPastoralVisitController::class, 'index']);
        });
    });
    
    // Attendance Module Routes
    Route::prefix('attendance')->group(function () {
        // Events
        Route::apiResource('events', ApiAttendanceController::class);
        
        // Attendance Records
        Route::post('events/{eventId}/record', [ApiAttendanceController::class, 'recordAttendance']);
        Route::post('events/{eventId}/bulk-record', [ApiAttendanceController::class, 'bulkRecordAttendance']);
        Route::post('records/{recordId}/check-out', [ApiAttendanceController::class, 'checkOut']);
        
        // Statistics
        Route::get('statistics', [ApiAttendanceController::class, 'getStatistics']);
        
        // Groups
        Route::apiResource('groups', ApiAttendanceGroupController::class);
        
        // Group Statistics
        Route::get('groups/{groupId}/statistics', [ApiAttendanceGroupController::class, 'getStatistics']);
        
        // Member-specific routes
        Route::prefix('members/{member}')->group(function () {
            Route::get('attendance', [ApiAttendanceController::class, 'index']);
            Route::get('groups', [ApiAttendanceGroupController::class, 'index']);
        });
        
        // Family-specific routes
        Route::prefix('families/{family}')->group(function () {
            Route::get('attendance', [ApiAttendanceController::class, 'index']);
        });
    });
});
