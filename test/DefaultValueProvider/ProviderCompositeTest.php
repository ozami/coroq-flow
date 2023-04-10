<?php
declare(strict_types=1);

use Coroq\Flow\DefaultValueProvider\DefaultValueProviderInterface;
use Coroq\Flow\DefaultValueProvider\ProviderComposite;
use PHPUnit\Framework\TestCase;

class ProviderCompositeTest extends TestCase {
  public function testGetValueReturnsNullIfNoProviders() {
    $composite = new ProviderComposite([]);
    $this->assertNull($composite->getValue('name'));
  }

  public function testGetValueReturnsValueFromProvider() {
    $provider = $this->createMock(DefaultValueProviderInterface::class);
    $provider->method('getValue')
      ->with($this->equalTo('name'))
      ->willReturn('value');
    $composite = new ProviderComposite([$provider]);
    $this->assertSame('value', $composite->getValue('name'));
  }

  public function testGetValueReturnsValueFromTheFirstProvider() {
    $provider1 = $this->createMock(DefaultValueProviderInterface::class);
    $provider1->method('getValue')
      ->with($this->equalTo('name'))
      ->willReturn('value1');
    $provider2 = $this->createMock(DefaultValueProviderInterface::class);
    $provider2->method('getValue')
      ->with($this->equalTo('name'))
      ->willReturn('value2');
    $composite = new ProviderComposite([$provider1, $provider2]);
    $this->assertSame('value1', $composite->getValue('name'));
  }

  public function testGetValueReturnsValueFromTheLastProvider() {
    $provider1 = $this->createMock(DefaultValueProviderInterface::class);
    $provider1->method('getValue')
      ->with($this->equalTo('name'))
      ->willReturn(null);
    $provider2 = $this->createMock(DefaultValueProviderInterface::class);
    $provider2->method('getValue')
      ->with($this->equalTo('name'))
      ->willReturn('value2');
    $composite = new ProviderComposite([$provider1, $provider2]);
    $this->assertSame('value2', $composite->getValue('name'));
  }
}
