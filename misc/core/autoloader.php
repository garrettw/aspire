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
 *         "includePath": "",
 *         "namespace": ""
 *     }
 * }
 * 
 * Here, the class "PSR0AutoloadRule" would be loaded from psr0autoloadrule.php
 * which must be in the same directory as the autoload.json file.
 * 
 * The "includePath" element gives the rule a base path that all of its
 * includes will share. If empty, it defaults to the same directory
 * as autoload.json.
 * 
 * "namespace", if specified, should limit the rule to only apply to classes
 * under a certain namespace (or potentially a sub-namespace of the one
 * specified, depending on the implementation in the rule class).
 */

class Autoloader
{
    private $rules = [];
    
    /**
     * When an instance of this class is created, its load method is
     * auto-registered with PHP.
     */
    public function __construct()
    {
        spl_autoload_register([$this, 'load']);
    }
    
    public function __destruct()
    {
        spl_autoload_unregister([$this, 'load']);
    }
    
    /**
     * Once you create an Autoloader instance, call this on a directory to
     * pull in the necessary rules for that library
     */
    public function registerLibrary($dir)
    {
        $jsonpath = $dir . DIRECTORY_SEPARATOR . 'autoload.json';
        if (!file_exists($jsonpath)):
            return false;
        endif;
        
        $autoloaders = json_decode(file_get_contents($jsonpath), true);
        
        if ($autoloaders === null):
            return false;
        endif;
        
        foreach ($autoloaders as $name => $params):
            $filepath = $dir . DIRECTORY_SEPARATOR . strtolower($name) . '.php';
            
            if (!(isset($params['namespace'])
                    && isset($params['includePath'])
                    && file_exists($filepath)
                )
            ):
                return false;
            endif;
            
            require_once $filepath;
            $this->addRule(
                new $name(
                    $params['namespace'], 
                    $dir . DIRECTORY_SEPARATOR . $params['includePath']
                )
            );
        endforeach;
        return true;
    }
    
    /**
     * Or call this directly on a rule object of your creation to add it
     * programmatically
     */
    public function addRule(AutoloadRule $rule)
    {
        $this->rules[] = $rule;
    }
    
    public function load($className)
    {
        foreach ($this->rules as $rule):
            if ($rule->loadClass($className)):
                return;
            endif;
        endforeach;
    }
}

interface AutoloadRule
{
    public function loadClass($className);
}

/**
 * For convenience, here's a modified PSR-0 rule you can load using addRule() 
 * instead of registerLibrary() if you want. Like so:
 * $loader->addRule(new PSR0AutoloadRule('namespace', 'include/path'));
 */
class PSR0AutoloadRule implements AutoloadRule
{
    private $namespace;
    private $includePath;
    
    public function __construct($ns, $includePath)
    {
        $this->namespace = $ns;
        $this->includePath = $includePath;
    }
    
    public function loadClass($className)
    {
        $matchns = strtolower($this->namespace);
        // remove any leading backslash or underscore and convert to lowercase
        $className = strtolower(ltrim($className,'\\_'));
        
        // if this loader's namespace doesn't match that of the class to load
        if (!empty($matchns)
            && $matchns . NAMESPACE_SEPARATOR != substr(
                $className, 0, strlen($matchns . NAMESPACE_SEPARATOR)
            )
        ):
            // then stop
            return false;
        endif;

        $fileName = $this->includePath . DIRECTORY_SEPARATOR;

        // Are there namespaces in the classname at all?
        if ($lastNsPos = strrpos($className, NAMESPACE_SEPARATOR)):
            $classns = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName .= strtr($classns, NAMESPACE_SEPARATOR, DIRECTORY_SEPARATOR)
                        .DIRECTORY_SEPARATOR;
        endif;
        
        $fileName .= $className . '.php';
        $filePath = stream_resolve_include_path($fileName);
        if ($filePath):
            require_once $filePath;
        endif;
        return $filePath != false;
    }
}

/**
 * And here's a rule that wraps your own custom callback rewriting function.
 * This can be implemented in a way that is PSR-4-compatible. Load it like this:
 * $loader->addRule(new RewriterAutoloadRule('namespace', 'include/path',
 *     function ($className) {
 *         return preg_replace('/pattern/', '/replacement/', $className);
 *     }
 * ));
 */
class RewriterAutoloadRule implements AutoloadRule
{
    private $namespace;
    private $includePath;
    private $pathRewriter;
    
    public function __construct($ns, $includePath, callable $pr)
    {
        $this->namespace = $ns;
        $this->includePath = $includePath;
        $this->pathRewriter = $pr;
    }
    
    public function loadClass($className)
    {
        // remove leading backslash
        $className = ltrim($className,'\\');
        
        // if this loader's namespace doesn't match that of the class to load
        if (!empty($this->namespace)
            && $this->namespace . NAMESPACE_SEPARATOR != substr(
                $className, 0, strlen($this->namespace . NAMESPACE_SEPARATOR)
            )
        ):
            // then stop
            return false;
        endif;

        $fileName = $this->includePath . DIRECTORY_SEPARATOR
                   .call_user_func($this->pathRewriter, $className) . '.php';
        
        $filePath = stream_resolve_include_path($fileName);
        if ($filePath):
            require_once $filePath;
        endif;
        return $filePath != false;
    }
}
