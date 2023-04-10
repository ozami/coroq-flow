<?php
declare(strict_types=1);

use Coroq\Flow\DefaultValueProvider\DefaultValueProviderInterface;
use Coroq\Flow\Flow;
use PHPUnit\Framework\TestCase;

/**
 * @covers Coroq\Flow\Flow
 */
class FlowTest extends TestCase {
  public function testEmptyFlowReturnsValuesAsPassed() {
    $values = ['x' => 1];
    $result = Flow::call([], $values);
    $this->assertSame($values, $result);
  }

  public function testCallWithEmptyClosure() {
    $arguments = ['x' => 1];
    $result = Flow::call([function() {}], $arguments);
    $this->assertSame($arguments, $result);
  }

  public function testCallWithEmptyFlow() {
    $arguments = ['x' => 1];
    $result = Flow::call([new Flow([function() {}])], $arguments);
    $this->assertSame($arguments, $result);
  }

  public function testArgumentBinding() {
    Flow::call([function($x, $y) {
      $this->assertSame(1, $x);
      $this->assertSame(2, $y);
    }], ['x' => 1, 'y' => 2]);
  }

  public function testArgumentBindingWithNullValues() {
    Flow::call([function($x, $y) {
      $this->assertSame(null, $x);
      $this->assertSame(null, $y);
    }]);
  }

  public function testArgumentBindingWithNestedFlow() {
    $subFlow = new Flow([
      function($x, $y) {
        $this->assertSame(1, $x);
        $this->assertSame(2, $y);
      },
    ]);
    Flow::call([$subFlow], ['x' => 1, 'y' => 2]);
  }

  public function testResultAssignment() {
    $result = Flow::call([function() {
      return ['x' => 1];
    }]);
    $this->assertSame(['x' => 1], $result);
  }

  public function testResultOverwrite() {
    $result = Flow::call([function() {
      return ['x' => 2];
    }], ['x' => 1]);
    $this->assertSame(['x' => 2], $result);
  }

  public function testResultOverwriteWithNull() {
    $result = Flow::call([function() {
      return ['x' => null];
    }], ['x' => 1]);
    $this->assertSame(['x' => null], $result);
  }

  public function testResultAddition() {
    $result = Flow::call([function() {
      return ['x' => 1];
    }], ['y' => 2]);
    $this->assertSame(['y' => 2, 'x' => 1], $result);
  }

  public function testExceptionThrownWhenStepReturnsString() {
    $this->expectException(\DomainException::class);
    $this->expectExceptionMessageMatches('# defined in .+\([0-9]+\)#u');
    Flow::call([function() {
      return 'test';
    }]);
  }

  public function testAllStepsExecutedInOrder() {
    $result = Flow::call([
      $this->makePushToArray(1),
      $this->makePushToArray(2),
    ], ['x' => []]);
    $this->assertEquals(['x' => [1, 2]], $result);
  }

  public function testNestedFlowStepExecution() {
    $subFlow = new Flow([
      $this->makePushToArray(1),
      $this->makePushToArray(2),
    ]);
    $result = Flow::call([
      $this->makePushToArray(0),
      $subFlow,
      $this->makePushToArray(3),
    ], ['x' => []]);
    $this->assertSame(['x' => [0, 1, 2, 3]], $result);
  }

  public function testAppendStep() {
    $flow = new Flow();
    $flow->appendStep($this->makePushToArray(0));
    $flow->appendStep($this->makePushToArray(1));
    $flow->appendStep($this->makePushToArray(2));
    $result = $flow();
    $this->assertSame(['x' => [0, 1, 2]], $result);
  }

  public function testExceptionWhenAppendingStepDuringExecution() {
    $flow = new Flow([
      function ($thisFlow) {
        $thisFlow->appendStep(function() {});
      },
    ]);
    $this->expectException(LogicException::class);
    $flow();
  }

  public function testPrependStep() {
    $flow = new Flow();
    $flow->prependStep($this->makePushToArray(0));
    $flow->prependStep($this->makePushToArray(1));
    $flow->prependStep($this->makePushToArray(2));
    $result = $flow();
    $this->assertSame(['x' => [2, 1, 0]], $result);
  }

  public function testExceptionWhenPrependingStepDuringExecution() {
    $flow = new Flow([
      function ($thisFlow) {
        $thisFlow->prependStep(function() {});
      }
    ]);
    $this->expectException(LogicException::class);
    $flow();
  }

  public function testGetAndSetDefaultValueProvider() {
    $container = $this->createMock(DefaultValueProviderInterface::class);
    $flow = new Flow();
    $this->assertNull($flow->getDefaultValueProvider());
    $flow->setDefaultValueProvider($container);
    $this->assertSame($container, $flow->getDefaultValueProvider());
  }

  public function testGetValues() {
    $values = ['x' => 1, 'y' => 2];
    $flow = new Flow();
    $flow(['x' => 1, 'y' => 2]);
    $this->assertSame($values, $flow->getValues());
  }

  public function testSetValues() {
    $flow = new Flow();
    $flow->setValues(['x' => 1, 'y' => 2]);
    $this->assertSame(['x' => 1, 'y' => 2], $flow->getValues());
  }

  public function testMergeValues() {
    $flow = new Flow();
    $flow->setValue('x', 1);
    $flow->setValue('y', 2);
    $flow->mergeValues(['x' => null, 'z' => 3]);
    $this->assertSame(['x' => null, 'y' => 2, 'z' => 3], $flow->getValues());
  }

  public function testMergeValuesCanHandleNull() {
    $flow = new Flow();
    $flow->setValue('x', 1);
    $flow->setValue('y', 2);
    $flow->mergeValues(null);
    $this->assertSame(['x' => 1, 'y' => 2], $flow->getValues());
  }

  public function testGetValueReturnsNullForNonexistentItemInContainer() {
    $container = $this->createMock(DefaultValueProviderInterface::class);
    $container->method('getValue')->with($this->equalTo('fromContainer'))->willReturn(null);
    $flow = new Flow();
    $flow->setDefaultValueProvider($container);
    $this->assertNull($flow->getValue('fromContainer'));
  }

  public function testGetValueReturnsValueForExistentItemInContainer() {
    $container = $this->createMock(DefaultValueProviderInterface::class);
    $container->method('getValue')->with($this->equalTo('fromContainer'))->willReturn('the_value');
    $flow = new Flow();
    $flow->setDefaultValueProvider($container);
    $this->assertSame('the_value', $flow->getValue('fromContainer'));
  }

  public function testSetValueBeforeExecution() {
    $flow = new Flow([
      function() {},
    ]);
    $flow->setValue('x', 1);
    $this->assertSame(['x' => 1], $flow->getValues());
    $result = $flow();
    $this->assertSame(['x' => 1], $result);
  }

  public function testSetValueAfterExecution() {
    $flow = new Flow();
    $flow(['x' => 1]);
    $flow->setValue('y', 2);
    $this->assertSame(['x' => 1, 'y' => 2], $flow->getValues());
  }

  public function testThisFlowWillBePassedAsAnArgument() {
    $flow = new Flow([
      function($thisFlow) {
        $this->assertInstanceOf(Flow::class, $thisFlow);
      },
    ]);
    $flow();
  }

  public function testBreakStopsFlowExecution() {
    $flow = new Flow([
      function() {
        return ['x' => 1];
      },
      function($x, $thisFlow) {
        $this->assertSame(1, $x);
        $thisFlow->break();
        return (['y' => 2]);
      },
      function() {
        // This function should not be called because the flow is stopped by the $breakFlow() call
        $this->fail('The flow should have stopped before this function.');
      },
    ]);
    $result = $flow();
    $this->assertSame(['x' => 1, 'y' => 2], $result);
  }

  public function testCallingBreakWhileNotInvoking() {
    $flow = new Flow();
    $this->expectException(LogicException::class);
    $flow->break();
  }

  public function testCallingApplyWhileNotInvoking() {
    $flow = new Flow();
    $step = function($x) {
      return ['y' => $x + 1];
    };
    $flow->setValue('x', 1);
    $flow->apply($step);
    $this->assertSame(['x' => 1, 'y' => 2], $flow->getValues());
  }

  public function testFlowWouldNotCatchException() {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Test exception');
    Flow::call([
      function() {
        throw new \RuntimeException('Test exception');
      },
      function() {
        $this->fail('The flow should have stopped before this function.');
      },
    ]);
  }

  private function makePushToArray($value) {
    return function($x) use ($value) {
      $x[] = $value;
      return compact("x");
    };
  }
}
