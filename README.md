# Flow

Flow is a lightweight PHP library designed to manage multi-step control flow in an organized and efficient manner.

## Requirements

- PHP >= 7.2
- Psr/container

## Installation

Install the library using Composer:

```sh
composer require coroq/flow
```

## Usage

### Basic

```php
use Coroq\Flow\Flow;

// Let's create a new Flow instance.
$flow = new Flow();

// The Flow manages a collection of values in an associative array.
// To get started, we set the value of 'x' to 1.
$flow->setValues(['x' => 1]);

// Now we'll prepare the steps for our flow.
// Steps can be any callable that returns an array or null.

// First step: using a closure.
// Our function receives its arguments from the values based on their names.
// The value of $x is taken from the value with the key 'x' above, which is 1.
$step1 = function($x) {
  // We return an array that will be merged with the existing values.
  // After this step, our values will be ['x' => 1, 'y' => 2].
  return ['y' => $x + 1];
};

// Second step: using a class with an __invoke magic method.
class Step2 {
  // Don't worry about the order of parameters; only names are used for argument binding.
  // Type hints (like int) don't affect argument binding.
  public function __invoke(int $y, int $x): array {
    // To update a value, return an array with the same key.
    // Since we only return the 'y' key, 'x' will not be changed.
    // After this step, our values will be ['x' => 1, 'y' => 3].
    return ['y' => $x + $y];
  }
}
$step2 = new Step2();

// Third step: using a regular function.
// Only list the parameters you need for the step.
// Here, $z will be null since there's no 'z' key in the values array.
function step3($y, $z) {
  if ($z === null) {
    return ['z' => $y * 2];
  }
  // If you return null, the values won't change.
}

// Add the steps to the flow in the order you want them to execute.
$flow->appendStep($step1);
$flow->appendStep($step2);
$flow->appendStep('step3');

// Execute!
$flow();

// The result will be ['x' => 1, 'y' => 3, 'z' => 6].
$result = $flow->getValues();

// Need a single value? Use getValue().
$z = $flow->getValue('z');

// For a shorter version, create a flow with the steps directly.
// Pass the initial value as an argument, and the Flow will return the result.
$flow = new Flow([$step1, $step2, 'step3']);
$result = $flow(['x' => 1]);

// The shortest version of all.
$result = Flow::call([$step1, $step2, 'step3'], ['x' => 1]);
```

### Nesting `Flow`

```php
function initializeMessage() {
  return ['message' => 'hello world'];
}

function appendExclamationMark(string $message) {
  $message .= '!';
  return compact('message');
}

$makeMessage = new Flow(['initializeMessage', 'appendExclamationMark']);

function convertToUppercase(string $message) {
  $message = strtoupper($message);
  return compact('message');
}

$makeMessageInUppercase = new Flow([
  $makeMessage, // A Flow object can be a step of another Flow
  'convertToUppercase',
]);

$result = $makeMessageInUppercase([]);
// $result will be ['message' => 'HELLO WORLD!'].
```

### Breaking the flow

This example demonstrates how to break the flow when a certain condition is met.

```php
// First step
// $thisFlow is a special argument representing the Flow instance executing this step.
function checkUserLoggedIn(Flow $thisFlow, $authenticationService, $responseFactory) {
  // If the user is not logged in, we stop the flow
  if (!$authenticationService->isLoggedIn()) {
    $thisFlow->break();
    // You can return a result after breaking the flow
    // In this case, we return a 'forbidden' response
    return ['response' => $responseFactory->createResponse(403)];
  }
}

class ProfileController {
  // Second step
  public static function edit($request, $responseFactory): array {
    // ...
  }
}

// Create and execute the Flow with the necessary dependencies
$controller = new Flow([
  'checkUserLoggedIn',
  'ProfileController::edit',
]);
$result = $controller([
  'authenticationService' => new AuthenticationService(),
  'responseFactory' => $responseFactory,
  'request' => $requestFactory->createServerRequest(...),
]);
```

### Integration with a DI Container

Flow offers a convenient way to integrate a DI Container by fetching default values from an external data source. This allows for seamless integration with your preferred DI Container.

```php
use Coroq\Flow\Flow;
use Coroq\Flow\DefaultValueProvider\Psr11ContainerAdapter;

// Set up a DI container implementing the PSR-11 ContainerInterface, and add some dependencies
$diContainer = new SomeDiContainer();
$diContainer->set('userRepository', $userRepositoryFactory);

// Define a step that uses an item from the container
$step = function(UserRepositoryInterface $userRepository) {
  // ...
};

$flow = new Flow([$step]);
// Set the container as the default value provider
$flow->setDefaultValueProvider(new Psr11ContainerAdapter($diContainer));
$result = $flow();
```

## License

This project is licensed under the MIT License.
