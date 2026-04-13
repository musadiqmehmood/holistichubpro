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
        Schema::create('store_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('store_code')->unique();
            $table->string('store_name');
            $table->string('mobile');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('gst_number')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('pan_number')->nullable();
            $table->string('store_website')->nullable();
            $table->boolean('show_signature_on_invoice')->default(false);
            $table->string('signature')->nullable();
            $table->text('bank_details')->nullable();
            $table->string('store_logo')->nullable();
            $table->foreignId('branch_id')->constrained('branches');
            $table->string('timezone')->default('UTC');
            $table->string('date_format')->default('Y-m-d');
            $table->string('time_format')->default('H:i:s');
            $table->string('currency', 10)->default('USD');
            $table->enum('currency_symbol_placement', ['before', 'after'])->default('before');
            $table->unsignedTinyInteger('decimals')->default(2);
            $table->unsignedTinyInteger('decimals_for_quantity')->default(2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
