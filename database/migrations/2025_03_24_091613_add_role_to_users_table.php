<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
       # Schema::table('users', function (Blueprint $table) {
        #    $table->string('role')->default('user')->after('email'); // Ajout de la colonne role
        #});
    }

    public function down()
    {
    #    Schema::table('users', function (Blueprint $table) {
     #       $table->dropColumn('role'); // Suppression de la colonne en cas de rollback
      #  });
    }
};
