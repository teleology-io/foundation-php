# Foundation Library
The Foundation PHP Library is your gateway to effortless interaction with the Foundation API. Designed with simplicity and efficiency in mind, this library abstracts away the complexity of direct API calls, providing a clean and intuitive interface for developers. 

## Installation

```
composer require foundation/foundation-php
```

## Usage Example:
```php
use Foundation\Foundation;


$foundation = new Foundation('https://foundation-api.teleology.io', '<your-api-key>', '<optional-global-uid>');

$foundation->subscribe(function (string $event, $data) {
   echo $event . ": " . json_encode($data) . "\n";
});

$env = $foundation->getEnvironment();
$config = $foundation->getConfiguration();
$variable = $foundation->getVariable('open_enrollment', '<uid-override>', '<fallback-value>');
```