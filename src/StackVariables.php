<?php


namespace Rokka\Utils;

class StackVariables
{
    public static function hasSpecialChars(string $value): bool
    {
        return  (preg_match('#[$/\-\#%&?;\\\]#', $value) > 0);
    }
}
