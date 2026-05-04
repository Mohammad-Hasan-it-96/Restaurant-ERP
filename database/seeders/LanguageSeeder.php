<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            [
                'name' => 'English',
                'code' => 'EN',
                'direction' => 'ltr' ,
                'status' => 1 ,
                'is_default' => 0 ,
                'flag_path' =>'flags/en.png'
            ],
            ['name' => 'Arabic',
                'code' => 'AR',
                'direction' => 'rtl' ,
                'status' => 1 ,
                'is_default' => 1 ,
                'flag_path' =>'flags/ar.png'
            ],
        ];
        foreach ($languages as $language) {
            Language::updateOrCreate(
                ['code' => $language['code']],   // match key
                [
                    'name'       => $language['name'],
                    'direction'  => $language['direction'],
                    'status'     => $language['status'],
                    'is_default' => $language['is_default'],
                    'flag_path'  => $language['flag_path'],
                ]
            );
        }
    }
}
