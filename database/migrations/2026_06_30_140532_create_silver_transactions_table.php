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
        Schema::create('silver_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('group_id')->nullable()->constrained();
            $table->enum('type',[
                'buy',
                'sell',
                'deposit',
                'withdraw'
            ]);
            $table->decimal('weight',20,3)->default(0);
            $table->decimal('price',20,2)->default(0);
            $table->decimal('amount',20,2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('silver_transactions');
    }
};
