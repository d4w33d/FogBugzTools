<?php

class Command
{

    private static $args;
    private static $options;

    public static function initialize()
    {
        list(self::$args, self::$options) = self::parseOptions(self::args());
    }

    public static function dispatch()
    {
        if (count($args = self::getArguments()) === 0)
        {
            self::error('No action given.');
        }

        $scriptName = Utils::capitalize(array_shift($args));
        $filename = SCRIPTS_DIR . DS . $scriptName . '.php';
        if (!is_file($filename))
        {
            self::error('Script <' . $scriptName . '> does not exists.');
        }

        require_once $filename;
        $className = $scriptName . 'Command';

        $cmd = new $className();

        $methodName = 'execute';
        if (!empty($args))
        {
            $methodName = $methodName . Utils::capitalize(array_shift($args));
        }
        if (!method_exists($cmd, $methodName))
        {
            self::error('Method <' . $scriptName . '::' . $methodName . '> does not exists');
        }

        $cmd->$methodName($args, self::getOptions());
    }

    public static function args()
    {
        $args = $_SERVER['argv'];
        array_shift($args);
        return $args;
    }

    public static function arg($index)
    {
        if (count($args = self::args()) - 1 >= $index) {
            return $args[$index];
        }
    }

    public static function getArguments()
    {
        return self::$args;
    }

    public static function getOptions()
    {
        return self::$options;
    }

    /**
     * Parses the array and returns a tuple containing the arguments and the options
     *
     * From: ConsoleKit
     *
     * @param array $argv
     * @return array
     */
    protected static function parseOptions(array $argv)
    {
        $args = array();
        $options = array();

        for ($i = 0, $c = count($argv); $i < $c; $i++) {
            $arg = $argv[$i];
            if ($arg === '--') {
                $args[] = implode(' ', array_slice($argv, $i + 1));
                break;
            }
            if (substr($arg, 0, 2) === '--') {
                $key = substr($arg, 2);
                $value = true;
                if (($sep = strpos($arg, '=')) !== false) {
                    $key = substr($arg, 2, $sep - 2);
                    $value = substr($arg, $sep + 1);
                }
                if (array_key_exists($key, $options)) {
                    if (!is_array($options[$key])) {
                        $options[$key] = array($options[$key]);
                    }
                    $options[$key][] = $value;
                } else {
                    $options[$key] = $value;
                }
            } else if (substr($arg, 0, 1) === '-') {
                foreach (str_split(substr($arg, 1)) as $key) {
                    $options[$key] = true;
                }
            } else {
                $args[] = $arg;
            }
        }

        return array($args, $options);
    }

    public function __construct()
    {
        return;
    }

    public function write($str)
    {
        fwrite(STDOUT, (string) $str);
    }

    public function writeln($str = '')
    {
        self::write($str);
        self::write("\n");
    }

    public function error($str)
    {
        self::writeln('Error: ' . $str);
        exit;
    }

    public function success($str)
    {
        self::writeln($str);
        exit;
    }

    public function prompt($str)
    {
        fwrite(STDOUT, $str);
        return trim(fgets(STDIN));
    }

    public function abort()
    {
        self::writeln();
        exit;
    }

}
