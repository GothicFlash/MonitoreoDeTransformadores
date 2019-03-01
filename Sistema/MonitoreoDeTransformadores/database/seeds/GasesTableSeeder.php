<?php

use Illuminate\Database\Seeder;
use App\Gas;

class GasesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('gas')->delete();
        Gas::create([
          'id' => 1,
          'name' => 'H2'
        ]);
        Gas::create([
          'id' => 2,
          'name' => 'WC'
        ]);
        Gas::create([
          'id' => 3,
          'name' => 'CO'
        ]);
        Gas::create([
          'id' => 4,
          'name' => 'Temperatura'
        ]);
        Gas::create([
          'id' => 5,
          'name' => 'CH4'
        ]);
        Gas::create([
          'id' => 6,
          'name' => 'C2H6'
        ]);
        Gas::create([
          'id' => 7,
          'name' => 'C2H4'
        ]);
        Gas::create([
          'id' => 8,
          'name' => 'C2H2'
        ]);
        Gas::create([
          'id' => 9,
          'name' => 'CO2'
        ]);
        Gas::create([
          'id' => 10,
          'name' => 'O2'
        ]);
    }
}
