<?php namespace Vanchelo\LaravelConsole\Http\Controllers;

use Illuminate\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\View\Factory as View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Vanchelo\LaravelConsole\Console;

class ConsoleController extends Controller
{

    /**
     * @var View
     */
    private $view;

    /**
     * @var Console
     */
    private $console;

    /**
     * @var Container
     */
    private $container;

    function __construct(Container $container, Console $console, View $view)
    {
        $this->view = $view;
        $this->console = $console;
        $this->container = $container;

        if ($this->container->bound('debugbar')) {
            $this->container['debugbar']->disable();
        }

        $this->container->singleton(
            ExceptionHandler::class,
            Handler::class
        );
    }

    public function index()
    {
        return $this->view->make('laravel-console::console');
    }

    /**
     * @param Request $request
     *
     * @return array|string
     */
    public function execute(Request $request)
    {
        $code = $request->input('code');

        $this->console->execute($code);

        if ( ! $this->console->isError()) {
            $profile = $this->console->getProfile();

            return $profile;
        }

        return '';
    }
}
