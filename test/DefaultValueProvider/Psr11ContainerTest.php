<?php
declare(strict_types=1);

use Coroq\Flow\DefaultValueProvider\Psr11Container;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class Psr11ContainerTest extends TestCase {
  public function testGetValueReturnsValueFromContainer() {
    $container = $this->createMock(ContainerInterface::class);
    $container->method('get')
      ->with($this->equalTo('itemId'))
      ->willReturn('the value');
    $provider = new Psr11Container($container);
    $this->assertSame('the value', $provider->getValue('itemId'));
  }

  public function testGetValueReturnsNullIfTheItemDoesNotExist() {
    $container = $this->createMock(ContainerInterface::class);
    $container->method('get')
      ->with($this->equalTo('itemId'))
      ->will($this->throwException(new SampleNotFoundException()));
    $provider = new Psr11Container($container);
    $this->assertNull($provider->getValue('itemId'));
  }
}

class SampleNotFoundException extends Exception implements NotFoundExceptionInterface {
}
