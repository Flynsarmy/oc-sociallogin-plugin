<?php namespace Flynsarmy\SocialLogin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateFlynsarmySocialLoginUserProvidersTable extends Migration
{

    public function up()
    {
        Schema::create('flynsarmy_sociallogin_user_providers', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('user_id')->unsigned()->index()->nullable();
            $table->string('provider_id')->default('');
            $table->string('provider_token')->default('');
            $table->index(['provider_id', 'provider_token'], 'provider_id_token_index');
        });
    }

    public function down()
    {
        Schema::drop('flynsarmy_sociallogin_user_providers');
    }

}
