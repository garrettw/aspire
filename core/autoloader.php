<?php
define('NAMESPACE_SEPARATOR', '\\');

/**
 * from https://gist.github.com/garrettw/23bde8f9bff6c5a27374
 * This is my version of an autoloader class that can use multiple cascading
 * loader classes, pass specific include paths to each one, and specify a
 * namespace that each loader applies to.
 * 
 * The idea came from: http://r.je/php-psr-0-pretty-shortsighted-really.html
 * Combined with: https://gist.github.com/jwage/221634
 * ... and incorporating some of the suggestions in the comments on that gist.
 * 
 * Here's an example autoload.json that registerLibrary() would use.
 * 
 * {
 *     "PSR0AutoloadRule": {
 *         "file": "psr0",
 *         "includePath": "",
 *         "namespace": ""
 *     }
 * }
 * 
 */

class Autoloader {
    private $rules = [];
    
    /**
     * When an instance of this class is created, its load method is
     * auto-registered with PHP.
     */
    public function __construct() {
        spl_autoload_register([$this, 'load']);
    }
    
    public function __destruct() {
        spl_autoload_unregister([$this, 'load']);
    }
    
    /**
     * Once you create an Autoloader instance, call this on a directory to
     * pull in the necessary rules for that library
     */
    public function registerLibrary($dir) {
        $autoloaders = json_decode(
                file_get_contents($dir . DIRECTORY_SEPARATOR . 'autoload.json')
        );
        foreach ($autoloaders as $name => $params) {
            require_once $dir . DIRECTORY_SEPARATOR . $params['file']
                . '.autoloadrule.php'
            ;
            $this->addRule(new $name($params['namespace'], $params['includePath']));
        }
    }
    
    /**
     * Or call this directly on a rule object of your creation to add it
     * programmatically
     */
    public function addRule(AutoloadRule $autoloadRule) {
        $this->rules[] = $rule;
    }
    
    public function load($className) {
        foreach ($this->rules as $rule) {
            if ($rule->loadClass($className)) {
                return;
            }
        }
    }
}

interface AutoloadRule {
    public function loadClass($className);
}

/**
 * For convenience, here's a PSR-0 rule you can load using addRule() instead of 
 * registerLibrary() if you want. Like so:
 * $loader->addRule(new PSR0AutoloadRule('namespace', 'include/path'));
 */
class PSR0AutoloadRule implements AutoloadRule {
    private $namespace;
    private $includePath;
    
    public function __construct($ns, $includePath) {
        $this->namespace = strtolower($ns);
        $this->includePath = $includePath;
    }
    
    public function loadClass($className) {
        // remove any leading backslash or underscore and convert to lowercase
        $className = strtolower(ltrim($className,'\\_'));
        
        // if this loader's namespace doesn't match that of the class to load
        if (!empty($this->namespace)
            && $this->namespace . NAMESPACE_SEPARATOR != substr(
                $className, 0, strlen($this->namespace . NAMESPACE_SEPARATOR)
            )
        ) {
            // then stop
            return false;
        }

        $fileName = $this->includePath . DIRECTORY_SEPARATOR;

        // Are there namespaces in the classname at all?
        if ($lastNsPos = strrpos($className, NAMESPACE_SEPARATOR)) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName  = strtr($namespace, NAMESPACE_SEPARATOR, DIRECTORY_SEPARATOR)
                        .DIRECTORY_SEPARATOR;
        }
        
        $fileName .= $className . '.php';
        $filePath = stream_resolve_include_path($fileName);
        if ($filePath) {
            require $filePath;
        }
        return $filePath != false;
    }
}
