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
        Schema::create("evenement", function (Blueprint $table) {
            $table->id("id_evnmt");
            $table->string("libelle");
            $table->string("adresse");
            $table->decimal("longitude", 10, 7);
            $table->decimal("latitude", 10, 7);
            $table->text("description")->nullable();
            $table->decimal("prix")->nullable();
            $table->date("date_debut");
            $table->date("date_fin");
            $table->boolean("status")->default(1);

            $table->foreignId("id_cat_evenmt")->constrained("cat_evenmt");
            $table->foreignId("id_admin")->constrained("admin");

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("evenement");
    }
};
