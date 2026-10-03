<?php

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The dataset the app-home performance budget is measured on (app-home plan, Observable; checks C48):
 * legislatures 57 and 58; in 58, 600 Câmara and 81 Senate memberships, all in exercise, 30 parties,
 * names of 10 to 40 characters; per house 1,500 plenary nominal or secret roll calls over the 48 months
 * of 58, and 500 Câmara plenary symbolic ones. Deterministic: the same rows on every run.
 */
class BudgetSeeder extends Seeder
{
    private const PARTIES = [
        'PT', 'PL', 'UNIÃO', 'PP', 'PSD', 'MDB', 'REPUBLICANOS', 'PDT', 'PSB', 'PSDB', 'PSOL', 'PODE', 'AVANTE', 'PCdoB', 'PV',
        'CIDADANIA', 'NOVO', 'SOLIDARIEDADE', 'PRD', 'REDE', 'AGIR', 'DC', 'MOBILIZA', 'PMB', 'PCO', 'PSTU', 'UP', 'PCB', 'PRTB', 'DEM',
    ];

    private const UFS = [
        'AC', 'AL', 'AM', 'AP', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MG', 'MS', 'MT', 'PA',
        'PB', 'PE', 'PI', 'PR', 'RJ', 'RN', 'RO', 'RR', 'RS', 'SC', 'SE', 'SP', 'TO',
    ];

    private const FIRST = [
        'Ana', 'Bruno', 'Cláudia', 'Daniel', 'Eduarda', 'Fábio', 'Gabriela', 'Heitor', 'Isabela', 'João', 'Karina', 'Luís',
        'Marcela', 'Nelson', 'Otávio', 'Patrícia', 'Raimundo', 'Sílvia', 'Tiago', 'Valéria', 'Wellington', 'Zuleica',
        'Antônio Carlos', 'Maria José', 'Francisco de Assis', 'Luiz Henrique',
    ];

    private const LAST = [
        'Souza', 'Lima', 'Oliveira', 'Pereira', 'Costa', 'Rodrigues', 'Almeida', 'Nascimento', 'Carvalho', 'Gonçalves',
        'Araújo', 'Ribeiro', 'Cavalcanti', 'Albuquerque', 'Magalhães', 'Vasconcelos', 'Figueiredo', 'Bittencourt', 'Brandão',
    ];

    private const GENERATED_AT = '2031-01-31 15:00:00+00';

    public function run(): void
    {
        $now = now();
        DB::table('legislatures')->insert([
            ['number' => 57, 'starts_on' => '2023-02-01', 'ends_on' => '2027-01-31', 'created_at' => $now, 'updated_at' => $now],
            ['number' => 58, 'starts_on' => '2027-02-01', 'ends_on' => '2031-01-31', 'created_at' => $now, 'updated_at' => $now],
        ]);

        foreach (['camara' => [600, 300, 1000], 'senado' => [81, 40, 5000]] as $house => [$current, $previous, $firstId]) {
            $memberships = $this->members($house, $current, $previous, $firstId, $now);
            $rollCalls = $this->rollCalls($house, $now);
            $this->propositions($house, $memberships, $now);
            $senate = $house === 'senado';
            DB::table('contract_imports')->insert([
                'house' => $house, 'schema_version' => 3, 'generated_at' => self::GENERATED_AT, 'meta_sha256' => str_repeat('0', 64),
                'classification_version' => 1,
                'coverage' => json_encode([
                    ['legislature' => 57, 'through' => null, 'rollCalls' => ['nominal' => 0, 'secret' => 0, 'symbolic' => $senate ? null : 0], 'unclassified' => 0, 'members' => $previous],
                    ['legislature' => 58, 'through' => '2031-01-28', 'rollCalls' => ['nominal' => 1350, 'secret' => 150, 'symbolic' => $senate ? null : 500], 'unclassified' => 375, 'members' => $current],
                ]),
                'members_count' => $current, 'mandates_count' => $current + $previous, 'roll_calls_count' => $rollCalls,
                'votes_count' => 0, 'propositions_count' => 200, 'full_texts_count' => 0, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    /** @return list<int> the ids of the memberships of 58 */
    private function members(string $house, int $current, int $previous, int $firstId, mixed $now): array
    {
        $senate = $house === 'senado';
        $ids = [];
        for ($i = 0; $i < $current; $i++) {
            $sourceId = (string) ($firstId + $i);
            $party = self::PARTIES[$i % 30];
            $uf = self::UFS[$i % 27];
            $memberId = DB::table('members')->insertGetId([
                'house' => $house, 'source_id' => $sourceId, 'name' => $this->name($i + ($senate ? 7 : 0)), 'party' => $party, 'uf' => $uf,
                'photo_url' => "https://example.org/{$house}/{$sourceId}.jpg", 'source_url' => "https://example.org/{$house}/{$sourceId}",
                'created_at' => $now, 'updated_at' => $now,
            ]);
            foreach ($i < $previous ? [57, 58] : [58] as $n) {
                $membershipId = DB::table('memberships')->insertGetId([
                    'member_id' => $memberId, 'legislature_number' => $n, 'party' => $party, 'uf' => $uf,
                    ...$this->indicators(), 'symbolic_merit' => $senate ? null : 0,
                    'authored_count' => 0, 'first_signer_count' => 0, 'requirements_count' => 0, 'created_at' => $now, 'updated_at' => $now,
                ]);
                DB::table('exercise_periods')->insert([
                    'membership_id' => $membershipId,
                    'starts_at' => $n === 58 ? '2027-02-01 00:00:00' : '2023-02-01 00:00:00',
                    'ends_at' => $n === 58 ? '2031-01-31 12:00:00' : '2027-02-01 00:00:00',
                    'created_at' => $now, 'updated_at' => $now,
                ]);
                if ($n === 58) {
                    $ids[] = $membershipId;
                }
            }
        }

        return $ids;
    }

    /** A name of 10 to 40 characters; every 97th is padded to exactly 40. */
    private function name(int $i): string
    {
        $name = self::FIRST[$i % count(self::FIRST)].' '.self::LAST[intdiv($i, 3) % count(self::LAST)].' '.self::LAST[($i * 7 + 3) % count(self::LAST)];
        if ($i % 97 === 0) {
            $name = mb_substr($name.' de Albuquerque Vasconcelos Figueiredo', 0, 40);
        }

        return rtrim(mb_substr($name, 0, 40));
    }

    /** @return array<string, int> every indicator 0 of 0: the overview and search read none */
    private function indicators(): array
    {
        $columns = [];
        foreach (['participation', 'government_alignment', 'party_alignment'] as $indicator) {
            foreach (['all', 'merit'] as $basis) {
                $columns["{$indicator}_{$basis}_count"] = 0;
                $columns["{$indicator}_{$basis}_total"] = 0;
            }
        }

        return $columns;
    }

    /** 1,500 plenary nominal or secret roll calls of 58 over its 48 months, and 500 Câmara symbolic; returns how many. */
    private function rollCalls(string $house, mixed $now): int
    {
        $kinds = ['final', 'amendment', 'procedural', 'unclassified'];
        $rows = [];
        $count = $house === 'camara' ? 2000 : 1500;
        for ($i = 0; $i < $count; $i++) {
            $symbolic = $i >= 1500;
            $date = CarbonImmutable::create(2027, 2, 1)->addMonths($i % 48)->addDays(($i * 7) % 27);
            $sourceId = $house === 'camara' ? (2000 + $i).'-'.($symbolic ? 2 : 1) : (string) (10000 + $i);
            $rows[] = [
                'house' => $house, 'source_id' => $sourceId, 'legislature_number' => 58, 'date' => $date->toDateString(), 'organ' => 'PLEN',
                'description' => 'Votação em turno único.', 'proposition_id' => null, 'approved' => [true, false, null][$i % 3],
                'ballot' => $symbolic ? 'symbolic' : ($i % 10 === 9 ? 'secret' : 'nominal'), 'kind' => $kinds[$i % 4], 'kind_rule' => null,
                'tally_yes' => null, 'tally_no' => null, 'tally_others' => null, 'government_orientation' => null,
                'source_url' => "https://example.org/{$house}/votacoes/{$sourceId}", 'created_at' => $now, 'updated_at' => $now,
            ];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('roll_calls')->insert($chunk);
        }

        return $count;
    }

    /** @param  list<int>  $memberships 200 propositions per house, each authored by one membership of 58 */
    private function propositions(string $house, array $memberships, mixed $now): void
    {
        $types = $house === 'camara' ? ['PL', 'PLP', 'PEC', 'PDL', 'PRC', 'REQ'] : ['PL', 'PLP', 'PEC', 'PDL', 'PRS', 'RQS'];
        for ($i = 0; $i < 200; $i++) {
            $id = DB::table('propositions')->insertGetId([
                'house' => $house, 'source_id' => (string) (700000 + $i), 'type' => $types[$i % 6], 'number' => $i + 1, 'year' => 2027 + $i % 4,
                'presented_on' => CarbonImmutable::create(2027, 3, 1)->addDays($i * 7)->toDateString(),
                'source_url' => "https://example.org/{$house}/proposicoes/{$i}", 'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('authorships')->insert([
                'membership_id' => $memberships[$i % count($memberships)], 'proposition_id' => $id, 'first_signer' => true,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            if ($i % 2 === 0) {
                DB::table('roll_calls')->where('house', $house)->where('source_id', $house === 'camara' ? (2000 + $i * 7).'-1' : (string) (10000 + $i * 7))
                    ->update(['proposition_id' => $id]);
            }
        }
    }
}
