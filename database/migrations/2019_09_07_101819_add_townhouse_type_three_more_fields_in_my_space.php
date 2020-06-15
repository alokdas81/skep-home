<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTownhouseTypeThreeMoreFieldsInMySpace extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('my_space', function (Blueprint $table) {
            $table->string('family_room')->after('dens')->nullable();
            $table->string('dining_room')->after('family_room')->nullable();
            $table->string('powder_room')->after('dining_room')->nullable();
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('my_space', function (Blueprint $table) {
            $table->dropColumn('family_room');
            $table->dropColumn('dining_room');
            $table->dropColumn('powder_room'); 
        });
    }
}
