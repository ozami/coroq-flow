<?php
declare(strict_types=1);
namespace Coroq\Flow;

use Coroq\Flow\DefaultValueProvider\DefaultValueProviderInterface;
use DomainException;
use LogicException;
use ReflectionFunctionAbstract;

class Flow {
  /** @var array<callable> */
  private $steps;

  /** @var bool */
  private $executing;

  /** @var array<string,mixed> */
  private $values;

  /** @var bool */
  private $breaked;

  /** @var ?DefaultValueProviderInterface */
  private $defaultValueProvider;

  /**
   * Constructor
   * @param array<callable> $steps Steps to be called in the Flow.
   */
  public function __construct(array $steps = []) {
    $this->steps = $steps;
    $this->executing = false;
    $this->values = [];
    $this->breaked = false;
    $this->defaultValueProvider = null;
  }

  /**
   * Adds a step to the end of the Flow
   * @param callable $step A step to be added to the Flow.
   * @return void
   */
  public function appendStep(callable $step): void {
    $this->assertNotExecuting();
    $this->steps[] = $step;
  }

  public function prependStep(callable $step): void {
    $this->assertNotExecuting();
    array_unshift($this->steps, $step);
  }

  public function getDefaultValueProvider(): ?DefaultValueProviderInterface {
    return $this->defaultValueProvider;
  }

  public function setDefaultValueProvider(?DefaultValueProviderInterface $defaultValueProvider): void {
    $this->defaultValueProvider = $defaultValueProvider;
  }

  /**
   * @return array<string,mixed>
   */
  public function getValues(): array {
    return $this->values;
  }

  /**
   * @param array<string,mixed> $values
   */
  public function setValues(array $values): void {
    $this->values = $values;
  }

  /**
   * @param ?array<string,mixed> $values
   */
  public function mergeValues(?array $values): void {
    $this->values = array_merge($this->values, (array)$values);
  }

  /**
   * @return mixed
   */
  public function getValue(string $name) {
    return $this->values[$name] ?? $this->getDefaultValue($name);
  }

  /**
   * @param mixed $value
   */
  public function setValue(string $name, $value): void {
    $this->values[$name] = $value;
  }

  public function break(): void {
    $this->assertExecuting();
    $this->breaked = true;
  }

  /**
   * Create a Flow and call it
   * @param array<callable> $steps Steps to be executed in flow
   * @param array<string,mixed> $values
   * @return array<string,mixed>
   */
  public static function call(array $steps, array $values = []): array {
    $flow = new self($steps);
    return $flow($values);
  }

  /**
   * @param array<string,mixed> $values
   * @return array<string,mixed>
   */
  public function __invoke(array $values = []): array {
    try {
      $this->mergeValues($values);
      $this->executing = true;
      $this->breaked = false;
      foreach ($this->steps as $step) {
        $this->apply($step);
        if ($this->breaked) {
          break;
        }
      }
      return $this->values;
    }
    finally {
      $this->executing = false;
    }
  }

  /**
   * Executes a single step within the context of the current Flow.
   * The arguments for the step are taken from the values of the Flow.
   * You can get the result values by calling getValues() after apply().
   * @param callable $step A step to be applied to the Flow
   */
  public function apply(callable $step): void {
    if ($step instanceof self) {
      $this->applyFlowStep($step);
    }
    else {
      $this->applyNonFlowStep($step);
    }
  }

  /**
   * Executes a Flow step within the context of the current Flow.
   * @param self $flow The Flow step to be executed.
   */
  private function applyFlowStep(self $flow): void {
    $this->values = $flow($this->values);
  }

  /**
   * Executes a non-Flow step within the context of the current Flow.
   * @param callable $callable The non-Flow step to be executed
   */
  private function applyNonFlowStep(callable $callable): void {
    $reflection = ReflectionCallable::createFromCallable($callable);
    $namedArguments = $this->makeNamedArguments($reflection);
    $result = call_user_func_array($callable, $namedArguments);
    $this->validateResult($result, $reflection);
    $this->mergeValues($result);
  }

  /**
   * @return array<mixed>
   */
  private function makeNamedArguments(ReflectionFunctionAbstract $reflection): array {
    $namedArguments = [];
    foreach ($reflection->getParameters() as $parameter) {
      $parameterName = $parameter->getName();
      if ($parameterName == 'thisFlow') {
        $namedArguments[] = $this;
      }
      else {
        $namedArguments[] = $this->getValue($parameterName);
      }
    }
    return $namedArguments;
  }

  /**
   * @param mixed $result
   */
  private function validateResult($result, ReflectionFunctionAbstract $reflection): void {
    if (!is_array($result) && !is_null($result)) {
      throw new DomainException(sprintf(
        'Flow function %s, defined in %s(%s), returned an invalid result type: %s. The result must be either an array or null.',
        $reflection->getName(),
        $reflection->getFileName(),
        $reflection->getStartLine(),
        gettype($result)
      ));
    }
  }

  /**
   * @return mixed
   */
  private function getDefaultValue(string $name) {
    if (!$this->defaultValueProvider) {
      return null;
    }
    return $this->defaultValueProvider->getValue($name);
  }

  private function assertExecuting(): void {
    if (!$this->executing) {
      throw new LogicException('This operation can only be performed while the Flow is being executed.');
    }
  }

  private function assertNotExecuting(): void {
    if ($this->executing) {
      throw new LogicException('This operation cannot be performed while the Flow is being executed.');
    }
  }
}
