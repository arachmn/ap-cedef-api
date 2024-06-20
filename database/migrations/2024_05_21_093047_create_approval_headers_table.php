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
        Schema::create('approval_headers', function (Blueprint $table) {
            $table->id();
            $table->string('apvh_code')->unique();
            $table->string('dep_code')->nullable();
            $table->string('apvh_description');
            $table->integer('apvh_target');
            $table->boolean('apvh_status');
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
        Schema::dropIfExists('approval_headers');
    }
};
