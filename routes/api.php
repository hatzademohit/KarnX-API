<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Configure\PermissionController;
use App\Http\Controllers\Api\Configure\RoleController;
use App\Http\Controllers\Api\Configure\UserController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;   
use App\Http\Controllers\Api\Auth\UserActivateAccountController;
use App\Http\Controllers\Api\Configure\PermissionHasAccessController;
use App\Http\Controllers\Api\Auth\UserProfileController;
use App\Http\Controllers\Api\Configure\ClientController;
use App\Http\Controllers\Api\Configure\BookingStatusController;
use App\Http\Controllers\Api\Configure\AssetController;
use App\Http\Controllers\Api\Configure\ModuleController;
use App\Http\Controllers\Api\BookingInquiries\BookingInquiriesController;
use App\Http\Controllers\Api\FormFieldsData\AirportCitiesController;
use App\Http\Controllers\Api\FormFieldsData\MedicalSupportsController;
use App\Http\Controllers\Api\FormFieldsData\AirCraftTypesController;
use App\Http\Controllers\Api\FormFieldsData\CrewRequirementsController;
use App\Http\Controllers\Api\FormFieldsData\TravelingPurposeController;
use App\Http\Controllers\Api\FormFieldsData\CateringDietaryController;
use App\Http\Controllers\Api\FormFieldsData\RequiredDocumentOptionController;
use App\Http\Controllers\Api\Dashboard\KXManagerController;
use App\Http\Controllers\Api\Dashboard\TravelAgentController;
use App\Http\Controllers\Api\Dashboard\AircraftOperatorController;
use App\Http\Controllers\Api\InquiryDetails\InquiryDetailsController;
use App\Http\Controllers\Api\InquiryOperators\KXManager\InquiryOperatorsController;
use App\Http\Controllers\Api\InquiryQuotes\InquiryQuoteController;
use App\Http\Controllers\Api\FormFieldsData\CancellationPoliciesController;
use App\Http\Controllers\Api\FormFieldsData\AvailableAmenitiesController;
use App\Http\Controllers\Api\Payment\RazorpayController;
use App\Http\Controllers\Api\TravellersDetails\TravellersDetailsController;

// Public route for user login
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword']);
Route::post('/activate-account', [UserActivateAccountController::class, 'activateAccount']);
// Route::get('/auth-check', [AuthController::class, 'authCheck']);
// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/check-token', [AuthController::class, 'checkToken']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('profile', UserProfileController::class);
    Route::post('/change-password', [UserProfileController::class, 'changePassword']);
    
    /** Configure/Setup Routes */ 
    Route::apiResource('permission', PermissionController::class);
    Route::apiResource('role', RoleController::class);
    Route::apiResource('user', UserController::class);  
    Route::apiResource('clients', ClientController::class); 
    Route::apiResource('booking-status', BookingStatusController::class); 
    Route::apiResource('assets', AssetController::class);

    /** Permission Access Assign Routes */ 
    Route::prefix('access-permission')->group(function () {
        // Role permissions
        Route::post('/role/{roleId}', [PermissionHasAccessController::class, 'assignPermissionsToRole']);
        Route::get('/role/{roleId}', [PermissionHasAccessController::class, 'getRolePermissions']);
        Route::get('/role/{roleId}/has/{permission}', [PermissionHasAccessController::class, 'roleHasPermission']);

        // User permissions
        Route::post('/user/{userId}', [PermissionHasAccessController::class, 'assignPermissionsToUser']);
        Route::get('/user/{userId}', [PermissionHasAccessController::class, 'getUserPermissions']);
        Route::get('/user/{userId}/has/{permission}', [PermissionHasAccessController::class, 'userHasPermission']);

        Route::apiResource('modules', ModuleController::class);
    });

    /** Form Fields Data Routes */ 
    Route::prefix('form-fields-data')->group(function () {
        Route::apiResource('airport-cities', AirportCitiesController::class);
        Route::apiResource('medical-supports', MedicalSupportsController::class);
        Route::apiResource('aircraft-types', AirCraftTypesController::class);
        Route::apiResource('crew-requirements', CrewRequirementsController::class);
        Route::apiResource('travel-purposes', TravelingPurposeController::class);
        Route::apiResource('catering-dietary', CateringDietaryController::class);
        Route::apiResource('required-document-option', RequiredDocumentOptionController::class);
        Route::apiResource('cancelation-policies', CancellationPoliciesController::class);
        Route::apiResource('available-amenities', AvailableAmenitiesController::class);
    });
    /** Booking Inquiries Routes */ 
    Route::apiResource('booking-inquiries', BookingInquiriesController::class);

    /**Dashboard Routes */
    Route::prefix('dashboard')->group(function () {
        Route::get('/kxmanager-cardcount', [KXManagerController::class, 'cardCount']);
        Route::get('/kxmanager-activitytimeline', [KXManagerController::class, 'getActivityTimeline']);
        Route::get('/kxmanager-prioritytask', [KXManagerController::class, 'getPriorityTask']);
        Route::get('/kxmanager-charter-inquiries', [KXManagerController::class, 'getCharterInquiries']);

        Route::get('/travelagent-cardcount', [TravelAgentController::class, 'cardCount']);
        Route::get('/travelagent-charter-inquiries', [TravelAgentController::class, 'getCharterInquiries']);

        Route::get('/aircraft-operator-cardcount', [AircraftOperatorController::class, 'cardCount']);
        Route::get('/aircraft-operator-charter-inquiries', [AircraftOperatorController::class, 'getCharterInquiries']);
    });

    /**Inquiry Details Routes */
    Route::prefix('inquiry-details')->group(function () {
        Route::get('/get-details/{id}', [InquiryDetailsController::class, 'index']);
    });

    /**Inquiry Details Operator Routes */
    Route::prefix('inquiry-operator')->group(function () {
        Route::post('/get-operators', [InquiryOperatorsController::class, 'getOperators']);
        Route::get('/get-assigned-operators', [InquiryOperatorsController::class, 'getAssignedOperators']);
        Route::post('/operators-assign', [InquiryOperatorsController::class, 'assignOperators']);
        Route::delete('/operators-remove/{id}', [InquiryOperatorsController::class, 'removeOperator']);        
    });

    /**Inquiry Quotes Routes */
    Route::prefix('inquiry-quotes')->group(function () {
        Route::get('/get-aircraft', [InquiryQuoteController::class, 'getMyAircraft']);       
        Route::get('/get-booking-flight-details/{inquiryId}', [InquiryQuoteController::class, 'getBookingFlightDetails']);       
        Route::post('/submit-quote', [InquiryQuoteController::class, 'submitQuote']);    
        Route::get('/edit-quote/{inquiryId}', [InquiryQuoteController::class, 'editQuote']); 
        Route::get('/get-quoted-quotes/{inquiryId}', [InquiryQuoteController::class, 'getQuotedQuotes']);
        Route::post('/reject-quote', [InquiryQuoteController::class, 'rejectQuote']);
        Route::post('/approve-quote', [InquiryQuoteController::class, 'approveQuote']);
        Route::post('/accept-quote', [InquiryQuoteController::class, 'acceptQuote']);
        Route::post('/confirm-booking', [InquiryQuoteController::class, 'confirmBooking']);
    });

    /**Inquiry Booking Travellers Routes */
    Route::prefix('booking-travellers')->group(function () {
        Route::get('/get-traveller-details/{inquiryId}', [TravellersDetailsController::class, 'getTravellerDetails']);
        Route::get('/get-traveller-passanger', [TravellersDetailsController::class, 'getTravellerPassengerData']);
    });

    /**Inquiry booking payments Routes */
    Route::prefix('booking-payment')->group(function () {
        Route::post('/create-order', [RazorpayController::class, 'createOrder']);
        Route::post('/verify-payment', [RazorpayController::class, 'verifyPayment']);
    });

});






