<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'analytics_purchase_claimed_at')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->timestamp('analytics_purchase_claimed_at')
                    ->nullable()
                    ->index()
                    ->after('analytics_purchase_emitted_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'analytics_purchase_claimed_at')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropColumn('analytics_purchase_claimed_at');
            });
        }
    }
};
