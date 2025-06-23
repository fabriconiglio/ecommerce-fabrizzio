<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('uuid')->unique()->nullable()->after('id');
            $table->string('username')->unique()->nullable()->after('email');
            $table->string('first_name')->nullable()->after('password');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('avatar', 500)->nullable()->after('last_name');
            $table->string('phone')->nullable()->after('avatar');
            $table->json('address')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('address');
            
            $table->index(['uuid', 'username', 'is_active']);
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'uuid', 'username', 'first_name', 'last_name', 
                'avatar', 'phone', 'address', 'is_active'
            ]);
        });
    }
};
