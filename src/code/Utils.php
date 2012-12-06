<?php

class Utils
{

    public static function capitalize($str)
    {
        $str = str_replace(array('_', '-'), ' ', $str);
        $str = str_replace(' ', '', ucwords($str));
        return $str;
    }

}
