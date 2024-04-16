<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\Company;
use App\Models\Department;
use App\Models\Division;
use App\Models\DivisionCategory;
use App\Models\FileType;
use App\Models\JobBand;
use App\Models\JobRate;
use App\Models\Location;
use App\Models\Position;
use App\Models\Subunit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $data = [];
        $faker = Faker::create();

        for ($i = 0; $i < 10000; $i++) {
            $data[]  = [
                'code' => $faker->randomDigit(),
                'location_name' => $faker->company(),
                // 'department_name' => $faker->company(),
                // 'department_code' => $faker->randomNumber(5, true),
                // 'department_name' => $faker->company(),
                // 'division_id' => $faker->randomDigit(),
                // 'division_cat_id' => $faker->randomDigit(),
                // 'company_id' => $faker->randomDigit(),
                // 'location_id' => $faker->randomDigit(),
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString()
                // 'job_level' => $faker->randomDigit(),
                // 'jobrate_name' => $faker->company(),
                // 'job_rate' => $faker->company(),
                // 'salary_structure' => $faker->company(),
                // 'created_at' => now()->toDateTimeString(),
                // 'updated_at' => now()->toDateTimeString()
            ];
        }

        $chunks = array_chunk($data, 1000);

        foreach ($chunks as $chunk) {
            Location::insert($chunk);
        }

        // \App\Models\User::factory(10)->create();
        // Location::factory(50000)->create();
    }
}
