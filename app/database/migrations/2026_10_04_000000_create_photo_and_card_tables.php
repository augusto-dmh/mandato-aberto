<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Official photo versions and card snapshots (share-cards door 2). Neither table has a foreign key
 * to the contract tables: an import's sweep, or app-contract-v3's emptying migration, must never
 * delete the photo or the snapshot behind a code already printed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_versions', function (Blueprint $table) {
            $table->id();
            $table->text('house');
            $table->text('member_source_id');
            $table->char('sha256', 64);
            $table->text('source_url');
            $table->text('final_url');
            $table->unsignedInteger('bytes');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->timestampTz('fetched_at');
            $table->timestampTz('checked_at');
            $table->unique(['house', 'member_source_id', 'sha256']);
            $table->index('sha256');
        });

        Schema::create('card_snapshots', function (Blueprint $table) {
            $table->id();
            $table->text('code')->unique();
            $table->char('digest', 64)->unique();
            $table->text('kind');
            $table->text('house');
            $table->text('source_id');
            $table->unsignedSmallInteger('legislature')->nullable();
            $table->unsignedInteger('template');
            $table->jsonb('payload');
            $table->timestampTz('created_at');
            $table->index(['kind', 'house', 'source_id', 'legislature', 'id']);
        });

        DB::statement("alter table photo_versions add constraint photo_versions_house_check check (house in ('camara','senado'))");
        DB::statement("alter table card_snapshots add constraint card_snapshots_house_check check (house in ('camara','senado'))");
        DB::statement("alter table card_snapshots add constraint card_snapshots_kind_check check (kind in ('member','roll_call'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('card_snapshots');
        Schema::dropIfExists('photo_versions');
    }
};
