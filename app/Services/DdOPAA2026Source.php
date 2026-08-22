<?php

namespace App\Services;

use RuntimeException;

/** Reads the reviewed SQL as data only; it never executes source SQL. */
class DdOPAA2026Source
{
    private string $sql;

    public function __construct(?string $path = null)
    {
        $path ??= base_path('docs/final/pmms_provincial_meet_2026_migration.sql');
        $contents = is_file($path) ? file_get_contents($path) : false;
        if ($contents === false) {
            throw new RuntimeException("DdOPAA source fixture not found: {$path}");
        }
        $this->sql = $contents;
    }

    /** @return list<array{key:string,name:string,flags:list<string>}> */
    public function people(): array
    {
        preg_match_all("/INSERT INTO pmms_people .*?VALUES \\('((?:''|[^'])*)','((?:''|[^'])*)','((?:''|[^'])*)'\\)/s", $this->sql, $matches, PREG_SET_ORDER);

        return array_map(fn (array $m) => ['key' => $this->value($m[1]), 'name' => $this->value($m[2]), 'flags' => explode(',', $this->value($m[3]))], $matches);
    }

    /** @return list<array{code:string,name:string}> */
    public function municipalities(): array
    {
        preg_match_all("/INSERT INTO pmms_municipalities .*?VALUES \\('([^']*)', '((?:''|[^'])*)'\\)/s", $this->sql, $matches, PREG_SET_ORDER);

        return array_map(fn (array $m) => ['code' => $m[1], 'name' => $this->value($m[2])], $matches);
    }

    /** @return list<array{municipality:string,code:string,name:string}> */
    public function schoolDistricts(): array
    {
        preg_match_all("/INSERT INTO pmms_school_districts .*?SELECT m.id,'([^']*)','((?:''|[^'])*)'.*?m.code='([^']+)'/s", $this->sql, $matches, PREG_SET_ORDER);

        return array_map(fn (array $m) => ['municipality' => $m[3], 'code' => $m[1], 'name' => $this->value($m[2])], $matches);
    }

    /** @return list<array{code:string,name:string,classification:string,order:int}> */
    public function sports(): array
    {
        preg_match_all("/INSERT INTO pmms_sports .*?VALUES \\('([^']*)','((?:''|[^'])*)','([^']*)','(?:''|[^']*)','[^']*',(\\d+)\\)/s", $this->sql, $matches, PREG_SET_ORDER);

        return array_map(fn (array $m) => ['code' => $m[1], 'name' => $this->value($m[2]), 'classification' => $m[3], 'order' => (int) $m[4]], $matches);
    }

    /** @return list<array{code:string,name:string,description:string,order:int}> */
    public function twgUnits(): array
    {
        preg_match_all("/INSERT INTO pmms_twg_units .*?SELECT m.id,'([^']*)','((?:''|[^'])*)','((?:''|[^'])*)',(\\d+)/s", $this->sql, $matches, PREG_SET_ORDER);

        return array_map(fn (array $m) => ['code' => $m[1], 'name' => $this->value($m[2]), 'description' => $this->value($m[3]), 'order' => (int) $m[4]], $matches);
    }

    /** @return list<array{unit:string,person:string,title:string,sequence:int|null}> */
    public function twgMemberships(): array
    {
        $rows = [];
        foreach ($this->statementsContaining('INSERT IGNORE INTO pmms_twg_memberships') as $sql) {
            if (preg_match("/SELECT tu.id,p.id,'((?:''|[^'])*)',(NULL|\\d+).*?person_key='([^']+)'.*?tu.code='([^']+)'/s", $sql, $m)) {
                $rows[] = ['unit' => $m[4], 'person' => $m[3], 'title' => $this->value($m[1]), 'sequence' => $m[2] === 'NULL' ? null : (int) $m[2]];
            }
        }

        return $rows;
    }

    /** @return list<array{municipality:string,school_district:string,person:string}> */
    public function dscAssignments(): array
    {
        $rows = [];
        foreach ($this->statementsContaining('INSERT IGNORE INTO pmms_dsc_assignments') as $sql) {
            if (preg_match("/mu.code='([^']+)'.*?sd.code='([^']+)'.*?person_key='([^']+)'/s", $sql, $m)) {
                $rows[] = ['municipality' => $m[1], 'school_district' => $m[2], 'person' => $m[3]];
            }
        }

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    public function sportPersonnelAssignments(): array
    {
        $rows = [];
        foreach ($this->statementsContaining('INSERT IGNORE INTO pmms_sport_personnel_assignments') as $sql) {
            if (! preg_match("/SELECT ms.id,p.id,(NULL|\\(SELECT id FROM pmms_municipalities WHERE code='([^']+)' LIMIT 1\\)),(NULL|\\(SELECT id FROM pmms_school_districts WHERE code='([^']+)' LIMIT 1\\)),'([^']*)','((?:''|[^'])*)',(NULL|'(?:''|[^'])*'),(\\d+),(NULL|'(?:''|[^'])*'),1.*?s.code='([^']+)'.*?person_key='([^']+)'/s", $sql, $m)) {
                continue;
            }
            $rows[] = [
                'municipality' => $m[2] ?: null,
                'school_district' => $m[4] ?: null,
                'role_code' => $m[5],
                'role_label' => $this->value($m[6]),
                'scope' => $this->nullableValue($m[7]),
                'sequence' => (int) $m[8],
                'district_text' => $this->nullableValue($m[9]),
                'sport' => $m[10],
                'person' => $m[11],
            ];
        }

        return $rows;
    }

    /** @return list<array{person:string,username:string,target_role:string,reason:string}> */
    public function accountProvisions(): array
    {
        $rows = [];
        foreach ($this->statementsContaining('INSERT INTO pmms_user_provisioning') as $sql) {
            if (preg_match("/SELECT p\.id,'([^']*)','([^']*)','([^']*)'.*?person_key='([^']+)'/s", $sql, $match)) {
                $rows[] = [
                    'person' => $match[4],
                    'username' => $match[1],
                    'target_role' => $match[2],
                    'reason' => $match[3],
                ];
            }
        }

        return $rows;
    }

    /** @return list<string> */
    private function statementsContaining(string $needle): array
    {
        return array_values(array_filter(explode(';', $this->sql), fn (string $statement) => str_contains($statement, $needle)));
    }

    private function value(string $value): string
    {
        return str_replace("''", "'", trim($value, "'"));
    }

    private function nullableValue(string $value): ?string
    {
        return $value === 'NULL' ? null : $this->value($value);
    }
}
