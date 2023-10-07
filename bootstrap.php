<?php

use Rocketman\Polyfill\mb_sprintf as p;

if (!function_exists('mb_sprintf')) {
    function mb_sprintf($format, ...$argv) { return p::mb_vsprintf($format, $argv); }
}
if (!function_exists('mb_vsprintf')) {
    function mb_vsprintf($format, $argv, $encoding=null) { return p::mb_vsprintf($format, $argv, $encoding); }
}
