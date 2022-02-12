<?php

namespace SearchEngine\Services;

/**
 * Class CallBackHandler
 */
class CallBackHandler
{
    /**
     * @var string|array|callable
     */
    protected $callback;

    /**
     * @var array
     */
    protected $metadata;

    /**
     * @param  string|array|object|callable $callback PHP callback
     * @param  array $metadata Callback metadata
     * @param $isCallableVerified
     */
    public function __construct($callback, array $metadata = array(), $isCallableVerified = false)
    {
        $this->metadata = $metadata;
        $this->registerCallback($callback, $isCallableVerified);
    }

    /**
     * @param $callback
     * @param $isCallableVerified
     * @throws \InvalidArgumentException
     */
    protected function registerCallback($callback, $isCallableVerified)
    {
        if ($isCallableVerified) {
            $this->callback = $callback;
        } elseif (is_callable($callback)) {
            $this->callback = $callback;
        } /** TODO This can be removed if PHP_VERSION >= 5.3. */
        elseif (is_callable(array($callback, '__invoke'))) {
            $this->callback = $callback;
        } else {
            throw new \InvalidArgumentException('Invalid callback provided; not callable');
        }
    }

    /**
     * @return callable
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @param  array $args Arguments to pass to callback
     * @return mixed
     */
    public function call(array $args = array())
    {
        $callback = $this->getCallback();

        $argCount = count($args);

        /** TODO: performance tweak; use call_user_func() until > 3 arguments reached */
        switch ($argCount) {
            case 0:
                return call_user_func($callback);
            case 1:
                return call_user_func($callback, array_shift($args));
            case 2:
                $arg1 = array_shift($args);
                $arg2 = array_shift($args);
                return call_user_func($callback, $arg1, $arg2);
            case 3:
                $arg1 = array_shift($args);
                $arg2 = array_shift($args);
                $arg3 = array_shift($args);
                return call_user_func($callback, $arg1, $arg2, $arg3);
            default:
                return call_user_func_array($callback, $args);
        }
    }

    /**
     * @param $callBack
     * @param array $args
     * @param bool|false $isCallableVerified
     * @return mixed
     */
    public static function optimizedCallUserFunction($callBack, $args = array(), $isCallableVerified = false)
    {
        $callBackHandler = new self($callBack, $args, $isCallableVerified);
        return $callBackHandler->call($callBackHandler->getMetadata());
    }

    /**
     * @return mixed
     */
    public function __invoke()
    {
        $args = func_get_args();
        $callback = array_shift($args);
        return self::optimizedCallUserFunction($callback, $args);
    }

    /**
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @param  string $name
     * @return mixed
     */
    public function getMetadatum($name)
    {
        if (array_key_exists($name, $this->metadata)) {
            return $this->metadata[$name];
        }
        return null;
    }
}