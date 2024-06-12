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
        Schema::create('payment_voucher_realizations', function (Blueprint $table) {
            $table->id();
            $table->string('pv_number');
            $table->string('pvr_number');
            $table->integer('pvr_status');
            $table->timestamp('pvr_doc_date');
            $table->timestamp('pvr_due_date')->nullable();
            $table->string('pv_note')->nullable();
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
        Schema::dropIfExists('payment_voucher_realizations');
    }
};
