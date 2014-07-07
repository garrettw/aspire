<?php class TwAutoloadRule implements AutoloadRule
{
    private $includePath;
    
    public function __construct($ns, $includePath) {
        $this->includePath = $includePath;
    }
    
    public function loadClass($className) {
        $fileName = $this->includePath . DIRECTORY_SEPARATOR 
                   .strtolower($className) . '.php';
        $filePath = stream_resolve_include_path($fileName);
        if ($filePath) {
            require $filePath;
        }
        return $filePath != false;
    }
}
