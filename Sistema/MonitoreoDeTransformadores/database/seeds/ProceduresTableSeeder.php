<?php

use Illuminate\Database\Seeder;
use App\Procedure;

class ProceduresTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      DB::table('procedures')->delete();
      Procedure::create([
        'id' => 1,
        'description' => "Método para CALISTO 1 y MHT410"
      ]);
      Procedure::create([
        'id' => 2,
        'description' => "Método para CALISTO 2"
      ]);
      Procedure::create([
        'id' => 3,
        'description' => "Método para CALISTO 9"
      ]);
      Procedure::create([
        'id' => 4,
        'description' => "Método para OPT100"
      ]);
    }
}
