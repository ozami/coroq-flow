<?php
namespace Coroq\Flow\DefaultValueProvider;

use Psr\Container\ContainerInterface;

class Psr11ContainerAdapter implements DefaultValueProviderInterface {
  /** @var ContainerInterface */
  private $container;

  public function __construct(ContainerInterface $container) {
    $this->container = $container;
  }

  /**
   * @return mixed
   */
  public function getValue(string $name) {
    return $this->container->get($name);
  }
}
