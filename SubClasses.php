<?php namespace IET_OU\SubClasses;

/**
 * SubClasses class.
 *
 * @copyright 2015 The Open University.
 * @author  N.D.Freear, 23 May 2015.
 * @link    https://gist.github.com/nfreear/72a3a62b8ac810ea4c49
 */

use \IET_OU\SubClasses\OffsetIterator;

class SubClasses extends OffsetIterator
{
    /**
     * How many PHP "core" classes should we skip? (performance)
     *
     * $  php -r 'echo count(get_declared_classes());'
     * Result: 139, Mac/PHP 5.4.38; 125, Ar**s/PHP 5.3.3; 120, Pan**s/RHE 6/PHP 5.5.26;
     */
    const PHP_CORE_OFFSET = 120;

    const PLUGIN_INTERFACE = '\\IET_OU\\SubClasses\\PluginInterface';
    const REGISTER_FN = 'registerPlugin';

    public static $verbose = false;
    protected $classes = array();


    public function __construct($offset = self::PHP_CORE_OFFSET)
    {
        $this->discoverClasses();
        //parent::__construct(get_declared_classes(), $offset);
    }

    /**
    * @param string $base_class A parent class or interface.
    * @param bool $callback Optionally, don't call the register callback to construct the results array.
    * @param bool $use_constructor Optionally, get a classinstance via constructor - potentially UNSAFE!
    * @return array Array of result classes, optionally keyed.
    */
    public function match($base_class, $callback = true, $use_constructor = false)
    {
        $results = array();
        //foreach ($this as $class) {
        foreach ($this->classes as $class) {
            if (is_subclass_of($class, $base_class)) {
                if ($callback) {
                    if ($use_constructor) {
                        $obj = new $class ();
                    } else {
                        $reflect = new \ReflectionClass($class);
                        $obj = $reflect->newInstanceWithoutConstructor();
                    }
                    $obj->{ self::REGISTER_FN }($results);
                    //Was: $results[ $class::{ $callback }() ] = $class;
                } else {
                    $results[] = $class;
                }
            }
        }
        return $results;
    }


    public function get_oembed_providers()
    {
        return $this->match('IET_OU\\Open_Media_Player\\Oembed_Provider');
    }


    protected function getPsr4Paths()
    {
        // Mode: as a vendor package.
        $path = __DIR__ .'/../../../../composer/autoload_psr4.php';
        $psr4 = @include $path;
        if (!$psr4) {
            // Mode: self-testing?!
            $path = __DIR__ .'/../../vendor/composer/autoload_psr4.php';
            $psr4 = require $path;
        }
        $flat_paths = call_user_func_array('array_merge', $psr4);
        return $psr4;
    }


    protected function discoverClasses()
    {
        $psr4 = $this->getPsr4Paths();

        foreach ($psr4 as $namespace => $paths) {
            $glob = sprintf('{%s/*.php}', implode('/*.php,', $paths));
            //$glob = $paths[ 0 ] .'/*.php';
            $this->debug('Glob: '. $glob);
            $files = glob($glob, GLOB_BRACE | GLOB_MARK);
            foreach ($files as $path) {
                $name = basename($path, '.php');
                $class = '\\' . $namespace . $name;
                //if ('MergePlugin' == $name || 'Suggest' == $name) continue;
                try {
                    $reflect = new \ReflectionClass($class);
                    if ($reflect->implementsInterface(self::PLUGIN_INTERFACE)
                    && $reflect->isInstantiable()) {
                        $this->classes[] = $class; //ltrim($class, '\\');
                    }
                } catch (\Exception $e) {
                    $this->debug('Warning! '. $e->getMessage());
                }
            }
        }
        $this->debug('OK, discovered classes: '. count($this->classes));
    }

    protected function debug($message)
    {
        if (self::$verbose && is_string($message)) {
            echo '>> '. $message . PHP_EOL;
        }
    }
}
