<?php

namespace App\Contract;

use App\Models\House;
use stdClass;

/**
 * Contract `schema_version` 2: the Câmara in the 57th legislature, so every record is stamped with
 * that scope (plan door 5). Members and their indicators come from `deputies.json`, votes and
 * authorships from `deputies/{id}.json` (they carry `partyMajority`), roll calls from
 * `roll-calls.json`; the per-roll-call files are validated but supply no row of their own.
 */
final class V2Reader implements ContractReader
{
    public const LEGISLATURE = 57;

    public function __construct(private readonly JsonFiles $files) {}

    public function read(string $dir): Snapshot
    {
        /** @var stdClass $meta */
        $meta = $this->files->validated("{$dir}/meta.json", 'meta.schema.json');
        /** @var list<stdClass> $deputies */
        $deputies = $this->files->validated("{$dir}/deputies.json", 'deputies.schema.json');
        /** @var list<stdClass> $rollCalls */
        $rollCalls = $this->files->validated("{$dir}/roll-calls.json", 'roll-calls.schema.json');

        $members = $memberships = $votes = $authorships = [];
        $propositions = $rollCallRecords = [];

        foreach ($rollCalls as $r) {
            $rollCallRecords[] = [
                'source_id' => $r->id,
                'date' => $r->date,
                'organ' => $r->organ,
                'description' => $r->description,
                'proposition_source_id' => $r->proposition === null ? null : (string) $r->proposition->id,
                'approved' => $r->approved,
                'secret' => $r->secret,
                'tally_yes' => $r->tallies->yes,
                'tally_no' => $r->tallies->no,
                'tally_others' => $r->tallies->others,
                'government_orientation' => $r->governmentOrientation,
                'source_url' => $r->sourceUrl,
            ];
            if ($r->proposition !== null) {
                $propositions[(string) $r->proposition->id] = [
                    'source_id' => (string) $r->proposition->id,
                    'title' => $r->proposition->title,
                    'summary' => $r->proposition->summary,
                ];
            }
        }
        $known = array_flip(array_column($rollCallRecords, 'source_id'));

        foreach ($rollCalls as $r) {
            $this->files->validated("{$dir}/roll-calls/{$r->id}.json", 'roll-call.schema.json');
        }

        foreach ($deputies as $d) {
            $id = (string) $d->id;
            $members[] = [
                'source_id' => $id,
                'name' => $d->name,
                'party' => $d->party,
                'uf' => $d->uf,
                'source_url' => $d->sourceUrl,
            ];
            $memberships[] = [
                'member_source_id' => $id,
                'participation_count' => $d->participation->count,
                'participation_total' => $d->participation->total,
                'government_alignment_count' => $d->governmentAlignment->count,
                'government_alignment_total' => $d->governmentAlignment->total,
                'party_alignment_count' => $d->partyAlignment->count,
                'party_alignment_total' => $d->partyAlignment->total,
                'authored_count' => $d->authoredCount,
                'first_signer_count' => $d->firstSignerCount,
                'requirements_count' => $d->requirementsCount,
            ];

            $path = "{$dir}/deputies/{$id}.json";
            /** @var stdClass $file */
            $file = $this->files->validated($path, 'deputy.schema.json');
            if ((string) $file->id !== $id) {
                throw new ContractException("{$path}: /id: {$file->id} does not match the file name");
            }
            foreach ($file->votes as $i => $v) {
                if (! isset($known[$v->rollCallId])) {
                    throw new ContractException("{$path}: /votes/{$i}/rollCallId: roll call {$v->rollCallId} is not in roll-calls.json");
                }
                $votes[] = [
                    'roll_call_source_id' => $v->rollCallId,
                    'member_source_id' => $id,
                    'vote' => $v->vote,
                    'party' => $v->party,
                    'party_majority' => $v->partyMajority,
                ];
            }
            foreach ($file->authored as $a) {
                $propositions[(string) $a->id] ??= ['source_id' => (string) $a->id, 'title' => null, 'summary' => null];
                $authorships[] = ['member_source_id' => $id, 'proposition_source_id' => (string) $a->id];
            }
        }

        return new Snapshot(
            schemaVersion: $meta->schema_version,
            generatedAt: $meta->generatedAt,
            scopes: [new Scope(
                house: House::Camara->value,
                legislature: self::LEGISLATURE,
                members: $members,
                memberships: $memberships,
                propositions: array_values($propositions),
                rollCalls: $rollCallRecords,
                votes: $votes,
                authorships: $authorships,
            )],
        );
    }
}
