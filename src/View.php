<?php
namespace Prim;

class View implements ViewInterface
{
    public $root = ROOT;

    protected $container;

    protected $templateName = 'design';
    protected $templatePack = 'BasePack';
    protected $pack = '';

    protected $vars = [];

    protected $sections = [];
    protected $section = 'default';
    protected $sectionPush = false;

    /**
     * @param Container $container
     */
    public function __construct($container)
    {
        $this->container = $container;

        $class_methods = get_class_methods($this);

        /*
         * All methods that start by build get automatically executed when the object is instantiated
         * */
        foreach ($class_methods as $method_name) {
            if (strpos($method_name, 'build') !== false) {
                $this->$method_name();
            }
        }
    }

    function setPack(string $pack) : void
    {
        $this->pack = $pack;
    }

    function setTemplate(string $name, string $pack) : void
    {
        $this->templateName = $name;
        $this->templatePack = $pack;
    }

    function design(string $view, string $packDirectory = '', array $vars = []) : void
    {
        $this->renderTemplate($view, $packDirectory, $vars, true, true);
    }

    function render(string $view, string $packDirectory = '', array $vars = [], bool $template = true) : void
    {
        $this->renderTemplate($view, $packDirectory, $vars, $template, false);
    }

    function escape(string $string) : string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE);
    }

    function registerFunction(string $name, \Closure $closure)
    {
        if(!isset($this->vars[$name])) $this->vars[$name] = $closure;
    }

    function vars(array $vars = []) : array
    {
        if(!empty($vars)) {
            $this->vars = $vars + $this->vars;
        }

        return $this->vars;
    }

    function renderTemplate(string $view, string $packDirectory = '', array $vars = [], bool $template = true, bool $default = false) : void
    {
        $this->vars($vars);
        unset($vars);
        extract($this->vars);

        if($packDirectory == '') {
            $packDirectory = $this->pack;
        }

        $this->registerFunction('e', function(string $string) {
            return $this->escape($string);
        });

        $level = ob_get_level();

        try {
            if($default) $this->start('default');

            include($this->getViewFilePath($packDirectory, $view));

            if($default) $this->end();
        } catch (\Exception $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }

        if ($template) {
            include($this->getViewFilePath($this->templatePack, "_templates/{$this->templateName}"));
        }
    }

    protected function getViewFilePath(string $pack, string $view) : string
    {
        $localViewFile = "{$this->root}src/$pack/view/$view.php";

        if(file_exists($localViewFile)) {
            return $localViewFile;
        }

        if($vendorPath = $this->container->getPackList()->getVendorPath($pack)) {
            $vendorFile = ROOT . "$vendorPath/view/$view.php";

            if(file_exists($vendorFile)) {
                return $vendorFile;
            }
        }

        throw new \Exception("Can't find view $view in $pack");
    }

    function push(string $section)
    {
        $this->start($section);
        $this->sectionPush = true;
    }

    function start(string $section)
    {
        $this->section = $section;
        ob_start();
    }

    function end()
    {
        if($this->sectionPush) $this->sections[$this->section] .= ob_get_clean();
        else $this->sections[$this->section] = ob_get_clean();

        $this->sectionPush = false;
        $this->section = 'default';
    }

    /**
     * Return the content of a section
     */
    function section(string $section) : string
    {
        return isset($this->sections[$section])? $this->sections[$section]: '';
    }

    public function insert(string $name, string $pack = '', array $vars = []) : void
    {
        echo $this->renderTemplate($name, $pack, $vars, false, false);
    }

    function addVar(string $name, $var) : void
    {
        $this->vars[$name] = $var;
    }

    function addVars(array $vars) : void
    {
        foreach($vars as $var) {
            $this->addVar($var[0], $var[1]);
        }
    }

    function fileHash(string $name) : string
    {
        return "$name?v=" . hash_file('fnv1a32', "{$this->root}public$name");
    }

    function fileCache(string $name) : string
    {
        return "$name?v=" . filemtime( "{$this->root}public/$name");
    }
}
