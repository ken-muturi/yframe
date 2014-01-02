<?php
/*
 * Rand_Model - a model used to generate random stuff -> digits, alphanumerics, passwords etc
 * */

class Rand
{
    public static function alphanum($xters = 5)
    {
        return substr(base_convert(hash("ripemd160", mt_rand()), 16, 36), 7, $xters);    
    }
    
    public static function hex($xters = 6)
    {
        return substr(hash("ripemd160", mt_rand()), 0, $xters);    
    }    
}
