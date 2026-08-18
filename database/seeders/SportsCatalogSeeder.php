<?php

namespace Database\Seeders;

use App\Enums\AgeDivision;
use App\Enums\GenderCategory;
use App\Models\Event;
use App\Models\Sport;
use Illuminate\Database\Seeder;

class SportsCatalogSeeder extends Seeder
{
    /**
     * Seed the common provincial-meet sports catalog.
     *
     * Genuine reference configuration (not sample data): the sports usually
     * played at a DepEd provincial meet, plus standard athletics track
     * events as a starting event set. Idempotent — safe to re-run; the SDO
     * adjusts the catalog through the UI afterwards.
     */
    public function run(): void
    {
        // Backwards-compatible entry point retained for existing showcase
        // seeders. The canonical catalog is now split by responsibility.
        $this->call(SportEventsSeeder::class);

        foreach ($this->sports() as $name => $descriptions) {
            $canonicalName = match ($name) {
                'Billiard' => 'Billiards',
                'Paragames - Boccee' => 'Bocce',
                'Paragames - Goal Ball' => 'Goalball',
                'Paragames - Athletics' => 'Para Athletics',
                'Paragames - Swimming' => 'Para Swimming',
                default => $name,
            };
            $sport = Sport::query()->where('name', $canonicalName)->first();

            if ($sport === null) {
                continue;
            }

            $generic = $sport->name.' competition configured for the DdOPAA provincial sports program.';
            $sport->forceFill([
                'short_description' => blank($sport->short_description) || $sport->short_description === $generic
                    ? $descriptions['short_description']
                    : $sport->short_description,
                'description' => blank($sport->description)
                    ? $descriptions['description']
                    : $sport->description,
            ])->save();
        }

        // Backfill descriptions for rows that already existed before this
        // WP added the columns (this dev database's 28 sports were seeded
        // long before `short_description`/`description` existed) — never
        // overwrites a non-null value, so an SDO's own edit via the admin
        // UI (once that form supports these fields) is never clobbered by
        // re-running this seeder.
        Sport::query()
            ->whereNull('short_description')
            ->get()
            ->each(function (Sport $sport): void {
                $descriptions = $this->sports()[$sport->name] ?? null;

                if ($descriptions !== null) {
                    $sport->forceFill($descriptions)->save();
                }
            });

        return;

        $athletics = Sport::query()->where('name', 'Athletics')->firstOrFail();

        $trackEvents = [
            ['100 Meter Dash', false, 2],
            ['200 Meter Dash', false, 2],
            ['400 Meter Dash', false, 2],
            ['4x100 Meter Relay', true, 1],
        ];

        foreach ($trackEvents as [$name, $team, $maxEntries]) {
            foreach (GenderCategory::cases() as $gender) {
                if ($gender === GenderCategory::Mixed) {
                    continue;
                }

                foreach (AgeDivision::cases() as $division) {
                    Event::query()->firstOrCreate(
                        [
                            'sport_id' => $athletics->id,
                            'name' => $name,
                            'gender' => $gender,
                            'age_division' => $division,
                        ],
                        [
                            'is_team_event' => $team,
                            'max_entries_per_delegation' => $maxEntries,
                        ],
                    );
                }
            }
        }
    }

    /**
     * The full sports catalog, each with a real, locally-written short
     * card blurb (~20-40 words) and full mini-portal description
     * (~100-180 words) — general, accurate statements about the sport and
     * how it fits into a provincial meet, deliberately not asserting any
     * specific rule detail (scoring systems, round counts, weight
     * classes, etc.) this app has no configured authority over.
     *
     * @return array<string, array{short_description: string, description: string}>
     */
    private function sports(): array
    {
        return [
            'Athletics' => [
                'short_description' => 'Track and field events testing speed, strength, and endurance across sprints, relays, and field disciplines.',
                'description' => "Athletics is the founding sport of the provincial meet, contested through individual and relay track events for Elementary and Secondary Boys and Girls. Delegations field their fastest and strongest athletes across sprints and relays, with results decided by time or distance rather than head-to-head matchups.\n\nEach event runs as its own heat and final, managed by the meet's assigned Tournament Management team and Technical Officials for timing, lane judging, and results verification. Because Athletics has no live-scoring concept the way ball sports do, results are confirmed and published once a Technical Official validates the official time or distance.\n\nAthletics traditionally anchors the overall medal tally given its large number of individual events across age and gender categories, making it one of the most closely watched sports of the meet.",
            ],
            'Archery' => [
                'short_description' => 'A precision target sport testing accuracy, focus, and consistency at fixed distances.',
                'description' => "Archery is a precision individual sport contested at the provincial meet across Elementary and Secondary categories. Athletes shoot a series of ends at a standard target, with placements decided by accumulated score rather than direct elimination in the early rounds.\n\nCompetitions are conducted at the meet's designated range under the supervision of assigned Technical Officials, who verify scoring and enforce safety protocols throughout. Because Archery depends heavily on calm conditions and range availability, its schedule is coordinated closely with the venue team.\n\nWhile a smaller-field sport compared to team ball games, Archery rewards discipline and mental composure, and its medalists are recognized alongside every other sport in the meet's official tally.",
            ],
            'Arnis' => [
                'short_description' => 'The Philippines\' national martial art, contested in stick-fighting bouts judged on skill and technique.',
                'description' => "Arnis, the Philippines' officially recognized national martial art and sport, is contested at the provincial meet through judged bouts between representatives of competing delegations. Athletes compete in weight- or category-based divisions for Elementary and Secondary Boys and Girls.\n\nBouts are conducted in rounds under the same red-corner/blue-corner format familiar from other combat sports, with panel judges scoring technique, control, and effective execution. A Tournament Management team and assigned Technical Officials oversee bout scheduling, judging, and results verification at the venue.\n\nAs a proudly Filipino sport with deep cultural roots, Arnis carries particular significance at DepEd-organized meets, and its inclusion highlights heritage alongside athletic competition.",
            ],
            'Badminton' => [
                'short_description' => 'Fast racket sport played to game points across singles and doubles categories.',
                'description' => "Badminton is a racket sport contested at the provincial meet in singles and doubles categories across Elementary and Secondary Boys and Girls divisions. Matches are played to a set number of games, with each game decided by points scored through rallies.\n\nCompetition follows the meet's configured tournament format toward the championship matches, managed by assigned Tournament Managers and Technical Officials who officiate serves, line calls, and scoring. Live scores may be available when venue connectivity permits; otherwise, results are confirmed and encoded once each match concludes.\n\nBadminton's quick pace and frequent shuttlecock exchanges make it a popular spectator sport throughout the meet, with strong turnout expected at every scheduled session.",
            ],
            'Baseball' => [
                'short_description' => 'A classic bat-and-ball team sport played over innings, testing hitting, fielding, and pitching.',
                'description' => "Baseball is a team sport contested at the provincial meet primarily in the Secondary Boys division, played between two municipal delegations over a scheduled number of innings. Teams alternate at bat and in the field, with runs scored by advancing base runners around the diamond.\n\nGames are conducted according to the official meet schedule and are managed by assigned Tournament Managers, Technical Officials, and Tournament Secretaries, who oversee umpiring, scorekeeping, and results confirmation. Live scores may be available when venue connectivity permits; otherwise, official results are encoded and confirmed after the match.\n\nAs one of the meet's traditional team sports, Baseball draws consistent delegation support and contributes meaningfully to the overall team-sport medal count.",
            ],
            'Basketball' => [
                'short_description' => 'Fast-paced team competition featuring Elementary and Secondary Boys and Girls divisions in the DdOPAA Meet.',
                'description' => "Basketball is one of the major team sports contested during the DdOPAA Provincial Meet. Municipal delegations compete through approved Elementary and Secondary categories for Boys and Girls. Games are conducted according to the official meet schedule and are managed by assigned Tournament Managers, Technical Officials, Tournament ICT personnel, and Tournament Secretaries.\n\nThe competition progresses through the configured tournament format until the championship matches. Live scores may be available when venue connectivity permits. Otherwise, official results are encoded and confirmed after the match.\n\nAs one of the most widely followed sports at any DepEd meet, Basketball typically draws large crowds and frequent live-scoring coverage, and its games are often featured prominently across the public portal's Live Now section.",
            ],
            'Basketball 3x3' => [
                'short_description' => 'A compact, fast-paced form of basketball played by three athletes per side on a half court.',
                'description' => "Basketball 3x3 is a fast-paced team sport contested on a half court by three active players from each delegation. Its compact format keeps play moving through quick possessions, frequent transitions, and continuous action around a single basket.\n\nGames follow the official DdOPAA Meet schedule and are handled by the assigned Tournament Managers, Technical Officials, Tournament ICT personnel, and Tournament Secretaries. They oversee officiating, scoring, match records, and confirmation of the official result. Live scores may be shown when venue connectivity permits; otherwise, results are encoded after each game.\n\nAlthough related to traditional Basketball, 3x3 is maintained as a separate meet sport with its own assignments, schedule, results, and medal outcome.",
            ],
            'Billiard' => [
                'short_description' => 'A cue sport contested rack by rack, rewarding precision, positioning, and composure.',
                'description' => "Billiard is an individual cue sport contested at the provincial meet across Secondary Boys and Girls categories. Matches are played as a race to a set number of racks, with the first athlete to reach the target declared the winner of that match.\n\nCompetitions take place at the meet's designated billiard venue under the supervision of Technical Officials who confirm rack outcomes and maintain match records. Given the sport's need for a controlled, quiet playing environment, its schedule is coordinated closely with venue availability.\n\nBilliard rewards patience and precision over raw athleticism, offering a different pace of competition within the broader meet program while still counting fully toward each delegation's overall standing.",
            ],
            'Paragames - Boccee' => [
                'short_description' => 'A precision ball sport where teams score points each end by landing closest to the target ball.',
                'description' => "Bocce is a precision ball sport contested at the provincial meet in team format across Elementary and Secondary categories. Competitors take turns rolling balls toward a smaller target ball, scoring points each end based on proximity, until a delegation reaches the target score.\n\nMatches are conducted at the meet's designated court under the oversight of assigned Technical Officials, who confirm end results and running scores. As with other precision sports, Bocce rewards accuracy and tactical shot selection over speed.\n\nBocce's inclusion broadens the range of competition formats available at the meet, giving delegations another avenue to contribute to their overall medal count outside the more physically intensive team sports.",
            ],
            'Boxing' => [
                'short_description' => 'Judged combat bouts between red and blue corners, contested over a set number of rounds.',
                'description' => "Boxing is a combat sport contested at the provincial meet through judged bouts between athletes representing competing delegations, organized by weight class across Secondary Boys and Girls divisions. Each bout is fought over a set number of rounds, with a panel of judges scoring effective blows, technique, and ring control.\n\nBouts are managed by assigned Tournament Managers and Technical Officials who oversee round timing, scoring, and safety throughout. Live round-by-round scores may be available when venue connectivity permits, giving spectators a real-time view of each judge's running tally.\n\nAs one of the meet's most closely officiated sports, Boxing's results are always confirmed by the assigned judging panel before being recorded as official.",
            ],
            'Chess' => [
                'short_description' => 'A strategic board game contested over individual matches, testing planning and composure.',
                'description' => "Chess is an individual strategy sport contested at the provincial meet across Elementary and Secondary Boys and Girls divisions. Matches are played under standard tournament time controls, with games decided by checkmate, resignation, or the clock, and results feeding into a round-based standing.\n\nCompetition is organized according to the meet's configured tournament format, overseen by assigned Tournament Managers and Technical Officials who enforce time controls and confirm results after each round. As a quiet, concentration-heavy sport, Chess is typically held in a dedicated venue separate from the meet's louder team sports.\n\nChess consistently draws a dedicated following at DepEd meets, valued for the mental discipline it demonstrates alongside the meet's more physically active sports.",
            ],
            'Dancesports' => [
                'short_description' => 'A judged performance sport combining athleticism and artistry across choreographed dance categories.',
                'description' => "Dancesports is a judged performance sport contested at the provincial meet across Elementary and Secondary categories. Competing pairs or teams perform choreographed routines within their category, evaluated by a panel of judges on technique, timing, and presentation.\n\nCompetitions are held at the meet's designated venue and are overseen by assigned Tournament Managers and Technical Officials responsible for scheduling heats, coordinating the judging panel, and confirming results. As a scored rather than head-to-head sport, placements are determined once every competing pair or team has performed.\n\nDancesports adds a distinctly artistic dimension to the meet's program, showcasing discipline and performance skill alongside the more conventional athletic and team sports.",
            ],
            'Football' => [
                'short_description' => 'Full-field team competition played over two halves, featuring goals scored by both delegations.',
                'description' => "Football is a team sport contested at the provincial meet across Secondary Boys and Girls divisions, played between two municipal delegations over two halves on a full-sized field. Teams compete to score more goals than their opponent within the match time.\n\nMatches follow the official meet schedule and are managed by assigned Tournament Managers, Technical Officials, and Tournament Secretaries who oversee refereeing, timing, and results confirmation. Live scores, including yellow and red card tallies, may be available when venue connectivity permits; otherwise, results are confirmed and encoded after the match.\n\nAs a full-field outdoor sport, Football matches are often among the longer scheduled sessions at the meet and draw significant delegation and spectator turnout.",
            ],
            'Futsal' => [
                'short_description' => 'A fast indoor variant of football played on a smaller court over shorter halves.',
                'description' => "Futsal is a team sport contested at the provincial meet across Elementary and Secondary Boys and Girls divisions, played on a smaller indoor or hard-court surface over two shorter halves than outdoor Football. The compact playing area rewards quick passing and close ball control.\n\nMatches follow the official meet schedule and are managed by assigned Tournament Managers and Technical Officials who oversee refereeing, timing, and results confirmation, including yellow and red card tallies where applicable. Live scores may be available when venue connectivity permits; otherwise, results are confirmed and encoded after the match.\n\nFutsal's smaller format allows more matches to be scheduled within a shorter window, making it a fast-paced, frequently featured sport across the meet's daily schedule.",
            ],
            'Paragames - Goal Ball' => [
                'short_description' => 'A Paralympic team sport for visually impaired athletes, played by ear using a bell-fitted ball.',
                'description' => "Goal Ball is a team sport for visually impaired athletes, contested at the provincial meet across its own categories. Teams attempt to roll a ball fitted with bells past their opponents into a goal, relying entirely on hearing and orientation within a marked, tactile-lined court.\n\nMatches are played over two halves under strict silence requirements from spectators, allowing athletes to track the ball by sound alone. Assigned Tournament Managers and Technical Officials oversee timing, goal confirmation, and penalty-throw calls throughout each match.\n\nGoal Ball's inclusion reflects the meet's commitment to inclusive competition, giving visually impaired athletes a genuine, fully officiated sport of their own within the overall program.",
            ],
            'Gymnastics' => [
                'short_description' => 'A judged sport combining strength, flexibility, and precision across individual apparatus and floor routines.',
                'description' => "Gymnastics is a judged individual sport contested at the provincial meet across Elementary and Secondary Boys and Girls divisions. Athletes perform routines on designated apparatus and floor exercises, evaluated by a panel of judges on execution, difficulty, and composition.\n\nCompetitions are held at the meet's designated venue and are overseen by assigned Tournament Managers and Technical Officials responsible for apparatus setup, judging coordination, and results confirmation. As with other judged sports, placements are finalized once every competing athlete has completed their routines.\n\nGymnastics showcases a distinct blend of strength, flexibility, and artistry, and its athletes are recognized among the meet's most technically demanding performers.",
            ],
            'Pencak Silat' => [
                'short_description' => 'A Southeast Asian martial art contested in judged sparring and artistic performance categories.',
                'description' => "Pencak Silat is a martial art and combat sport contested at the provincial meet, typically through judged sparring bouts (Tanding) organized by category across Secondary Boys and Girls divisions. Athletes compete under a red-corner/blue-corner format familiar from other combat sports, with rounds scored by a judging panel.\n\nBouts are managed by assigned Tournament Managers and Technical Officials who oversee round timing, scoring, and safety throughout the competition. Live round-by-round scores may be available when venue connectivity permits.\n\nAs a widely practiced Southeast Asian martial art, Pencak Silat's presence at the meet reflects the sport's growing recognition within Philippine school-level competition alongside other combat disciplines.",
            ],
            'Swimming' => [
                'short_description' => 'Timed pool races across multiple strokes and distances, decided purely by the clock.',
                'description' => "Swimming is contested at the provincial meet through timed individual and relay races across multiple strokes and distances, for Elementary and Secondary Boys and Girls. As with Athletics, results are decided entirely by time rather than direct elimination, with heats feeding into finals where applicable.\n\nEvents are conducted at the meet's designated pool venue and are overseen by assigned Tournament Managers and Technical Officials responsible for timing, lane judging, and results verification. Because Swimming has no live-scoring concept the way ball sports do, results are confirmed once a Technical Official validates the official time.\n\nSwimming is one of the meet's larger individual-event sports given its many stroke, distance, and category combinations, and consistently contributes a significant share of the overall medal tally.",
            ],
            'Weightlifting' => [
                'short_description' => 'A strength sport testing an athlete\'s best lift across bodyweight-based categories.',
                'description' => "Weightlifting is an individual strength sport contested at the provincial meet across bodyweight-based categories for Secondary Boys and Girls. Athletes attempt a series of lifts, with placement determined by the highest successful weight lifted within their category.\n\nCompetitions are held at the meet's designated venue under the close supervision of assigned Technical Officials, who judge each attempt's validity and record official results. Given the technical and safety demands of the sport, lifts are conducted one athlete at a time under strict officiating.\n\nWeightlifting rewards focused strength training and technique, and its athletes are recognized for some of the most physically demanding individual performances across the entire meet program.",
            ],
            'Sepak Takraw' => [
                'short_description' => 'A rally-based net sport played with a woven ball using feet, knees, chest, and head.',
                'description' => "Sepak Takraw is a rally-based net sport contested at the provincial meet across Secondary Boys and Girls team categories. Players use their feet, knees, chest, and head — never their hands — to send a woven ball over the net, competing to win a set number of sets per match.\n\nMatches follow the meet's configured tournament format and are managed by assigned Tournament Managers and Technical Officials who oversee serving rotation, scoring, and results confirmation. Live scores may be available when venue connectivity permits; otherwise, results are confirmed and encoded after the match.\n\nAs a distinctly Southeast Asian sport requiring exceptional footwork and agility, Sepak Takraw is often one of the more visually striking competitions at the meet.",
            ],
            'Softball' => [
                'short_description' => 'A bat-and-ball team sport played over innings, closely related to Baseball and popular in Girls divisions.',
                'description' => "Softball is a team sport contested at the provincial meet primarily in the Secondary Girls division, played between two municipal delegations over a scheduled number of innings. Teams alternate at bat and in the field, tracking balls, strikes, and outs toward each half-inning's conclusion.\n\nGames are conducted according to the official meet schedule and are managed by assigned Tournament Managers, Technical Officials, and Tournament Secretaries, who oversee umpiring, scorekeeping, and results confirmation. Live scores may be available when venue connectivity permits; otherwise, official results are encoded and confirmed after the match.\n\nSoftball is consistently one of the meet's most closely contested team sports, with strong delegation rivalries carried over from previous years' competitions.",
            ],
            'Taekwondo' => [
                'short_description' => 'A Korean martial art contested in judged sparring bouts across weight divisions.',
                'description' => "Taekwondo is a martial art and combat sport contested at the provincial meet through judged sparring bouts organized by weight division across Elementary and Secondary Boys and Girls. Athletes compete under the familiar red-corner/blue-corner format, with rounds scored by a judging panel based on scoring kicks and strikes.\n\nBouts are managed by assigned Tournament Managers and Technical Officials who oversee round timing, scoring, and safety throughout the competition. Live round-by-round scores may be available when venue connectivity permits, giving spectators a real-time view of each bout's progress.\n\nTaekwondo is one of the meet's most widely contested combat sports, drawing entries across a broad range of weight classes and age divisions.",
            ],
            'Table Tennis' => [
                'short_description' => 'Rapid-fire racket sport contested at close range across singles and doubles categories.',
                'description' => "Table Tennis is a racket sport contested at the provincial meet in singles and doubles categories across Elementary and Secondary Boys and Girls divisions. Matches are played to a set number of games, with each game decided by points scored through fast, close-range rallies.\n\nCompetition follows the meet's configured tournament format toward the championship matches, managed by assigned Tournament Managers and Technical Officials who officiate serves and scoring. Live scores may be available when venue connectivity permits; otherwise, results are confirmed and encoded once each match concludes.\n\nTable Tennis's rapid pace and require for sharp reflexes make it a consistently entertaining sport to follow throughout the meet's schedule.",
            ],
            'Tennis' => [
                'short_description' => 'Classic racket sport played across games, sets, and a full match, in singles or doubles.',
                'description' => "Tennis is a racket sport contested at the provincial meet in singles and doubles categories across Secondary Boys and Girls divisions. Matches progress through games and sets, with the format following standard scoring conventions including deuce and tiebreak situations at the set level.\n\nCompetition follows the meet's configured tournament format toward the championship matches, managed by assigned Tournament Managers and Technical Officials who officiate serves, line calls, and scoring. Live scores may be available when venue connectivity permits; otherwise, results are confirmed and encoded once each match concludes.\n\nTennis demands sustained focus over what can be lengthy matches, and its athletes are recognized for both physical endurance and tactical precision.",
            ],
            'Volleyball' => [
                'short_description' => 'Rally-point team sport played to a best-of-sets format between competing delegations.',
                'description' => "Volleyball is a team sport contested at the provincial meet across Secondary Boys and Girls divisions, played between two municipal delegations in a best-of-sets format. Teams compete for rally points until a set is won, with the match decided once one team wins the required number of sets.\n\nMatches follow the meet's configured tournament format and are managed by assigned Tournament Managers, Technical Officials, and Tournament Secretaries who oversee serving rotation, scoring, and results confirmation. Live scores may be available when venue connectivity permits; otherwise, results are confirmed and encoded after the match.\n\nVolleyball is consistently one of the meet's most attended team sports, known for long, closely fought sets that often run to their final points.",
            ],
            'Wrestling' => [
                'short_description' => 'A grappling combat sport where athletes score named moves across weight-class divisions.',
                'description' => "Wrestling is a grappling combat sport contested at the provincial meet across weight-class divisions for Secondary Boys and Girls. Athletes compete to score points through recognized wrestling moves within a match, which may also end early by fall.\n\nMatches are managed by assigned Tournament Managers and Technical Officials who oversee period timing, scoring, and fall confirmation throughout the competition. Live scores may be available when venue connectivity permits, giving spectators a real-time view of each match's running total.\n\nWrestling rewards a combination of technique, conditioning, and strategy, and its bouts are among the most physically intense combat-sport competitions held at the meet.",
            ],
            'Wushu' => [
                'short_description' => 'A Chinese martial art contested in both judged forms performance and sparring categories.',
                'description' => "Wushu is a martial art and combat sport contested at the provincial meet, typically through judged sparring bouts (Sanda) organized by category across Secondary Boys and Girls divisions. Athletes compete under a red-corner/blue-corner format, with rounds scored by a judging panel.\n\nBouts are managed by assigned Tournament Managers and Technical Officials who oversee round timing, scoring, and safety throughout the competition. Live round-by-round scores may be available when venue connectivity permits.\n\nWushu's presence at the meet reflects the sport's growing following within Philippine school-level competition, offering athletes another combat discipline alongside Taekwondo, Boxing, Arnis, and Pencak Silat.",
            ],
            'Paragames - Athletics' => [
                'short_description' => 'Track and field competition for athletes with disabilities, held under Paragames classification.',
                'description' => "Paragames Athletics brings track and field competition to athletes with disabilities at the provincial meet, contested under its own Paragames classification alongside the regular sports program. Events follow the same general timed-race and field-event format as standard Athletics, adapted to each competing category.\n\nEvents are conducted at the meet's designated venue and are overseen by assigned Tournament Managers and Technical Officials responsible for timing, results verification, and event-specific accommodations. Results are confirmed and published once validated, the same as every other individual-event sport at the meet.\n\nParagames Athletics reflects the meet's commitment to inclusive competition, ensuring athletes with disabilities have a genuine, fully officiated track and field program of their own.",
            ],
            'Paragames - Swimming' => [
                'short_description' => 'Pool competition for athletes with disabilities, held under Paragames classification.',
                'description' => "Paragames Swimming brings pool competition to athletes with disabilities at the provincial meet, contested under its own Paragames classification alongside the regular sports program. Races follow the same general timed-heat format as standard Swimming, adapted to each competing category.\n\nEvents are conducted at the meet's designated pool venue and are overseen by assigned Tournament Managers and Technical Officials responsible for timing, lane judging, and results verification. Results are confirmed once validated, the same as every other individual-event sport at the meet.\n\nParagames Swimming reflects the meet's commitment to inclusive competition, ensuring athletes with disabilities have a genuine, fully officiated pool program of their own.",
            ],
        ];
    }
}
