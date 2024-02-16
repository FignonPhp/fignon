<?php

declare(strict_types=1);

namespace Fignon\Helper;

class UtilitiesHelper
{
    public static function setNestedProperty(string $name, mixed $value): array
    {
        $array = [];
        $keys = explode('.', $name);
        $topKey = array_shift($keys);
        $nestedArray = &$array;

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($nestedArray[$key]) || !is_array($nestedArray[$key])) {
                $nestedArray[$key] = [];
            }

            $nestedArray = &$nestedArray[$key];
        }

        $nestedArray[array_shift($keys)] = $value;

        // Return the top key and the modified array
        return [$topKey, $array];
    }


    public static function toConvertToCamelCase(string $string): string
    {
        $string = preg_replace('/[^a-zA-Z0-9.]+/', ' ', $string);
        $string = ucwords($string);
        $string = str_replace([' ', '_', '.'], ['', '', ''], $string);
        $string = lcfirst($string);

        return $string;
    }
}
