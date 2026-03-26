<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('set null');
            $table->decimal('total_amount', 10, 2)->nullable()->after('status');
            $table->text('shipping_address')->nullable()->after('total_amount');
            $table->string('phone')->nullable()->after('shipping_address');
            $table->string('payment_method')->default('cod')->after('phone');
            $table->string('order_status')->default('pending')->after('payment_method');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'total_amount', 'shipping_address', 'phone', 'payment_method', 'order_status']);
        });
    }
};