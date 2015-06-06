<?php

namespace Vanchelo\LaravelConsole;

use Exception;
use Illuminate\Container\Container;

class Console
{

    /**
     * Array with error code => string pairs.
     *
     * Used to convert error codes into human readable strings.
     *
     * @var array
     */
    public $errorMap = [
        E_ERROR => 'E_ERROR',
        E_WARNING => 'E_WARNING',
        E_PARSE => 'E_PARSE',
        E_NOTICE => 'E_NOTICE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_CORE_WARNING => 'E_CORE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR => 'E_USER_ERROR',
        E_USER_WARNING => 'E_USER_WARNING',
        E_USER_NOTICE => 'E_USER_NOTICE',
        E_STRICT => 'E_STRICT',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        E_ALL => 'E_ALL',
    ];

    /**
     * Execution profile.
     *
     * @var array
     */
    public $profile = [
        'queries' => [],
        'memory' => 0,
        'memory_peak' => 0,
        'time' => 0,
        'time_queries' => 0,
        'time_total' => 0,
        'output' => '',
        'output_size' => 0,
        'error' => false
    ];

    private $resourcesPath;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var bool
     */
    private $error = false;

    function __construct(Container $app)
    {
        $this->container = $app;
    }

    /**
     * Adds one or multiple fields into profile.
     *
     * @param string $property Property name, or an array of name => value pairs.
     * @param mixed  $value    Property value.
     */
    public function addProfile($property, $value = null)
    {
        if (is_array($property)) {
            foreach ($property as $key => $value) {
                $this->addProfile($key, $value);
            }

            return;
        }

        // Normalize properties
        $normalizerName = 'normalize' . ucfirst($property);

        if (method_exists($this, $normalizerName)) {
            $value = call_user_func([__CLASS__, $normalizerName], $value);
        }

        $this->profile[$property] = $value;
    }

    /**
     * Returns current profile.
     *
     * @return array
     */
    public function getProfile()
    {
        // Total execution time by queries
        $time_queries = 0;

        foreach ($this->profile['queries'] as $query) {
            $time_queries += $query['time'];
        }

        // Extend the profile with current data
        $this->addProfile([
            'memory' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'time_queries' => round($time_queries),
            'time_total' => round((microtime(true) - LARAVEL_START) * 1000),
        ]);

        return $this->profile;
    }

    /**
     * Executes a code and returns current profile.
     *
     * @param  string $code
     *
     * @return array
     */
    public function execute($code)
    {
        // Execute the code
        ob_start();
        $consoleExecuteStart = microtime(true);
        $estatus = @eval($code);
        $consoleExecuteEnd = microtime(true);
        $output = ob_get_contents();
        ob_end_clean();

        // When error occurred, add it to profile.
        if ($estatus !== null && trim($code) !== '') {
            $this->error = true;
        }

        // Extend the profile
        $this->addProfile([
            'time' => round(($consoleExecuteEnd - $consoleExecuteStart) * 1000),
            'output' => $output,
            'output_size' => strlen($output)
        ]);
    }

    /**
     * Processes a Laravel query event to profile executed queries.
     *
     * @param  string $sql
     * @param  array  $bindings
     * @param  int    $time
     */
    public function query($sql, $bindings, $time)
    {
        foreach ($bindings as $binding) {
            // Sometimes, object $binding is passed, and needs to be stringified
            if (is_object($binding)) {
                $className = get_class($binding);

                switch ($className) {
                    case 'DateTime':
                        $binding = $binding->format('Y-m-d H:i:s e');
                        break;

                    default:
                        $binding = '(object)' . $className;
                }
            }

            $binding = \DB::connection()->getPdo()->quote($binding);

            $sql = preg_replace('/\?/', $binding, $sql, 1);
            $sql = htmlspecialchars(htmlspecialchars_decode($sql));
        }

        $this->profile['queries'][] = [
            'query' => $sql,
            'time' => $time
        ];
    }

    /**
     * Attaches Laravel event listeners.
     */
    public function attach()
    {
        $this->container['db']->listen(function ($sql, $bindings, $time) {
            $this->query($sql, $bindings, $time);
        });
    }

    /**
     * Normalizes error profile
     *
     * @param Exception $e Error object
     *
     * @return array Normalized error array
     */
    public function normalizeError(Exception $e)
    {
        $output = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];

        // Set human readable error type
        if (isset($this->errorMap[$e->getCode()])) {
            $output['type'] = $this->errorMap[$e->getCode()];
        }

        // Validate and return the error
        if (isset($output['type'], $output['message'], $output['file'], $output['line'])) {
            return $output;
        } else {
            return $this->profile['error'];
        }
    }

    /**
     * @param null $path
     *
     * @return mixed
     */
    public function getResourcesPath($path = null)
    {
        return $path ? $this->resourcesPath . trim($path) : $this->resourcesPath;
    }

    /**
     * @param mixed $resourcesPath
     */
    public function setResourcesPath($resourcesPath)
    {
        $this->resourcesPath = $resourcesPath;
    }

    public function isError()
    {
        return $this->error;
    }
}
