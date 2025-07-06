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
        $this->namespace = $ns;
        $this->includePath = $includePath;
    }
    
    public function loadClass($className)
    {
        $nssep = NAMESPACE_SEPARATOR;
        // remove leading backslash & convert to lowercase
        $className = strtolower(ltrim($className,'\\'));
        
        // if this loader's namespace doesn't match that of the class to load,
        // either via regex or simple equivalence
        if (!(empty($this->namespace)
                || @preg_match($this->namespace, $className)
                || $this->namespace . $nssep
                    == substr($className, 0, strlen($this->namespace . $nssep))
            )
        ):
            // then stop
            return false;
        endif;
        
        $fileName = $this->includePath . DIRECTORY_SEPARATOR;
        
        // Are there namespaces in the classname at all?
        if ($lastNsPos = strrpos($className, $nssep)):
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName .= strtr($namespace, $nssep, DIRECTORY_SEPARATOR)
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
