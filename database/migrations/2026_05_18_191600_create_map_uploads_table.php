<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('map_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('file_type');
            $table->string('file_size');
            $table->string('uploaded_by')->default('admin');
            $table->string('status')->default('Processed');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('map_uploads');
    }
};
