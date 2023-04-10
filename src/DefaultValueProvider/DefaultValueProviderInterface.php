<?php
namespace Coroq\Flow\DefaultValueProvider;

interface DefaultValueProviderInterface {
  /**
   * @return mixed
   */
  public function getValue(string $name);
}
