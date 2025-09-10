<?php declare(strict_types=1);

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
        Schema::create('redirect_uris', function (Blueprint $table) {
            $table->id();

            $table->string('code', 20);
            $table->text('uri');

            $table->integer('read_times')
                ->default(0);

            $table->integer('max_read_times')
                ->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redirect_uris');
    }
};
