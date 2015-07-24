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

    protected static $skip_me = array('\\Wikimedia\\Composer\\MergePlugin', '\\Nfreear\\Composer\\Suggest');


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
                    try {
                        if ($use_constructor) {
                            $obj = new $class ();
                        } else {
                            $reflect = new \ReflectionClass($class);
                            if ($reflect->isInstantiable()) {
                                $obj = $reflect->newInstanceWithoutConstructor();
                            }
                        }
                        if ($obj) {
                            $obj->{ self::REGISTER_FN }($results);
                        }
                    } catch (\ReflectionException $e) {
                        $this->debug('Warning! (RF) '. $e->getMessage());
                    } catch (\Exception $e) {
                        $this->debug('Warning! '. $e->getMessage());
                    }
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

    public function get_player_themes()
    {
        return $this->match('IET_OU\\Open_Media_Player\\Media_Player_Theme');
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
        return $psr4;
    }


    protected function discoverClasses()
    {
        $psr4_paths = $this->getPsr4Paths();

        foreach ($psr4_paths as $namespace => $paths) {
            //$glob = sprintf('{%s/*.php}', implode('/*.php,', $paths));  // Not on Windows!
            foreach ($paths as $file_path) {
                $glob = $file_path .'/*.php';
                $this->debug('Glob: '. $glob);
                $files = glob($glob, GLOB_MARK);
                foreach ($files as $path) {
                    $name = basename($path, '.php');
                    $class = '\\' . $namespace . $name;

                    // A hack :(!
                    if (in_array($class, self::$skip_me)) {
                        $this->debug("Skipping class '$class'");
                        continue;
                    }

                    $this->debug($class, $path);
                    try {
                        if (class_exists($class)
                            && is_subclass_of($class, self::PLUGIN_INTERFACE)) {
                        /*$reflect = new \ReflectionClass($class);
                        if ($reflect->implementsInterface(self::PLUGIN_INTERFACE)
                            && $reflect->isInstantiable()) {*/
                            $this->classes[] = $class;
                        }
                    } catch (\Exception $e) {
                        $this->debug('Warning! '. $e->getMessage());
                    }
                }
            }
        }
        $count = count($this->classes);
        $this->debug('OK, discovered classes: '. $count, $this->classes);
    }

    protected function debug($message)
    {
        if (self::$verbose && is_string($message)) {
            echo '>> '. $message . PHP_EOL;
        }
    }
}
