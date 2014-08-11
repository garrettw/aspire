<?php
/**
 * Talkwork's autoloader is basically PSR-0 -- only diff is lines 24 and 46
 * 
 * @version    0.1
 * @author     Garrett Whitehorn
 * @package    Talkwork
 */

class TwAutoloadRule implements AutoloadRule
{
    private $namespace;
    private $includePath;
    
    public function __construct($ns, $includePath)
    {
        $this->namespace = strtolower($ns);
        $this->includePath = $includePath;
    }
    
    public function loadClass($className)
    {
        // remove leading backslash & convert to lowercase
        $className = strtolower(ltrim($className,'\\'));
        
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
            $fileName .= strtr($namespace, NAMESPACE_SEPARATOR, DIRECTORY_SEPARATOR)
                        .DIRECTORY_SEPARATOR;
        }
        
        $fileName .= $className . '.php';
        $filePath = stream_resolve_include_path($fileName);
        if ($filePath) {
            require_once $filePath;
        }
        return $filePath != false;
    }
}
