<?php namespace Flynsarmy\SocialLogin\Updates;

use Schema;
use Flynsarmy\SocialLogin\Models\Provider;
use October\Rain\Database\Updates\Migration;

class UpdateUserProvidersTokenColumn1023 extends Migration
{

    public function up()
    {
        Provider::query()->truncate();

        Schema::table('flynsarmy_sociallogin_user_providers', function($table)
        {
            $indexes = Schema::getConnection()
                ->getDoctrineSchemaManager()
                ->listTableIndexes('flynsarmy_sociallogin_user_providers');

            //Remove the old fields
            if ( array_key_exists('provider_id_token_index', $indexes) )
                $table->dropIndex('provider_id_token_index');
            $table->dropColumn('provider_token');
        });

        Schema::table('flynsarmy_sociallogin_user_providers', function($table)
        {
            $table->text('provider_token')->after('provider_id');
        });
    }

    public function down()
    {
        Provider::query()->truncate();

        Schema::table('flynsarmy_sociallogin_user_providers', function($table)
        {
            $indexes = Schema::getConnection()
                ->getDoctrineSchemaManager()
                ->listTableIndexes('flynsarmy_sociallogin_user_providers');

            //Remove the old fields
            if ( array_key_exists('provider_id_token_index', $indexes) )
                $table->dropIndex('provider_id_token_index');
            $table->dropColumn('provider_token');
        });

        Schema::table('flynsarmy_sociallogin_user_providers', function($table)
        {
            $table->string('provider_token')->default('')->after('provider_id');
            $table->index(['provider_id', 'provider_token'], 'provider_id_token_index');
        });
    }

}
