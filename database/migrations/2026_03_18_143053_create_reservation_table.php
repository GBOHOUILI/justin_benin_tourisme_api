<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("reservation", function (Blueprint $table) {
            $table->id("id_reservation");
            $table->string("type");
            $table->decimal("prix");
            $table->integer("nombre");
            $table->decimal("total");
            $table->text("description")->nullable();

            $table->foreignId("id_site")->nullable()->constrained("site");
            $table->foreignId("id_evnmt")->nullable()->constrained("evenement");
            $table->foreignId("id_user")->constrained("users");

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("reservation");
    }
};
