<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        Program::create([
            'name' => 'Flood Control Infrastructure',
            'name_abbreviation' => 'FCI',
            'description' => 'Flood Control Infrastructure Program for disaster risk reduction and management',
            'slug' => 'flood-control-infrastructure',
        ]);

        Program::create([
            'name' => 'National Road Infrastructure',
            'name_abbreviation' => 'NRI',
            'description' => 'National road development and maintenance program',
            'slug' => 'national-road-infrastructure',
        ]);

        Program::create([
            'name' => 'School Building Program',
            'name_abbreviation' => 'SBP',
            'description' => 'Construction and rehabilitation of school facilities',
            'slug' => 'school-building-program',
        ]);
    }
}
