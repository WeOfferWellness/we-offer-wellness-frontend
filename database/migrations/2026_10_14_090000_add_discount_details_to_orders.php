<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'discount_code')) {
                $table->string('discount_code', 64)->nullable()->after('amount_total');
            }
            if (! Schema::hasColumn('orders', 'discount_amount')) {
                $table->unsignedBigInteger('discount_amount')->default(0)->after('discount_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('orders', 'discount_amount')) {
                $columns[] = 'discount_amount';
            }
            if (Schema::hasColumn('orders', 'discount_code')) {
                $columns[] = 'discount_code';
            }
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
