<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AwardController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DraftController;
use App\Http\Controllers\Api\FreeAgentController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\LeagueHistoryController;
use App\Http\Controllers\Api\PlayerController;
use App\Http\Controllers\Api\PlayoffController;
use App\Http\Controllers\Api\RosterController;
use App\Http\Controllers\Api\SeasonController;
use App\Http\Controllers\Api\StandingController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\TradeController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\VenueController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/teams', [TeamController::class, 'store'])->middleware('role:admin');
    Route::get('/teams/divisions', [TeamController::class, 'divisions'])->middleware('role:admin');
    Route::put('/players/{id}/ratings', [PlayerController::class, 'updateRatings'])->middleware('role:admin');
    Route::post('/free-agents/coaches', [FreeAgentController::class, 'storeCoach'])->middleware('role:admin');
    Route::post('/league-history', [LeagueHistoryController::class, 'store'])->middleware('role:admin');
    Route::post('/trades', [TradeController::class, 'store'])->middleware('role:admin,team');
    Route::get('/dashboard/admin', [DashboardController::class, 'admin'])->middleware('role:admin');
    Route::get('/dashboard/team', [DashboardController::class, 'team'])->middleware('role:team,admin');
    Route::get('/homecourt', [VenueController::class, 'homecourt'])->middleware('role:team,admin');
    Route::get('/season', [SeasonController::class, 'season'])->middleware('role:admin');
});

// Public (and auth) read endpoints used by React
Route::get('/seasons', [SeasonController::class, 'seasons']);
Route::get('/conferences', [TeamController::class, 'conferences']);
Route::get('/divisions', [TeamController::class, 'divisions']);
Route::get('/teams', [TeamController::class, 'index']);
Route::get('/rosters', [RosterController::class, 'index']);
Route::get('/coaches/staff', [RosterController::class, 'coaches']);
Route::get('/venues', [VenueController::class, 'venues']);
Route::get('/owners', [VenueController::class, 'owners']);
Route::get('/homecourt', [VenueController::class, 'homecourt']);
Route::get('/contracts', [ContractController::class, 'index']);
Route::get('/transactions', [TransactionController::class, 'index']);
Route::get('/standings', [StandingController::class, 'index']);
Route::get('/standings', [StandingController::class, 'index']);
Route::middleware('admin')->group(function () {
    Route::patch('/standings/{standing}', [StandingController::class, 'update']);
});
Route::get('/playoffs', [PlayoffController::class, 'index']);
Route::get('/awards', [AwardController::class, 'index']);
Route::get('/league-history', [LeagueHistoryController::class, 'index']);
Route::get('/drafts', [DraftController::class, 'index']);
Route::get('/draft-picks', [DraftController::class, 'ownedPicks']);
Route::get('/free-agents/players', [FreeAgentController::class, 'players']);
Route::get('/free-agents/coaches', [FreeAgentController::class, 'coaches']);
Route::get('/games', [GameController::class, 'index']);
Route::get('/dashboard/fan', [DashboardController::class, 'fan']);
