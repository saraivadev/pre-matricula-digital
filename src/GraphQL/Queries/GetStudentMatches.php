<?php

namespace iEducar\Packages\PreMatricula\GraphQL\Queries;

use App\Models\LegacyPerson;
use iEducar\Packages\PreMatricula\Models\Grade;
use iEducar\Packages\PreMatricula\Models\Period;
use iEducar\Packages\PreMatricula\Models\PreRegistration;
use iEducar\Packages\PreMatricula\Models\ProcessStage;
use iEducar\Packages\PreMatricula\Models\RegistrationMatch;
use iEducar\Packages\PreMatricula\Models\School;
use iEducar\Packages\PreMatricula\Services\Concerns\LegacyPersonStudentFinder;
use iEducar\Packages\PreMatricula\Support\InitialsFromName;
use Illuminate\Support\Facades\DB;

class GetStudentMatches
{
    use InitialsFromName;

    public function __construct(
        protected LegacyPersonStudentFinder $finder
    ) {}

    public function __invoke($_, array $data): array
    {
        /** @var ProcessStage $stage */
        $stage = ProcessStage::query()->findOrFail($data['stage']);

        if ($stage->process->one_per_year) {
            $match = $this->findPreRegistration($data, $stage->process->schoolYear->year);

            if ($match) {
                return [$match];
            }
        }

        if ($stage->restriction_type === ProcessStage::RESTRICTION_NONE) {
            return [];
        }

        $registrations = [
            ProcessStage::RESTRICTION_REGISTRATION_LAST_YEAR,
            ProcessStage::RESTRICTION_REGISTRATION_CURRENT_YEAR,
            ProcessStage::RESTRICTION_NO_REGISTRATION_CURRENT_YEAR,
            ProcessStage::RESTRICTION_NO_REGISTRATION_PERIOD_CURRENT_YEAR,
        ];

        if (in_array($stage->restriction_type, $registrations)) {
            $year = $stage->restriction_type === 2
                ? $stage->process->schoolYear->year - 1
                : $stage->process->schoolYear->year;

            $data['periods'] = $stage->restriction_type === ProcessStage::RESTRICTION_NO_REGISTRATION_PERIOD_CURRENT_YEAR
                ? $stage->process->periods->pluck('id')->toArray()
                : [];

            return RegistrationMatch::query()
                ->year($year)
                ->match($data)
                ->get()
                ->map(function (RegistrationMatch $match) use ($stage) {
                    $school = School::query()->find($match->school);
                    $grade = Grade::query()->find($match->grade);
                    $period = Period::query()->find($match->period);

                    if (empty($school) || empty($grade)) {
                        return []; // @codeCoverageIgnore
                    }

                    return [
                        'id' => $match->id,
                        'initials' => $this->initials($match->name),
                        'date_of_birth' => $match->date_of_birth,
                        'type' => $stage->restriction_type,
                        'registration' => [
                            'year' => $match->year,
                            'school' => [
                                'id' => $school->id,
                                'name' => $school->name,
                            ],
                            'grade' => [
                                'id' => $grade->id,
                                'name' => $grade->name,
                            ],
                            'next_grades' => $this->nextGrades($stage, $grade->id),
                            'period' => [
                                'id' => $period?->id,
                                'name' => $period?->name,
                            ],
                        ],
                    ];
                })
                ->reject(fn ($item) => empty($item))
                ->toArray();
        }

        if ($stage->restriction_type === ProcessStage::RESTRICTION_NEW_STUDENT) {
            $match = $this->findLegacyPerson($data);

            if ($match) {
                return [$match];
            }
        }

        return [];
    }

    /**
     * Séries seguintes à série atual do(a) aluno(a), conforme a sequência de
     * séries do i-Educar (pmieducar.sequencia_serie), limitadas às séries do
     * processo. Usado na rematrícula para sugerir/restringir a próxima série.
     */
    private function nextGrades(ProcessStage $stage, int $gradeId): array
    {
        return DB::table('pmieducar.sequencia_serie as ss')
            ->join('process_grade as pg', 'pg.grade_id', '=', 'ss.ref_serie_destino')
            ->join('pmieducar.serie as s', 's.cod_serie', '=', 'ss.ref_serie_destino')
            ->where('ss.ref_serie_origem', $gradeId)
            ->where('ss.ativo', 1)
            ->where('pg.process_id', $stage->process_id)
            ->orderBy('s.nm_serie')
            ->get(['s.cod_serie as id', 's.nm_serie as name'])
            ->map(fn ($g) => ['id' => $g->id, 'name' => $g->name])
            ->toArray();
    }

    private function findLegacyPerson(array $data): array
    {
        /** @var LegacyPerson $person */
        $person = $this->finder->find($data);

        if ($person) {
            return [
                'id' => $person->id,
                'initials' => $this->initials($person->name),
                'date_of_birth' => $person->individual->birthdate,
                'type' => ProcessStage::RESTRICTION_NEW_STUDENT,
            ];
        }

        return [];
    }

    private function findPreRegistration(array $data, int $year): array
    {
        $preregistration = PreRegistration::query()
            ->with('student')
            ->with('process')
            ->match($data)
            ->whereHas('process.schoolYear', fn ($query) => $query->where('year', $year))
            ->whereNotIn('status', [
                PreRegistration::STATUS_ACCEPTED,
                PreRegistration::STATUS_REJECTED,
            ])
            ->first();

        if (empty($preregistration)) {
            return [];
        }

        return [
            'id' => $preregistration->student->id,
            'initials' => $this->initials($preregistration->student->name),
            'date_of_birth' => $preregistration->student->date_of_birth,
            'type' => ProcessStage::RESTRICTION_PRE_REGISTRATION,
        ];
    }
}
