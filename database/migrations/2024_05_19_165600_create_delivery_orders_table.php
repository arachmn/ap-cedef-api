<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->string('dep_code');
            $table->string('do_number');
            $table->timestamp('do_date');
            $table->string('po_number');
            $table->integer('vend_code');
            $table->string('do_note')->nullable();
            $table->text('do_description');
            $table->decimal('do_amount', 12, 2);
            $table->integer('do_status');
            $table->integer('user_id');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_orders');
    }
};
