<?php

namespace Database\Seeders;

use App\TranslationParserTrait;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Translation;
use Illuminate\Support\Facades\File;

class TranslationSeeder extends Seeder
{

    use TranslationParserTrait;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $import_folder = database_path('seeders/sample data');

        if (File::isDirectory($import_folder)) {
            $localization_files = File::files($import_folder);

            foreach ($localization_files as $localization) {
                $locale = explode('.', $localization->getFilename())[0];
                $locale_contents = $localization->getContents();

                $this->command->info("\tImporting $locale");

                $json_contents = json_decode(
                    $locale_contents,
                    true
                );
                $translations = $this->parseFile($json_contents, 'web', $locale);

                // dd($translations);

                Translation::insert($translations);
            }
        }
    }
}
