<?php
/**
 * Created by PhpStorm.
 * User: Truman
 * Date: 12.10.2017
 * Time: 11:26
 */


function ids_deactivate() {
    $date = '['. date('Y-m-d H:m:s') . ']';
    error_log($date . " -> Плагин деактивирован\r\n", 3, dirname(__FILE__).'/wp-idea-errors.log');
}