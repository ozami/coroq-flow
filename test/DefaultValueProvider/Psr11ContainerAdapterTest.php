<?php
declare(strict_types=1);

use Coroq\Flow\DefaultValueProvider\Psr11ContainerAdapter;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class Psr11ContainerAdapterTest extends TestCase {
  public function testGetValueReturnsValueFromContainer() {
    $container = $this->createMock(ContainerInterface::class);
    $container->method('get')
      ->with($this->equalTo('itemId'))
      ->willReturn('the value');
    $provider = new Psr11ContainerAdapter($container);
    $this->assertSame('the value', $provider->getValue('itemId'));
  }

  public function testGetValueReturnsNullIfTheItemDoesNotExist() {
    $container = $this->createMock(ContainerInterface::class);
    $container->method('get')
      ->with($this->equalTo('itemId'))
      ->will($this->throwException(new SampleNotFoundException()));
    $provider = new Psr11ContainerAdapter($container);
    $this->assertNull($provider->getValue('itemId'));
  }
}

class SampleNotFoundException extends Exception implements NotFoundExceptionInterface {
}
