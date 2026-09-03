<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAutomasSmsToSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'sms_gateway')) {
                $table->string('sms_gateway')->nullable()->default('automas');
            }
            if (!Schema::hasColumn('settings', 'automas_api_key')) {
                $table->string('automas_api_key')->nullable();
            }
            if (!Schema::hasColumn('settings', 'automas_sender_id')) {
                $table->string('automas_sender_id')->nullable();
            }
            if (!Schema::hasColumn('settings', 'automas_type')) {
                $table->string('automas_type')->nullable()->default('auto');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'sms_gateway',
                'automas_api_key',
                'automas_sender_id',
                'automas_type'
            ]);
        });
    }
}
