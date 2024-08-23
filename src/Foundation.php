<?php
namespace Foundation;

require 'vendor/autoload.php'; // Ensure you have the required libraries installed

use GuzzleHttp\Client as HttpClient;
use React\EventLoop\Factory;
use React\Socket\Connector;
use WebSocket\Client;
use WebSocket\ConnectionException;
use Ratchet\RFC6455\Messaging\MessageInterface;

class Foundation
{
  private $url;
  private $uid;
  private $config;
  private $environment;
  private $variables = [];
  private $callback;
  private $client;

  private $socket;

  private $socketUrl;

  public function __construct(string $url, string $apiKey, ?string $uid = null)
  {
    $this->url = $url;
    $this->uid = $uid;
    $this->socketUrl = str_replace("http", "ws", "{$url}") . "/v1/realtime?apiKey={$apiKey}";

    $this->client = new HttpClient([
      'headers' => ['X-Api-Key' => $apiKey]
    ]);

    $this->realtime();
  }

  private function realtime() {
    \Ratchet\Client\connect($this->socketUrl)->then(function ($conn) {
      $conn->on('message', function (MessageInterface $msg) use ($conn) {
        $data = json_decode($msg->getPayload(), true);
        $this->handleEvent($data["type"], $data);
      });
    }, function ($e) {
      $this->realtime();
    });
  }

  public function getEnvironment()
  {
    if ($this->environment === null) {
      $response = $this->client->request('GET', "{$this->url}/v1/environment");
      $this->environment = json_decode($response->getBody(), true);
    }

    return $this->environment;
  }

  public function getConfiguration()
  {
    if ($this->config === null) {
      $response = $this->client->request('GET', "{$this->url}/v1/configuration");
      $result = json_decode($response->getBody(), true);

      if ($response->getHeaderLine('Content-Type') === 'application/json') {
        $this->config = json_decode($result['content'], true);
      } else {
        $this->config = $result['content'];
      }
    }

    return $this->config;
  }

  public function getVariable(string $name, ?string $uid = null, $fallback = null)
  {
    if (isset($this->variables[$name])) {
      return $this->variables[$name]['value'];
    }

    $response = $this->client->request('POST', "{$this->url}/v1/variable", [
      'json' => ['name' => $name, 'uid' => $uid ?? $this->uid]
    ]);

    if ($response->getStatusCode() === 200) {
      $this->variables[$name] = json_decode($response->getBody(), true);
      return $this->variables[$name]['value'];
    }

    return $fallback;
  }

  public function subscribe(callable $cb)
  {
    $this->callback = $cb;
  }

  private function handleEvent(string $event, $data)
  {
    switch ($event) {
      case 'variable.updated':
        $name = $data['payload']['name'];
        unset($this->variables[$name]);
        $this->getVariable($name);

        if ($this->callback) {
          call_user_func($this->callback, $event, $this->variables[$name]);
        }
        break;

      case 'configuration.published':
        $this->config = null;

        if ($this->callback) {
          call_user_func($this->callback, $event, $this->getConfiguration());
        }
        break;

      case 'environment.updated':
        $this->environment = null;

        if ($this->callback) {
          call_user_func($this->callback, $event, $this->getEnvironment());
        }
        break;
    }
  }
}
