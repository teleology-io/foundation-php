<?php
require 'vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Foundation\Foundation;

class FoundationTest extends TestCase
{
    private $foundation;

    protected function setUp(): void
    {
        $this->foundation = new Foundation('https://foundation-api.teleology.io', '');
        $this->foundation->subscribe(function ($event, $data) {
            var_dump("REALTIME", $event, $data);
        });
    }

    public function testApi()
    {
        // Assuming getEnvironment() returns an array
        $result = $this->foundation->getEnvironment();
        var_dump("ENV", $result);
        $this->assertIsArray($result);

        $result = $this->foundation->getConfiguration();
        var_dump("CONFIG", $result);
        $this->assertIsArray($result);

        $value = $this->foundation->getVariable('open_enrollment');
        var_dump("VARIABLE", $value);
        $this->assertNotNull($value);

        sleep(20);
    }

}
