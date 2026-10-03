<?php

namespace App\Contract;

use stdClass;

/**
 * Contract `schema_version` 3: one directory per house (contract-v3 door 1). Every file is
 * validated against `etl/schema/v3` and every cross-file reference resolved before the importer
 * writes a row (plan door 1, AC 8 to 10); one scope per legislature `meta.legislatures` lists.
 */
final class V3Reader implements ContractReader
{
    public function __construct(private readonly JsonFiles $files) {}

    public function read(string $houseDir): Snapshot
    {
        $dir = rtrim($houseDir, '/');
        /** @var stdClass $meta */
        $meta = $this->files->validated("{$dir}/meta.json", 'v3/meta.schema.json');
        $house = $meta->house;
        /** @var list<stdClass> $members */
        $members = $this->files->validated("{$dir}/members.json", 'v3/members.schema.json');
        /** @var list<stdClass> $rollCalls */
        $rollCalls = $this->files->validated("{$dir}/roll-calls.json", 'v3/roll-calls.schema.json');
        /** @var list<stdClass> $propositions */
        $propositions = $this->files->validated("{$dir}/propositions.json", 'v3/propositions.schema.json');
        /** @var list<stdClass> $rules */
        $rules = $this->files->validated("{$dir}/classification-rules.json", 'v3/classification-rules.schema.json');

        $legislatures = [];
        foreach ($meta->legislatures as $l) {
            $legislatures[$l->id] = ['number' => $l->id, 'starts_on' => $l->start, 'ends_on' => $l->end];
        }
        $refuse = fn (string $file, string $reason, int|string $id) => new ContractException("{$dir}/{$file}: {$reason}: {$id}");
        $sameHouse = function (stdClass $record, string $file, int|string $id) use ($house, $refuse) {
            if ($record->house !== $house) {
                throw $refuse($file, "house {$record->house} differs from meta.house {$house}", $id);
            }
        };
        $listed = function (int $legislature, string $file, int|string $id) use ($legislatures, $refuse) {
            if (! isset($legislatures[$legislature])) {
                throw $refuse($file, "legislature {$legislature} not in meta.legislatures", $id);
            }
        };

        $ruleRecords = [];
        foreach ($rules as $r) {
            $sameHouse($r, 'classification-rules.json', $r->id);
            $ruleRecords[$r->id] = ['rule_id' => $r->id, 'kind' => $r->kind, 'field' => $r->field, 'pattern' => $r->pattern, 'description' => $r->description];
        }

        $propositionRecords = $presented = $authors = [];
        foreach ($propositions as $p) {
            $sameHouse($p, 'propositions.json', $p->id);
            $id = (string) $p->id;
            $propositionRecords[$id] = [
                'source_id' => $id,
                'type' => $p->type,
                'number' => $p->number,
                'year' => $p->year,
                'summary' => $p->summary,
                'presented_on' => $p->presentedAt,
                'status' => $p->status,
                'source_url' => $p->sourceUrl,
            ];
            $presented[$id] = $p->presentedAt;
            $authors[$id] = $p->authors;
        }

        $memberRecords = $memberships = [];
        foreach ($members as $m) {
            $sameHouse($m, 'members.json', $m->id);
            $id = (string) $m->id;
            $memberRecords[$id] = [
                'source_id' => $id,
                'name' => $m->name,
                'party' => $m->party,
                'uf' => $m->uf,
                'photo_url' => $m->photoUrl,
                'source_url' => $m->sourceUrl,
            ];
            foreach ($m->mandates as $mandate) {
                $listed($mandate->legislature, 'members.json', $m->id);
                $memberships[$mandate->legislature][$id] = $this->membership($id, $mandate);
            }
        }
        $refuseMember = function (int $memberId, string $file, string $role) use ($memberRecords, $refuse) {
            if (! isset($memberRecords[(string) $memberId])) {
                throw $refuse($file, "{$role} member not in members.json", $memberId);
            }
        };

        $rollCallRecords = $votes = [];
        $byId = [];
        foreach ($rollCalls as $r) {
            $sameHouse($r, 'roll-calls.json', $r->id);
            $listed($r->legislature, 'roll-calls.json', $r->id);
            if ($r->kindRule !== null && ! isset($ruleRecords[$r->kindRule])) {
                throw $refuse('roll-calls.json', "kindRule {$r->kindRule} not in classification-rules.json", $r->id);
            }
            if ($r->propositionId !== null && ! isset($propositionRecords[(string) $r->propositionId])) {
                throw $refuse('roll-calls.json', "proposition {$r->propositionId} not in propositions.json", $r->id);
            }
            $byId[$r->id] = $r;
        }

        foreach ($this->jsonFiles("{$dir}/roll-calls") as $id) {
            if (! isset($byId[$id])) {
                throw $refuse("roll-calls/{$id}.json", 'roll call not in roll-calls.json', $id);
            }
            if ($byId[$id]->ballot === 'symbolic') {
                throw $refuse("roll-calls/{$id}.json", 'roll-call file for a symbolic roll call', $id);
            }
        }

        foreach ($rollCalls as $r) {
            $record = [
                'source_id' => $r->id,
                'date' => $r->date,
                'organ' => $r->organ,
                'description' => $r->description,
                'proposition_source_id' => $r->propositionId === null ? null : (string) $r->propositionId,
                'approved' => $r->approved,
                'ballot' => $r->ballot,
                'kind' => $r->kind,
                'kind_rule' => $r->kindRule,
                'tally_yes' => $r->tallies?->yes,
                'tally_no' => $r->tallies?->no,
                'tally_others' => $r->tallies?->others,
                'government_orientation' => $r->governmentOrientation,
                'source_url' => $r->sourceUrl,
                'opening_description' => null,
                'last_presentation_description' => null,
            ];
            if ($r->ballot !== 'symbolic') {
                $file = "roll-calls/{$r->id}.json";
                if (! is_file("{$dir}/{$file}")) {
                    throw $refuse($file, 'file not found for a nominal or secret roll call', $r->id);
                }
                /** @var stdClass $detail */
                $detail = $this->files->validated("{$dir}/{$file}", 'v3/roll-call.schema.json');
                $sameHouse($detail, $file, $r->id);
                if ($detail->id !== $r->id) {
                    throw $refuse($file, "id {$detail->id} differs from the file name", $r->id);
                }
                $record['opening_description'] = $detail->openingDescription;
                $record['last_presentation_description'] = $detail->lastPresentationDescription;
                foreach ($detail->votes as $v) {
                    $refuseMember($v->memberId, $file, 'vote');
                    $votes[$r->legislature][] = [
                        'roll_call_source_id' => $r->id,
                        'member_source_id' => (string) $v->memberId,
                        'official' => $v->official,
                        'position' => $v->position,
                        'party' => $v->party,
                        'party_majority' => $v->partyMajority,
                    ];
                }
            }
            $rollCallRecords[$r->legislature][] = $record;
        }

        $authorships = [];
        foreach ($authors as $propositionId => $list) {
            $legislature = $this->legislatureOn($presented[$propositionId], $legislatures);
            foreach ($list as $a) {
                $refuseMember($a->memberId, 'propositions.json', 'author');
                // Authorship belongs to the mandate in force when the proposition was presented (AC 4).
                if ($legislature !== null && isset($memberships[$legislature][(string) $a->memberId])) {
                    $authorships[$legislature][] = [
                        'member_source_id' => (string) $a->memberId,
                        'proposition_source_id' => (string) $propositionId,
                        'first_signer' => $a->firstSigner,
                    ];
                }
            }
        }

        $fullTexts = [];
        foreach ($this->jsonFiles("{$dir}/full-texts") as $name) {
            $file = "full-texts/{$name}.json";
            /** @var stdClass $text */
            $text = $this->files->validated("{$dir}/{$file}", 'v3/full-text.schema.json');
            $sameHouse($text, $file, $text->propositionId);
            if ((string) $text->propositionId !== $name) {
                throw $refuse($file, "propositionId {$text->propositionId} differs from the file name", $name);
            }
            if (! isset($propositionRecords[$name])) {
                throw $refuse($file, 'proposition not in propositions.json', $name);
            }
            $fullTexts[] = [
                'proposition_source_id' => $name,
                'source_url' => $text->sourceUrl,
                'document_sha256' => $text->documentSha256,
                'extractor' => $text->extractor,
                'extracted_at' => $text->extractedAt,
                'text' => $text->text,
            ];
        }

        $scopes = [];
        foreach (array_keys($legislatures) as $number) {
            $scopes[] = new Scope(
                house: $house,
                legislature: $number,
                memberships: array_values($memberships[$number] ?? []),
                rollCalls: $rollCallRecords[$number] ?? [],
                votes: $votes[$number] ?? [],
                authorships: $authorships[$number] ?? [],
            );
        }

        return new Snapshot(
            schemaVersion: $meta->schema_version,
            house: $house,
            generatedAt: $meta->generatedAt,
            classificationVersion: $meta->classification->version,
            legislatures: array_values($legislatures),
            coverage: json_decode((string) json_encode($meta->coverage), true),
            members: array_values($memberRecords),
            propositions: array_values($propositionRecords),
            rules: array_values($ruleRecords),
            fullTexts: $fullTexts,
            scopes: $scopes,
        );
    }

    /** @return array<string, mixed> */
    private function membership(string $memberId, stdClass $mandate): array
    {
        $record = ['member_source_id' => $memberId, 'party' => $mandate->party, 'uf' => $mandate->uf];
        foreach (['participation' => 'participation', 'governmentAlignment' => 'government_alignment', 'partyAlignment' => 'party_alignment'] as $key => $column) {
            foreach (['all', 'merit'] as $basis) {
                $record["{$column}_{$basis}_count"] = $mandate->{$key}->{$basis}->count;
                $record["{$column}_{$basis}_total"] = $mandate->{$key}->{$basis}->total;
            }
        }

        return [
            ...$record,
            'symbolic_merit' => $mandate->symbolicMerit,
            'authored_count' => $mandate->authoredCount,
            'first_signer_count' => $mandate->firstSignerCount,
            'requirements_count' => $mandate->requirementsCount,
            'periods' => array_map(fn (stdClass $p) => ['starts_at' => $p->start, 'ends_at' => $p->end], $mandate->exercisePeriods),
        ];
    }

    /**
     * The legislature whose dates contain `$date`, among those the directory lists.
     *
     * @param  array<int, array{number: int, starts_on: string, ends_on: string}>  $legislatures
     */
    private function legislatureOn(?string $date, array $legislatures): ?int
    {
        if ($date === null) {
            return null;
        }
        foreach ($legislatures as $l) {
            if ($l['starts_on'] <= $date && $date <= $l['ends_on']) {
                return $l['number'];
            }
        }

        return null;
    }

    /** @return list<string> base names of the `*.json` files of `$dir`, sorted; none when it does not exist */
    private function jsonFiles(string $dir): array
    {
        $names = array_map(fn (string $path) => basename($path, '.json'), glob("{$dir}/*.json") ?: []);
        sort($names);

        return $names;
    }
}
