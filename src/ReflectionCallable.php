<?php
declare(strict_types=1);
namespace Coroq\Flow;

use Closure;
use LogicException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

/**
 * @internal
 */
class ReflectionCallable {
  /**
   * Get a reflection of a callable
   * @param callable $callable
   * @return ReflectionFunctionAbstract
   */
  public static function createFromCallable(callable $callable): ReflectionFunctionAbstract {
    if (is_array($callable)) {
      return new ReflectionMethod($callable[0], $callable[1]);
    }
    if ($callable instanceof Closure) {
      return new ReflectionFunction($callable);
    }
    if (is_object($callable)) {
      return new ReflectionMethod($callable, '__invoke');
    }
    if (is_string($callable)) {
      if (strpos($callable, '::') === false) {
        return new ReflectionFunction($callable);
      }
      return new ReflectionMethod($callable);
    }
    // @codeCoverageIgnoreStart
    throw new LogicException('Unknown type of callable. ' . gettype($callable));
    // @codeCoverageIgnoreEnd
  }
}
