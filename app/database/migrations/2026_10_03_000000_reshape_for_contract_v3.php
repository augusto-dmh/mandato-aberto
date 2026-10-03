<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The stored shape of contract v3 (plan door 3). The contract tables are a derived copy of the
 * ETL's files, and v2 rows carry no ballot, kind or position to backfill, so they are emptied
 * first; the next `mandato:import` refills them. Enum columns hold the contract's literal values,
 * enforced by checks.
 */
return new class extends Migration
{
    private const TABLES = ['votes', 'authorships', 'roll_calls', 'memberships', 'propositions', 'members', 'contract_imports', 'legislatures'];

    private const POSITIONS = "('yes','no','abstention','obstruction','presiding','secret','notVoting')";

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            DB::table($table)->delete();
        }

        Schema::table('legislatures', function (Blueprint $table) {
            $table->date('starts_on');
            $table->date('ends_on');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->text('photo_url');
        });

        Schema::table('memberships', function (Blueprint $table) {
            $table->dropColumn([
                'participation_count', 'participation_total', 'government_alignment_count', 'government_alignment_total',
                'party_alignment_count', 'party_alignment_total',
            ]);
        });
        Schema::table('memberships', function (Blueprint $table) {
            $table->text('party');
            $table->text('uf');
            foreach (['participation', 'government_alignment', 'party_alignment'] as $indicator) {
                foreach (['all', 'merit'] as $basis) {
                    $table->unsignedInteger("{$indicator}_{$basis}_count");
                    $table->unsignedInteger("{$indicator}_{$basis}_total");
                }
            }
            // Null means the house publishes no symbolic roll calls, never zero (contract-v3 door 9).
            $table->unsignedInteger('symbolic_merit')->nullable();
        });

        Schema::create('exercise_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_id')->constrained()->cascadeOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamps();
            $table->unique(['membership_id', 'starts_at']);
        });

        Schema::table('roll_calls', function (Blueprint $table) {
            $table->dropColumn('secret');
        });
        Schema::table('roll_calls', function (Blueprint $table) {
            $table->text('ballot');
            $table->text('kind');
            // A rule id with no foreign key: a house's rules are replaced as a set on each import.
            $table->text('kind_rule')->nullable();
            $table->text('opening_description')->nullable();
            $table->text('last_presentation_description')->nullable();
            $table->unsignedInteger('tally_yes')->nullable()->change();
            $table->unsignedInteger('tally_no')->nullable()->change();
            $table->unsignedInteger('tally_others')->nullable()->change();
        });

        Schema::table('votes', function (Blueprint $table) {
            $table->renameColumn('vote', 'official');
        });
        Schema::table('votes', function (Blueprint $table) {
            $table->text('position');
        });

        Schema::table('propositions', function (Blueprint $table) {
            $table->text('type');
            $table->integer('number')->nullable();
            $table->integer('year')->nullable();
            $table->date('presented_on')->nullable();
            $table->text('status')->nullable();
            $table->text('source_url');
        });

        Schema::table('authorships', function (Blueprint $table) {
            $table->boolean('first_signer');
        });

        Schema::create('classification_rules', function (Blueprint $table) {
            $table->id();
            $table->text('house');
            $table->text('rule_id');
            $table->unsignedSmallInteger('position');
            $table->text('kind');
            $table->text('field');
            $table->text('pattern');
            $table->text('description');
            $table->timestamps();
            $table->unique(['house', 'rule_id']);
        });

        Schema::create('full_texts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposition_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('source_url');
            $table->char('document_sha256', 64);
            $table->text('extractor');
            $table->timestampTz('extracted_at');
            $table->text('text');
            $table->timestamps();
        });

        Schema::table('contract_imports', function (Blueprint $table) {
            $table->text('house');
            $table->unsignedInteger('classification_version');
            $table->jsonb('coverage');
            $table->unsignedInteger('mandates_count');
            $table->unsignedInteger('full_texts_count');
        });

        $checks = [
            'roll_calls' => [
                'roll_calls_ballot_check' => "ballot in ('nominal','secret','symbolic')",
                'roll_calls_kind_check' => "kind in ('final','amendment','procedural','unclassified')",
                'roll_calls_tallies_check' => '(tally_yes is null) = (tally_no is null) and (tally_no is null) = (tally_others is null)',
                'roll_calls_government_orientation_check' => "government_orientation in ('yes','no','abstention','obstruction','free')",
            ],
            'votes' => [
                'votes_position_check' => 'position in '.self::POSITIONS,
                'votes_party_majority_check' => 'party_majority in '.self::POSITIONS,
            ],
            'classification_rules' => [
                'classification_rules_house_check' => "house in ('camara','senado')",
                'classification_rules_kind_check' => "kind in ('final','amendment','procedural')",
            ],
            'contract_imports' => [
                'contract_imports_house_check' => "house in ('camara','senado')",
            ],
        ];
        foreach ($checks as $table => $constraints) {
            foreach ($constraints as $name => $expression) {
                DB::statement("alter table {$table} add constraint {$name} check ({$expression})");
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            DB::table($table)->delete();
        }
        Schema::dropIfExists('full_texts');
        Schema::dropIfExists('classification_rules');
        Schema::dropIfExists('exercise_periods');

        Schema::table('contract_imports', function (Blueprint $table) {
            $table->dropColumn(['house', 'classification_version', 'coverage', 'mandates_count', 'full_texts_count']);
        });
        Schema::table('authorships', function (Blueprint $table) {
            $table->dropColumn('first_signer');
        });
        Schema::table('propositions', function (Blueprint $table) {
            $table->dropColumn(['type', 'number', 'year', 'presented_on', 'status', 'source_url']);
        });
        DB::statement('alter table votes drop constraint votes_position_check, drop constraint votes_party_majority_check');
        Schema::table('votes', function (Blueprint $table) {
            $table->dropColumn('position');
        });
        Schema::table('votes', function (Blueprint $table) {
            $table->renameColumn('official', 'vote');
        });
        DB::statement('alter table roll_calls drop constraint roll_calls_tallies_check, drop constraint roll_calls_government_orientation_check');
        Schema::table('roll_calls', function (Blueprint $table) {
            $table->dropColumn(['ballot', 'kind', 'kind_rule', 'opening_description', 'last_presentation_description']);
            $table->unsignedInteger('tally_yes')->nullable(false)->change();
            $table->unsignedInteger('tally_no')->nullable(false)->change();
            $table->unsignedInteger('tally_others')->nullable(false)->change();
            $table->boolean('secret');
        });
        Schema::table('memberships', function (Blueprint $table) {
            $columns = ['party', 'uf', 'symbolic_merit'];
            foreach (['participation', 'government_alignment', 'party_alignment'] as $indicator) {
                foreach (['all', 'merit'] as $basis) {
                    $columns[] = "{$indicator}_{$basis}_count";
                    $columns[] = "{$indicator}_{$basis}_total";
                }
            }
            $table->dropColumn($columns);
        });
        Schema::table('memberships', function (Blueprint $table) {
            foreach (['participation', 'government_alignment', 'party_alignment'] as $indicator) {
                $table->unsignedInteger("{$indicator}_count");
                $table->unsignedInteger("{$indicator}_total");
            }
        });
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('photo_url');
        });
        Schema::table('legislatures', function (Blueprint $table) {
            $table->dropColumn(['starts_on', 'ends_on']);
        });
    }
};
