<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('admin', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom');
            $table->string('tel')->unique();
            $table->string('password');
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('admin'); }
};
