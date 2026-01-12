<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ex: "Grande Section", "6ème A", "Terminale C"
            $table->string('level_group'); // 'maternelle', 'primaire', 'college', 'lycee'
            $table->integer('level_order'); // Ordre dans le groupe (1 = plus jeune)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_classes');
    }
};
