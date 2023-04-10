<?php
namespace Coroq\Flow\DefaultValueProvider;

class ProviderComposite implements DefaultValueProviderInterface {
  /** @var array<DefaultValueProviderInterface> */
  private $providers;

  /**
   * @param array<DefaultValueProviderInterface> $providers
   */
  public function __construct(array $providers) {
    $this->providers = $providers;
  }

  /**
   * @return mixed
   */
  public function getValue(string $name) {
    foreach ($this->providers as $provider) {
      $value = $provider->getValue($name);
      if ($value !== null) {
        return $value;
      }
    }
    return null;
  }
}
