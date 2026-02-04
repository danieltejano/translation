<?php

namespace App;

trait TranslationParserTrait
{
    private function parseFile(array $data, string $platform, string $lang, ?string $grouping = null) : array
    {
        $translations = [];

        foreach($data as $key => $value){
            if(is_array($value)){
                $group = $grouping ? "{$grouping}.{$key}" : $key;
                $translations = array_merge($translations, $this->parseFile($value, $lang, $group));
            }else{
                $translations[] = [
                    'lang' => $lang,
                    'platform' => json_encode([$platform]),
                    'key' => $grouping ?? $key,
                    'value' => (string) $value
                ];
            }
        }

        return $translations;
    }
}
