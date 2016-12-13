<?php

namespace Jced;

/**
 * Class LeakSafeGenerator
 */
class LeakSafeGenerator
{
    /** @var Closure */
    private $onInterruptClosure;

    /** @var Closure */
    private $onCompleteClosure;

    /** @var Closure */
    private $onFinishClosure;

    /** @var Closure */
    private $generatorClosure;

    /**
     * LeakSafeGenerator constructor.
     * @param Closure $initClosure
     * @param Closure $onFinishClosure
     */
    public function __construct(Closure $initClosure = null, Closure $onFinishClosure = null)
    {
        if (!is_null($initClosure)) {
            $this->init($initClosure);
        } else {
            // just create empty generator if function isn't provided
            $this->init(function() {
                if (false) {
                    yield;
                }
            });
        }
        if (!is_null($onFinishClosure)) {
            $this->onFinish($onFinishClosure);
        }
    }
    
    /**
     * @param Closure $closure
     * @return $this
     */
    public function init(Closure $closure) {
        $this->generatorClosure = $closure->bindTo($this);
        return $this;
    }

    /**
     * @param Closure $closure
     * @return $this
     */
    public function onFinish(Closure $closure) {
        $this->onFinishClosure = $closure->bindTo($this);
        return $this;
    }

    /**
     * @param Closure $closure
     * @return $this
     */
    public function onComplete(Closure $closure) {
        $this->onCompleteClosure = $closure->bindTo($this);
        return $this;
    }

    /**
     * @param Closure $closure
     * @return $this
     */
    public function onInterrupt(Closure $closure) {
        $this->onInterruptClosure = $closure->bindTo($this);
        return $this;
    }

    /**
     * @return Generator
     */
    public function getGenerator() {
        try {
            $done = false;
            foreach (call_user_func_array($this->generatorClosure, func_get_args()) as $key => $result) {
                yield $key => $result;
            }
            $done = true;
        } finally {
            if ($done && $this->onCompleteClosure) {
                call_user_func($this->onCompleteClosure);
            } elseif (!$done && $this->onInterruptClosure) {
                call_user_func($this->onInterruptClosure);
            }
            if ($this->onFinishClosure) {
                call_user_func($this->onFinishClosure);
            }
        }
    }
}
