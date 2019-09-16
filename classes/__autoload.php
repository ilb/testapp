<?php

/**
 * $Id: __autoload.php 326 2013-09-30 20:21:47Z dab $
 *
 * загрузчик классов.
 *
 * имена классов и расположение в файловой системе аналогично Zend Framework
 * класс Category1_Subcategory2_Class3 распологается в файле Category1/Subcategory2/Class3.php
 *
 *  @author dab@bystrobank.ru
 */
spl_autoload_register(function ($c) {
    $f = str_replace("_", DIRECTORY_SEPARATOR, $c) . ".php";
    //проверяем сами - чтоб отловить место где произошла ошибка (стандартное сообщение неинформатвно)
    $r = null;
    foreach (explode(PATH_SEPARATOR, get_include_path()) as $p) {
        if (file_exists($p . DIRECTORY_SEPARATOR . $f)) {
            // файл класса существует
            $r = $p . DIRECTORY_SEPARATOR . $f;
            break;
        }
    }
    //из __autoload нельзя выбросить исключение, поэтому зовем обработчик ошибок явно
    if (!$r) {
        uncaughtFatalErrorExceptionHandler(
                new FatalErrorException("File '" . $f . "' not found in '" . get_include_path() . "'"));
    }
    if (!is_readable($r)) {
        uncaughtFatalErrorExceptionHandler(
                new FatalErrorException("File '" . $r . "' not readable"));
    }
    require_once($r);
    if (!(class_exists($c, false) || interface_exists($c, false))) {
        //проверяем загрузился ли класс
        uncaughtFatalErrorExceptionHandler(
                new FatalErrorException("Class '" . $c . "' not found in '" . $r . "'"));
    }
});

