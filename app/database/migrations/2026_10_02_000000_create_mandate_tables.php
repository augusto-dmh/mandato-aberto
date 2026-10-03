<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The stored shape of the ETL contract (plan door 4): house and legislature are dimensions from
 * day one, every source id is text, and each row is unique on its natural key so an import can
 * upsert by it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legislatures', function (Blueprint $table) {
            $table->unsignedSmallInteger('number')->primary();
            $table->timestamps();
        });

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->text('house');
            $table->text('source_id');
            $table->text('name');
            $table->text('party');
            $table->text('uf');
            $table->text('source_url');
            $table->timestamps();
            $table->unique(['house', 'source_id']);
        });

        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained();
            $table->unsignedSmallInteger('legislature_number');
            $table->foreign('legislature_number')->references('number')->on('legislatures');
            foreach (['participation', 'government_alignment', 'party_alignment'] as $indicator) {
                $table->unsignedInteger("{$indicator}_count");
                $table->unsignedInteger("{$indicator}_total");
            }
            $table->unsignedInteger('authored_count');
            $table->unsignedInteger('first_signer_count');
            $table->unsignedInteger('requirements_count');
            $table->timestamps();
            $table->unique(['member_id', 'legislature_number']);
        });

        Schema::create('propositions', function (Blueprint $table) {
            $table->id();
            $table->text('house');
            $table->text('source_id');
            $table->text('title')->nullable();
            $table->text('summary')->nullable();
            $table->timestamps();
            $table->unique(['house', 'source_id']);
        });

        Schema::create('roll_calls', function (Blueprint $table) {
            $table->id();
            $table->text('house');
            $table->text('source_id');
            $table->unsignedSmallInteger('legislature_number');
            $table->foreign('legislature_number')->references('number')->on('legislatures');
            $table->date('date');
            $table->text('organ');
            $table->text('description');
            $table->foreignId('proposition_id')->nullable()->constrained();
            $table->boolean('approved')->nullable();
            $table->boolean('secret');
            $table->unsignedInteger('tally_yes');
            $table->unsignedInteger('tally_no');
            $table->unsignedInteger('tally_others');
            $table->text('government_orientation')->nullable();
            $table->text('source_url');
            $table->timestamps();
            $table->unique(['house', 'source_id']);
            $table->index(['house', 'legislature_number']);
        });

        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roll_call_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained();
            $table->text('vote');
            $table->text('party');
            $table->text('party_majority')->nullable();
            $table->timestamps();
            $table->unique(['roll_call_id', 'member_id']);
            $table->index('member_id');
        });

        Schema::create('authorships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proposition_id')->constrained();
            $table->timestamps();
            $table->unique(['membership_id', 'proposition_id']);
        });

        Schema::create('contract_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('schema_version');
            $table->timestampTz('generated_at');
            $table->char('meta_sha256', 64);
            $table->unsignedInteger('members_count');
            $table->unsignedInteger('roll_calls_count');
            $table->unsignedInteger('votes_count');
            $table->unsignedInteger('propositions_count');
            $table->timestamps();
        });

        foreach (['members', 'propositions', 'roll_calls'] as $table) {
            DB::statement("alter table {$table} add constraint {$table}_house_check check (house in ('camara', 'senado'))");
        }
    }

    public function down(): void
    {
        foreach (['contract_imports', 'authorships', 'votes', 'roll_calls', 'propositions', 'memberships', 'members', 'legislatures'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
