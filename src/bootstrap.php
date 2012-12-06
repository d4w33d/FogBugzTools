<?php

define('DS', DIRECTORY_SEPARATOR);
define('ROOT_DIR', dirname(dirname(__file__)));
define('CONFIG_DIR', ROOT_DIR . DS . 'config');
define('SRC_DIR', ROOT_DIR . DS . 'src');
define('CODE_DIR', SRC_DIR . DS . 'code');
define('SCRIPTS_DIR', SRC_DIR . DS . 'scripts');

set_include_path(get_include_path()
    . PATH_SEPARATOR . CODE_DIR
    . PATH_SEPARATOR . CONFIG_DIR);

spl_autoload_register(function($className) {
    $className = str_replace('\\', DS, $className) . '.php';
    require_once $className;
});

Command::initialize();
