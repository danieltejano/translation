<?php

namespace Database\Seeders;

use App\TranslationParserTrait;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Translation;

class TranslationSeeder extends Seeder
{

    use TranslationParserTrait;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json_contents = json_decode(file_get_contents(base_path('/database/seeders/sample data/en_US.json')),true);
        $translations = $this->parseFile($json_contents, 'web' ,'en');

        foreach($translations as $translation){
            Translation::create($translation);
        }
    }
}
