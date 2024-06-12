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
        Schema::create('receipt_notes', function (Blueprint $table) {
            $table->id();
            $table->string('dep_code');
            $table->integer('do_id');
            $table->string('do_number');
            $table->string('apvdh_code')->unique();
            $table->string('rn_number')->unique();
            $table->timestamp('rn_date');
            $table->timestamp('rn_receipt_date')->nullable();
            $table->integer('rnt_id');
            $table->integer('rn_status');
            $table->string('rn_note')->nullable();
            $table->text('rn_description');
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
        Schema::dropIfExists('receipt_notes');
    }
};
