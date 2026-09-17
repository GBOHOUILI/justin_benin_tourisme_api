<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('prix', function (Blueprint $table) {
            $table->boolean('echelonnable')->default(false)->after('montant');
            $table->unsignedTinyInteger('nombre_echeances')->nullable()->after('echelonnable');
        });
    }

    public function down(): void
    {
        Schema::table('prix', function (Blueprint $table) {
            $table->dropColumn(['echelonnable', 'nombre_echeances']);
        });
    }
};
