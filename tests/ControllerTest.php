<?php
declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Prim\Controller;
use Tests\Mocks\Container;

class ControllerTest extends TestCase
{
    public function testConstruct()
    {
        define('ROOT', '');
        define('DEBUG', true);
        define('DB_ENABLE', false);

        $container = new Container();
        $view = $container->getView();
        $controller = new Controller($view, $container);

        $this->assertEquals('Prim', $controller->projectNamespace);
        $this->assertEquals('', $controller->packNamespace);

        return $controller;
    }

    /**
     * @depends testConstruct
     */
    public function testgetNamespace($controller)
    {
        $controller->getNamespace('\\Project\\TestPack\\Controller\\Test');

        $this->assertEquals('Project', $controller->projectNamespace);
        $this->assertEquals('TestPack', $controller->packNamespace);

        $controller->getNamespace('\\TestPack\\Controller\\Test');

        $this->assertEquals('', $controller->projectNamespace);
        $this->assertEquals('TestPack', $controller->packNamespace);
    }
}