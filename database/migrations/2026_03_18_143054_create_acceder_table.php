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
        Schema::create("acceder", function (Blueprint $table) {
            $table
                ->foreignId("id_admin")
                ->constrained("admin")
                ->cascadeOnDelete();
            $table
                ->foreignId("id_fonc")
                ->constrained("fonctionnalite")
                ->cascadeOnDelete();
            $table->boolean("status")->default(1);

            $table->primary(["id_admin", "id_fonc"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("acceder");
    }
};
