<?php

namespace App\Http\Controllers;

use App\Enums\DelegationStatus;
use App\Enums\EligibilityStatus;
use App\Models\Accreditation;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\Personnel;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AccreditationController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Per-delegation accreditation view: who is accredited, and who is
     * eligible but not yet accredited. Restricted like roster data.
     */
    public function index(Delegation $delegation): Response
    {
        Gate::authorize('viewRoster', $delegation);

        $delegation->load([
            'school:id,name',
            'meet:id,name,school_year',
            'athletes' => fn ($query) => $query
                ->with(['eligibilityReview:id,athlete_id,status', 'accreditation'])
                ->orderBy('last_name')->orderBy('first_name'),
            'personnel' => fn ($query) => $query
                ->with('accreditation')
                ->orderBy('last_name')->orderBy('first_name'),
        ]);

        $approved = $delegation->status === DelegationStatus::Approved;

        return Inertia::render('accreditation/index', [
            'delegation' => [
                'id' => $delegation->id,
                'school' => $delegation->school->name,
                'meet' => $delegation->meet->name,
                'school_year' => $delegation->meet->school_year,
                'status_label' => $delegation->status->label(),
                'approved' => $approved,
            ],
            'athletes' => $delegation->athletes->map(fn (Athlete $athlete): array => [
                'id' => $athlete->id,
                'name' => "{$athlete->last_name}, {$athlete->first_name}",
                'grade_level' => $athlete->grade_level,
                'division_label' => $athlete->ageDivision()->label(),
                'eligibility_approved' => $athlete->eligibilityReview?->status === EligibilityStatus::Approved,
                'accreditation' => $athlete->accreditation === null ? null : [
                    'id' => $athlete->accreditation->id,
                    'number' => $athlete->accreditation->number,
                ],
                'can_accredit' => $approved
                    && $athlete->accreditation === null
                    && $athlete->eligibilityReview?->status === EligibilityStatus::Approved,
            ])->values()->all(),
            'personnel' => $delegation->personnel->map(fn (Personnel $person): array => [
                'id' => $person->id,
                'name' => "{$person->last_name}, {$person->first_name}",
                'role_label' => $person->role->label(),
                'accreditation' => $person->accreditation === null ? null : [
                    'id' => $person->accreditation->id,
                    'number' => $person->accreditation->number,
                ],
                'can_accredit' => $approved && $person->accreditation === null,
            ])->values()->all(),
            'accreditedCount' => $delegation->athletes->whereNotNull('accreditation')->count()
                + $delegation->personnel->whereNotNull('accreditation')->count(),
            'canManage' => Gate::allows('manage-meet-data'),
        ]);
    }

    /**
     * Accredit one athlete or personnel member, enforcing the gate:
     * approved delegation, and for athletes an approved eligibility review.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'athlete_id' => [
                'nullable', 'integer', Rule::exists('athletes', 'id'),
                'required_without:personnel_id', 'prohibits:personnel_id',
            ],
            'personnel_id' => ['nullable', 'integer', Rule::exists('personnel', 'id')],
        ]);

        $athleteId = $request->integer('athlete_id');

        if ($athleteId > 0) {
            $athlete = Athlete::query()
                ->with(['delegation', 'eligibilityReview'])
                ->findOrFail($athleteId);

            $this->assertDelegationApproved($athlete->delegation, 'athlete_id');

            if ($athlete->eligibilityReview?->status !== EligibilityStatus::Approved) {
                throw ValidationException::withMessages([
                    'athlete_id' => __('Athletes need an approved eligibility review before accreditation.'),
                ]);
            }

            if ($athlete->accreditation()->exists()) {
                throw ValidationException::withMessages([
                    'athlete_id' => __('This athlete is already accredited.'),
                ]);
            }

            $accreditation = new Accreditation([
                'delegation_id' => $athlete->delegation_id,
                'athlete_id' => $athlete->id,
                'accredited_at' => now(),
            ]);
        } else {
            $person = Personnel::query()
                ->with('delegation')
                ->findOrFail($request->integer('personnel_id'));

            $this->assertDelegationApproved($person->delegation, 'personnel_id');

            if ($person->accreditation()->exists()) {
                throw ValidationException::withMessages([
                    'personnel_id' => __('This personnel member is already accredited.'),
                ]);
            }

            $accreditation = new Accreditation([
                'delegation_id' => $person->delegation_id,
                'personnel_id' => $person->id,
                'accredited_at' => now(),
            ]);
        }

        /** @var User $user */
        $user = $request->user();

        $accreditation->accredited_by = $user->id;
        $accreditation->save();
        $accreditation->assignNumber();

        $this->audit->record('accreditation.granted', $accreditation, $this->context($accreditation));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Accreditation granted.')]);

        return back();
    }

    /**
     * Revoke an accreditation (manager decision; the card number is retired).
     */
    public function destroy(Accreditation $accreditation): RedirectResponse
    {
        $context = $this->context($accreditation);

        $accreditation->delete();

        $this->audit->record('accreditation.revoked', $accreditation, $context);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Accreditation revoked.')]);

        return back();
    }

    /**
     * One printable ID card — a sensitive view, audited.
     */
    public function card(Accreditation $accreditation): Response
    {
        Gate::authorize('viewRoster', $accreditation->delegation);

        $this->audit->record('accreditation.card_viewed', $accreditation, $this->context($accreditation));

        $accreditation->load(['delegation.school:id,name', 'delegation.meet:id,name,school_year']);

        return Inertia::render('accreditation/cards', [
            'delegation' => [
                'id' => $accreditation->delegation->id,
                'school' => $accreditation->delegation->school->name,
                'meet' => $accreditation->delegation->meet->name,
                'school_year' => $accreditation->delegation->meet->school_year,
            ],
            'cards' => [$this->cardData($accreditation)],
            'generatedAt' => now()->toDayDateTimeString(),
        ]);
    }

    /**
     * All ID cards of one delegation for batch printing — audited.
     */
    public function cards(Delegation $delegation): Response
    {
        Gate::authorize('viewRoster', $delegation);

        $delegation->load(['school:id,name', 'meet:id,name,school_year']);

        $accreditations = Accreditation::query()
            ->where('delegation_id', $delegation->id)
            ->with(['athlete', 'personnel'])
            ->get()
            ->sortBy(fn (Accreditation $accreditation): string => $accreditation->subjectName())
            ->values();

        $this->audit->record('accreditation.cards_viewed', $delegation, [
            'school' => $delegation->school->name,
            'meet' => $delegation->meet->name,
            'cards' => $accreditations->count(),
        ]);

        return Inertia::render('accreditation/cards', [
            'delegation' => [
                'id' => $delegation->id,
                'school' => $delegation->school->name,
                'meet' => $delegation->meet->name,
                'school_year' => $delegation->meet->school_year,
            ],
            'cards' => $accreditations
                ->map(fn (Accreditation $accreditation): array => $this->cardData($accreditation))
                ->all(),
            'generatedAt' => now()->toDayDateTimeString(),
        ]);
    }

    private function assertDelegationApproved(Delegation $delegation, string $field): void
    {
        if ($delegation->status !== DelegationStatus::Approved) {
            throw ValidationException::withMessages([
                $field => __('Only members of an approved delegation can be accredited.'),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function cardData(Accreditation $accreditation): array
    {
        $accreditation->loadMissing(['athlete', 'personnel', 'delegation.school:id,name']);

        $athlete = $accreditation->athlete;
        $person = $accreditation->personnel;

        return [
            'id' => $accreditation->id,
            'number' => $accreditation->number,
            'name' => $accreditation->subjectName(),
            'type_label' => $athlete !== null ? 'Athlete' : ($person?->role->label() ?? ''),
            'detail' => $athlete !== null
                ? "Grade {$athlete->grade_level} — {$athlete->ageDivision()->label()}"
                : null,
            'school' => $accreditation->delegation->school->name,
            'photo_url' => match (true) {
                $athlete !== null && $athlete->photo_upload_id !== null => route('athletes.photo', $athlete),
                $person !== null && $person->photo_upload_id !== null => route('personnel.photo', $person),
                default => null,
            },
            'accredited_on' => $accreditation->accredited_at->format('M j, Y'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function context(Accreditation $accreditation): array
    {
        $accreditation->loadMissing(['athlete', 'personnel', 'delegation.school:id,name', 'delegation.meet:id,name']);

        return [
            'person' => $accreditation->subjectName(),
            'type' => $accreditation->athlete_id !== null ? 'athlete' : 'personnel',
            'school' => $accreditation->delegation->school->name,
            'meet' => $accreditation->delegation->meet->name,
            'number' => $accreditation->number,
        ];
    }
}
