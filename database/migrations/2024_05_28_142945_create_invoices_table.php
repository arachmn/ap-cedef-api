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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->integer('vend_code');
            $table->integer('inv_type');
            $table->string('po_number')->nullable();
            $table->integer('rn_id')->nullable();
            $table->string('rn_number')->nullable();
            $table->string('ref_number')->nullable();
            $table->string('inv_number');
            $table->integer('inv_status');
            $table->timestamp('inv_receipt_date');
            $table->timestamp('inv_doc_date')->nullable();
            $table->timestamp('inv_due_date')->nullable();
            $table->integer('ppn_type');
            $table->string('ppn_number')->nullable();
            $table->decimal('ppn_amount', 12, 2);
            $table->string('ppn_account');
            $table->integer('pph_type');
            $table->string('pph_number')->nullable();
            $table->decimal('pph_amount', 12, 2);
            $table->string('pph_account')->nullable();
            $table->decimal('dpp_amount', 12, 2);
            $table->string('dpp_account');
            $table->decimal('inv_amount', 12, 2);
            $table->decimal('inv_payed', 12, 2)->default(0);
            $table->decimal('inv_not_payed', 12, 2);
            $table->integer('inv_pay_status')->default(false);
            $table->string('inv_note')->nullable();
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
        Schema::dropIfExists('invoices');
    }
};
