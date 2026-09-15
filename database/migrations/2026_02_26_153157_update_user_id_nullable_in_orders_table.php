<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'user_id')) {
                $table->dropForeign(['user_id']);
            }
        });

        // The original orders migration already declares user_id nullable. MySQL
        // needs the explicit MODIFY, while SQLite cannot execute MySQL syntax.
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE `orders` MODIFY `user_id` BIGINT UNSIGNED NULL');
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'user_id')) {
                $table->dropForeign(['user_id']);
            }
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE `orders` MODIFY `user_id` BIGINT UNSIGNED NOT NULL');
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
