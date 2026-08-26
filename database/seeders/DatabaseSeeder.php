<?php

namespace Database\Seeders;

use App\Models\Award;
use App\Models\Coach;
use App\Models\CoachRating;
use App\Models\Conference;
use App\Models\Contract;
use App\Models\ContractYear;
use App\Models\Draft;
use App\Models\DraftPick;
use App\Models\Game;
use App\Models\LeagueHistory;
use App\Models\Player;
use App\Models\PlayerRating;
use App\Models\Playoff;
use App\Models\PlayoffSeries;
use App\Models\Season;
use App\Models\Standing;
use App\Models\Team;
use App\Models\TeamCoach;
use App\Models\TeamOwner;
use App\Models\TeamRoster;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $s1 = Season::query()->create([
            'name' => '2024-2025',
            'year' => '2024-2025',
            'is_current' => false,
            'starts_at' => '2024-10-01',
            'ends_at' => '2025-06-30',
        ]);
        $s2 = Season::query()->create([
            'name' => '2025-2026',
            'year' => '2025-2026',
            'is_current' => false,
            'starts_at' => '2025-10-01',
            'ends_at' => '2026-06-30',
        ]);
        $s3 = Season::query()->create([
            'name' => '2026-2027',
            'year' => '2026-2027',
            'is_current' => true,
            'starts_at' => '2026-10-01',
            'ends_at' => '2027-06-30',
        ]);

        $east = Conference::query()->create(['name' => 'Eastern', 'abbreviation' => 'EAST']);
        $west = Conference::query()->create(['name' => 'Western', 'abbreviation' => 'WEST']);

        $v1 = Venue::query()->create([
            'name' => 'Smart Araneta Coliseum',
            'city' => 'Quezon City',
            'capacity' => 15000,
        ]);
        $v2 = Venue::query()->create([
            'name' => 'Mall of Asia Arena',
            'city' => 'Pasay',
            'capacity' => 15000,
        ]);

        $t1 = Team::query()->create([
            'name' => 'Manila Kings',
            'abbreviation' => 'MNK',
            'city' => 'Manila',
            'conference_id' => $east->id,
            'venue_id' => $v1->id,
            'primary_color' => '#0B3D91',
            'secondary_color' => '#F4C430',
        ]);
        $t2 = Team::query()->create([
            'name' => 'Cebu Titans',
            'abbreviation' => 'CEB',
            'city' => 'Cebu',
            'conference_id' => $east->id,
            'venue_id' => $v2->id,
            'primary_color' => '#7A0019',
            'secondary_color' => '#FFFFFF',
        ]);
        $t3 = Team::query()->create([
            'name' => 'Davao Blaze',
            'abbreviation' => 'DAV',
            'city' => 'Davao',
            'conference_id' => $west->id,
            'primary_color' => '#E65C00',
            'secondary_color' => '#111111',
        ]);
        $t4 = Team::query()->create([
            'name' => 'Iloilo Storm',
            'abbreviation' => 'ILO',
            'city' => 'Iloilo',
            'conference_id' => $west->id,
            'primary_color' => '#1B7A4E',
            'secondary_color' => '#CFE8D8',
        ]);

        TeamOwner::query()->create(['team_id' => $t1->id, 'name' => 'Ramon Sy', 'company' => 'Metro Sports Group']);
        TeamOwner::query()->create(['team_id' => $t2->id, 'name' => 'Liza Tan', 'company' => 'Visayas Holdings']);
        TeamOwner::query()->create(['team_id' => $t3->id, 'name' => 'Carlos Rivera', 'company' => 'Mindanao Ventures']);
        TeamOwner::query()->create(['team_id' => $t4->id, 'name' => 'Ana Gomez', 'company' => 'Westport Corp']);

        User::query()->create([
            'name' => 'PBA Admin',
            'full_name' => 'PBA Admin',
            'email' => 'admin@pba.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'team_id' => null,
            'is_active' => true,
        ]);
        User::query()->create([
            'name' => 'Team Manager',
            'full_name' => 'Team Manager',
            'email' => 'team@pba.test',
            'password' => Hash::make('password'),
            'role' => 'team',
            'team_id' => $t1->id,
            'is_active' => true,
        ]);

        $playerDefs = [
            ['June', 'Marquez', 28, 76, 210, 'PG', $t1->id, 7, 88],
            ['Rico', 'Santos', 25, 78, 220, 'SG', $t1->id, 11, 84],
            ['Ben', 'Cruz', 27, 80, 235, 'SF', $t1->id, 23, 86],
            ['Omar', 'Lim', 24, 82, 245, 'PF', $t2->id, 15, 82],
            ['Paolo', 'Reyes', 29, 84, 255, 'C', $t2->id, 33, 85],
            ['Jake', 'Navarro', 26, 77, 205, 'PG', $t3->id, 1, 83],
            ['Marco', 'Diaz', 23, 79, 215, 'SG', $t3->id, 9, 81],
            ['Luis', 'Vargas', 30, 81, 230, 'SF', $t4->id, 8, 80],
            ['Noel', 'Garcia', 22, 75, 195, 'PG', $t4->id, 3, 78],
            ['Theo', 'Mendoza', 27, 83, 250, 'C', $t1->id, 44, 87],
        ];

        $players = [];
        foreach ($playerDefs as $i => $def) {
            [$fn, $ln, $age, $ht, $wt, $pos, $teamId, $jersey, $ovr] = $def;
            $player = Player::query()->create([
                'first_name' => $fn,
                'last_name' => $ln,
                'photo_url' => null,
                'age' => $age,
                'height_inches' => $ht,
                'weight_lbs' => $wt,
                'position' => $pos,
            ]);
            $players[] = $player;

            $this->seedRatings($player->id, $s3->id, $ovr);
            if ($i < 4) {
                $this->seedRatings($player->id, $s2->id, max(70, $ovr - 2));
            }

            TeamRoster::query()->create([
                'team_id' => $teamId,
                'player_id' => $player->id,
                'season_id' => $s3->id,
                'jersey_number' => $jersey,
            ]);

            $contract = Contract::query()->create([
                'team_id' => $teamId,
                'player_id' => $player->id,
                'type' => 'player',
                'start_season_id' => $s2->id,
                'end_season_id' => $s3->id,
                'total_salary' => 0,
                'is_active' => true,
            ]);
            $y1 = 8_000_000 + ($ovr * 50_000);
            $y2 = 8_500_000 + ($ovr * 55_000);
            $y3 = 9_000_000 + ($ovr * 60_000);
            ContractYear::query()->create(['contract_id' => $contract->id, 'season_id' => $s1->id, 'salary' => $y1, 'year_number' => 1]);
            ContractYear::query()->create(['contract_id' => $contract->id, 'season_id' => $s2->id, 'salary' => $y2, 'year_number' => 2]);
            ContractYear::query()->create(['contract_id' => $contract->id, 'season_id' => $s3->id, 'salary' => $y3, 'year_number' => 3]);
            $contract->update(['total_salary' => $y1 + $y2 + $y3]);
        }

        // Free agent players
        $fa1 = Player::query()->create([
            'first_name' => 'Andre',
            'last_name' => 'Flores',
            'age' => 31,
            'height_inches' => 78,
            'weight_lbs' => 215,
            'position' => 'SG',
        ]);
        $fa2 = Player::query()->create([
            'first_name' => 'Kyle',
            'last_name' => 'Bautista',
            'age' => 24,
            'height_inches' => 80,
            'weight_lbs' => 225,
            'position' => 'PF',
        ]);
        $this->seedRatings($fa1->id, $s3->id, 79);
        $this->seedRatings($fa2->id, $s3->id, 76);

        $coachDefs = [
            ['Chot Reyes', 'Head Coach', $t1->id, 'A', 'A-', 'B+', 'A', 'A'],
            ['Tim Cone', 'Head Coach', $t2->id, 'A+', 'A', 'A', 'A+', 'A+'],
            ['Yeng Guiao', 'Head Coach', $t3->id, 'A-', 'A', 'B+', 'A', 'A'],
            ['Jong Uichico', 'Head Coach', $t4->id, 'B+', 'A-', 'A-', 'B+', 'B+'],
        ];
        $coaches = [];
        foreach ($coachDefs as $def) {
            [$name, $role, $teamId, $off, $defg, $dev, $lead, $ovr] = $def;
            $coach = Coach::query()->create(['name' => $name, 'role' => $role]);
            $coaches[] = $coach;
            CoachRating::query()->create([
                'coach_id' => $coach->id,
                'season_id' => $s3->id,
                'offense_grade' => $off,
                'defense_grade' => $defg,
                'player_development_grade' => $dev,
                'leadership_grade' => $lead,
                'overall_grade' => $ovr,
            ]);
            TeamCoach::query()->create([
                'team_id' => $teamId,
                'coach_id' => $coach->id,
                'season_id' => $s3->id,
                'role' => $role,
            ]);
            $cContract = Contract::query()->create([
                'team_id' => $teamId,
                'coach_id' => $coach->id,
                'type' => 'coach',
                'start_season_id' => $s3->id,
                'end_season_id' => $s3->id,
                'total_salary' => 12_000_000,
                'is_active' => true,
            ]);
            ContractYear::query()->create([
                'contract_id' => $cContract->id,
                'season_id' => $s3->id,
                'salary' => 12_000_000,
                'year_number' => 1,
            ]);
        }

        $faCoach = Coach::query()->create(['name' => 'Norman Black', 'role' => 'Head Coach']);
        CoachRating::query()->create([
            'coach_id' => $faCoach->id,
            'season_id' => $s3->id,
            'offense_grade' => 'A-',
            'defense_grade' => 'B+',
            'player_development_grade' => 'A',
            'leadership_grade' => 'A',
            'overall_grade' => 'A-',
        ]);
        $faStaff = Coach::query()->create(['name' => 'Alex Compton', 'role' => 'Assistant Coach']);
        CoachRating::query()->create([
            'coach_id' => $faStaff->id,
            'season_id' => $s3->id,
            'offense_grade' => 'B+',
            'defense_grade' => 'B',
            'player_development_grade' => 'A-',
            'leadership_grade' => 'B+',
            'overall_grade' => 'B+',
        ]);

        // Standings current season
        $standingRows = [
            [$t1, $east, 18, 8, 1, 0],
            [$t2, $east, 14, 12, 2, 4],
            [$t3, $west, 16, 10, 1, 0],
            [$t4, $west, 11, 15, 2, 5],
        ];
        foreach ($standingRows as [$team, $conf, $w, $l, $rank, $gb]) {
            $pct = $w + $l > 0 ? round($w / ($w + $l), 3) : 0;
            Standing::query()->create([
                'season_id' => $s3->id,
                'team_id' => $team->id,
                'conference_id' => $conf->id,
                'wins' => $w,
                'losses' => $l,
                'pct' => $pct,
                'gb' => $gb,
                'rank' => $rank,
            ]);
        }

        Game::query()->create([
            'season_id' => $s3->id,
            'home_team_id' => $t1->id,
            'away_team_id' => $t2->id,
            'venue_id' => $v1->id,
            'game_date' => '2026-11-05 19:00:00',
            'home_score' => 102,
            'away_score' => 98,
            'status' => 'final',
        ]);
        Game::query()->create([
            'season_id' => $s3->id,
            'home_team_id' => $t3->id,
            'away_team_id' => $t4->id,
            'game_date' => '2026-11-06 18:30:00',
            'home_score' => 95,
            'away_score' => 88,
            'status' => 'final',
        ]);
        Game::query()->create([
            'season_id' => $s3->id,
            'home_team_id' => $t1->id,
            'away_team_id' => $t3->id,
            'venue_id' => $v1->id,
            'game_date' => '2026-12-01 19:00:00',
            'status' => 'scheduled',
        ]);
        Game::query()->create([
            'season_id' => $s3->id,
            'home_team_id' => $t2->id,
            'away_team_id' => $t4->id,
            'venue_id' => $v2->id,
            'game_date' => '2026-12-03 18:00:00',
            'status' => 'scheduled',
        ]);

        // Transactions
        $tx1 = Transaction::query()->create([
            'season_id' => $s3->id,
            'type' => 'sign',
            'from_team_id' => null,
            'to_team_id' => $t1->id,
            'date' => '2026-09-15',
            'notes' => 'Signed free agent',
            'description' => 'June Marquez signed with Manila Kings',
        ]);
        $tx1->players()->attach($players[0]->id);

        $tx2 = Transaction::query()->create([
            'season_id' => $s3->id,
            'type' => 'trade',
            'from_team_id' => $t2->id,
            'to_team_id' => $t3->id,
            'date' => '2026-10-02',
            'notes' => 'Multi-team deal assets moved',
            'description' => 'Omar Lim traded to Davao Blaze',
        ]);
        $tx2->players()->attach($players[3]->id);

        $tx3 = Transaction::query()->create([
            'season_id' => $s3->id,
            'type' => 'waive',
            'from_team_id' => $t4->id,
            'to_team_id' => null,
            'date' => '2026-10-20',
            'notes' => 'Cleared roster spot',
            'description' => 'Waived for salary flexibility',
        ]);
        $tx3->players()->attach($fa1->id);

        // Playoffs for prior season
        $playoff = Playoff::query()->create([
            'season_id' => $s2->id,
            'name' => '2025-2026 Playoffs',
        ]);
        PlayoffSeries::query()->create([
            'playoff_id' => $playoff->id,
            'season_id' => $s2->id,
            'conference_id' => $east->id,
            'round' => 'conference_finals',
            'team1_id' => $t1->id,
            'team2_id' => $t2->id,
            'winner_team_id' => $t1->id,
            'wins_team1' => 4,
            'wins_team2' => 2,
        ]);
        PlayoffSeries::query()->create([
            'playoff_id' => $playoff->id,
            'season_id' => $s2->id,
            'conference_id' => $west->id,
            'round' => 'conference_finals',
            'team1_id' => $t3->id,
            'team2_id' => $t4->id,
            'winner_team_id' => $t3->id,
            'wins_team1' => 4,
            'wins_team2' => 1,
        ]);
        // Finals — triggers league_history sync
        PlayoffSeries::query()->create([
            'playoff_id' => $playoff->id,
            'season_id' => $s2->id,
            'conference_id' => null,
            'round' => 'finals',
            'team1_id' => $t1->id,
            'team2_id' => $t3->id,
            'winner_team_id' => $t1->id,
            'wins_team1' => 4,
            'wins_team2' => 3,
        ]);

        LeagueHistory::query()
            ->where('season_id', $s2->id)
            ->where('type', 'league_champion')
            ->update(['finals_mvp_id' => $players[0]->id]);

        // Also seed older season history manually
        LeagueHistory::query()->create([
            'season_id' => $s1->id,
            'conference_id' => $east->id,
            'champion_team_id' => $t2->id,
            'type' => 'conference_champion',
        ]);
        LeagueHistory::query()->create([
            'season_id' => $s1->id,
            'conference_id' => $west->id,
            'champion_team_id' => $t4->id,
            'type' => 'conference_champion',
        ]);
        LeagueHistory::query()->create([
            'season_id' => $s1->id,
            'conference_id' => null,
            'champion_team_id' => $t2->id,
            'finals_mvp_id' => $players[4]->id,
            'type' => 'league_champion',
        ]);

        Award::query()->create([
            'season_id' => $s2->id,
            'name' => 'Most Valuable Player',
            'player_id' => $players[0]->id,
            'team_id' => $t1->id,
        ]);
        Award::query()->create([
            'season_id' => $s2->id,
            'name' => 'Defensive Player of the Year',
            'player_id' => $players[9]->id,
            'team_id' => $t1->id,
        ]);
        Award::query()->create([
            'season_id' => $s2->id,
            'name' => 'Coach of the Year',
            'coach_id' => $coaches[0]->id,
            'team_id' => $t1->id,
        ]);
        Award::query()->create([
            'season_id' => $s3->id,
            'name' => 'Rookie of the Year',
            'player_id' => $players[8]->id,
            'team_id' => $t4->id,
        ]);

        $draft = Draft::query()->create([
            'year' => '2025',
            'season_id' => $s2->id,
            'name' => '2025 PBA Draft',
        ]);
        DraftPick::query()->create([
            'draft_id' => $draft->id,
            'year' => '2025',
            'round' => 1,
            'pick_number' => 1,
            'overall_pick' => 1,
            'team_id' => $t4->id,
            'original_team_id' => $t4->id,
            'player_id' => $players[8]->id,
        ]);
        DraftPick::query()->create([
            'draft_id' => $draft->id,
            'year' => '2025',
            'round' => 1,
            'pick_number' => 2,
            'overall_pick' => 2,
            'team_id' => $t3->id,
            'original_team_id' => $t3->id,
            'player_id' => $players[6]->id,
        ]);
        DraftPick::query()->create([
            'draft_id' => $draft->id,
            'year' => '2025',
            'round' => 2,
            'pick_number' => 1,
            'overall_pick' => 5,
            'team_id' => $t2->id,
            'original_team_id' => $t2->id,
            'player_id' => $fa2->id,
        ]);

        // Future owned picks for trades
        foreach ([$t1, $t2, $t3, $t4] as $idx => $team) {
            DraftPick::query()->create([
                'draft_id' => null,
                'year' => '2027',
                'round' => 1,
                'pick_number' => $idx + 1,
                'team_id' => $team->id,
                'original_team_id' => $team->id,
                'is_future' => true,
                'notes' => 'Own 1st round',
            ]);
            DraftPick::query()->create([
                'draft_id' => null,
                'year' => '2028',
                'round' => 2,
                'pick_number' => $idx + 1,
                'team_id' => $team->id,
                'original_team_id' => $team->id,
                'is_future' => true,
                'notes' => 'Own 2nd round',
            ]);
        }
    }

    private function seedRatings(int $playerId, int $seasonId, int $overall): void
    {
        $base = max(50, $overall - 8);
        PlayerRating::query()->create([
            'player_id' => $playerId,
            'season_id' => $seasonId,
            'overall' => $overall,
            'inside' => $base + 5,
            'mid_range' => $base + 3,
            'three_point' => $base,
            'free_throw' => $base + 6,
            'dunk' => $base + 2,
            'layup' => $base + 7,
            'passing' => $base + 4,
            'ball_handle' => $base + 3,
            'speed' => $base + 5,
            'acceleration' => $base + 4,
            'strength' => $base + 2,
            'vertical' => $base + 1,
            'stamina' => $base + 6,
            'defense' => $base + 3,
            'steal' => $base,
            'block' => $base - 2,
            'rebound' => $base + 2,
            'iq' => $base + 5,
        ]);
    }
}
