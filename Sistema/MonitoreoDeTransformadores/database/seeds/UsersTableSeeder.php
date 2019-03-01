<?php

use Illuminate\Database\Seeder;
use App\User;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
      DB::table('users')->delete();
      //Config user master
      User::create([
        'name' => 'MasterConfig',
        'email' => 'master_config@confiamex.com',
        'type' => 'config',
        'password' => bcrypt('mastercfgroot'),
        'encrypted_password' => Crypt::encrypt('mastercfgroot')
      ]);
    }
}
