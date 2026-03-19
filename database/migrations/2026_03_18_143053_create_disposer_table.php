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
        Schema::create("disposer", function (Blueprint $table) {
            $table
                ->foreignId("id_evnmt")
                ->constrained("evenement")
                ->cascadeOnDelete();
            $table
                ->foreignId("id_site")
                ->constrained("site")
                ->cascadeOnDelete();
            $table->primary(["id_evnmt", "id_site"]);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("disposer");
    }
};
