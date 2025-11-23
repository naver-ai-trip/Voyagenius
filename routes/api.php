<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\ChecklistItemController;
use App\Http\Controllers\CheckpointImageController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\FlightController;
use App\Http\Controllers\ItineraryItemController;
use App\Http\Controllers\MapCheckpointController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\SearchTrendController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TranslationController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\TripDiaryController;
use App\Http\Controllers\TripParticipantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Social Authentication (Public routes)
Route::get('/auth/google', [SocialAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

Route::middleware('auth:sanctum')->group(function () {
    // Trip Management
    Route::apiResource('trips', TripController::class);

    // Trip Participants (nested under trips)
    Route::apiResource('trips.participants', TripParticipantController::class);

    // Trip Shares (nested under trips)
    Route::get('/trips/{trip}/shares', [ShareController::class, 'index'])->name('trips.shares.index');
    Route::post('/trips/{trip}/shares', [ShareController::class, 'store'])->name('trips.shares.store');
    
    // Public share access (by token)
    Route::get('/shares/{token}', [ShareController::class, 'show'])->name('shares.show');
    Route::delete('/shares/{share}', [ShareController::class, 'destroy'])->name('shares.destroy');

    // Trip Diaries
    Route::apiResource('diaries', TripDiaryController::class);

    // Checklist Items
    Route::apiResource('checklist-items', ChecklistItemController::class);

    // Itinerary Items
    Route::apiResource('itinerary-items', ItineraryItemController::class);

    // Map Checkpoints
    Route::apiResource('checkpoints', MapCheckpointController::class);

    // Checkpoint Images (nested under checkpoints)
    Route::apiResource('checkpoints.images', CheckpointImageController::class)->except(['create', 'edit']);

    // Reviews
    Route::apiResource('reviews', ReviewController::class);

    // Favorites
    Route::apiResource('favorites', FavoriteController::class)->except(['update']);

    // Comments
    Route::apiResource('comments', CommentController::class);

    // Notifications
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::patch('/notifications/{notification}/mark-read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
    Route::apiResource('notifications', NotificationController::class)->only(['index', 'show', 'destroy']);

    // Tags
    Route::get('/tags/popular', [TagController::class, 'popular'])->name('tags.popular');
    Route::post('/tags/attach', [TagController::class, 'attach'])->name('tags.attach');
    Route::delete('/tags/detach', [TagController::class, 'detach'])->name('tags.detach');
    Route::apiResource('tags', TagController::class)->only(['index', 'show']);

    // Translations (NAVER Papago, OCR, Speech)
    Route::post('/translations/text', [TranslationController::class, 'translateText'])->name('translations.text');
    Route::post('/translations/image', [TranslationController::class, 'translateImage'])->name('translations.image');
    Route::post('/translations/ocr', [TranslationController::class, 'translateOcr'])->name('translations.ocr');
    Route::post('/translations/speech', [TranslationController::class, 'translateSpeech'])->name('translations.speech');
    Route::get('/translations', [TranslationController::class, 'index'])->name('translations.index');
    Route::get('/translations/{translation}', [TranslationController::class, 'show'])->name('translations.show');
    Route::delete('/translations/{translation}', [TranslationController::class, 'destroy'])->name('translations.destroy');

    // Place Search & Management
    Route::post('/places/search', [PlaceController::class, 'search'])->name('places.search');
    Route::post('/places/search-nearby', [PlaceController::class, 'searchNearby'])->name('places.search-nearby');
    Route::get('/places/naver/{naverPlaceId}', [PlaceController::class, 'getByNaverId'])->name('places.naver');
    Route::apiResource('places', PlaceController::class);

    // NAVER Maps API (Geocoding & Directions)
    Route::post('/maps/geocode', [MapController::class, 'geocode'])->name('maps.geocode');
    Route::post('/maps/reverse-geocode', [MapController::class, 'reverseGeocode'])->name('maps.reverse-geocode');
    Route::post('/maps/directions', [MapController::class, 'directions'])->name('maps.directions');
    Route::post('/maps/directions-waypoints', [MapController::class, 'directionsWithWaypoints'])->name('maps.directions-waypoints');

    // NAVER Search Trends (DataLab)
    Route::post('/search-trends/keywords', [SearchTrendController::class, 'getKeywordTrends'])->name('search-trends.keywords');
    Route::post('/search-trends/compare', [SearchTrendController::class, 'compareKeywords'])->name('search-trends.compare');
    Route::post('/search-trends/demographics', [SearchTrendController::class, 'getAgeGenderTrends'])->name('search-trends.demographics');
    Route::post('/search-trends/devices', [SearchTrendController::class, 'getDeviceTrends'])->name('search-trends.devices');
    Route::post('/search-trends/destination-popularity', [SearchTrendController::class, 'analyzeDestinationPopularity'])->name('search-trends.destination-popularity');
    Route::post('/search-trends/seasonal-insights', [SearchTrendController::class, 'getSeasonalInsights'])->name('search-trends.seasonal-insights');

    // Amadeus Hotel APIs
    Route::post('/hotels/search', [HotelController::class, 'search'])->name('hotels.search');
    Route::post('/hotels/offers', [HotelController::class, 'searchOffers'])->name('hotels.offers');
    Route::get('/hotels/offers/{offerId}', [HotelController::class, 'getOffer'])->name('hotels.offers.show');
    Route::post('/hotels/ratings', [HotelController::class, 'getRatings'])->name('hotels.ratings');
    Route::post('/hotels/bookings', [HotelController::class, 'createBooking'])->name('hotels.bookings.store');
    Route::post('/hotels/search-with-offers', [HotelController::class, 'searchWithOffers'])->name('hotels.search-with-offers');

    // Amadeus Flights
    Route::post('/flights/search', [FlightController::class, 'searchOffers'])->name('flights.search');
});
