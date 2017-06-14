<?php

/**
 * @file
 * Provides a D&B Plus HTTP API connection service.
 */

/**
 * Guzzle usage, for now.
 *
 * @TODO Replace hard Guzzle requirement to have it injected with PSR-7.
 */
use Guzzle\Http\Client;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Exception\RequestException;

/**
 * Class DnbConnection.
 */
class DnbConnection {

  /**
   * The access token expiration timeout for accessing DNB API.
   *
   * @var DateTime
   */
  private $accessExpiration;

  /**
   * The access token for accessing DNB API.
   *
   * @var string
   */
  private $accessToken;

  /**
   * The HTTP client for making API requests.
   *
   * @var \Guzzle\Http\ClientInterface
   */
  private $client;

  /**
   * The base64 encoded access secret.
   *
   * @var string
   *
   * @TODO Experiment with allowing this to be set only from $conf.
   * @TODO Allow this to be generated from regular basic auth credentials.
   */
  private $apiSecret;

  /**
   * The API origin URL.
   *
   * @var string
   */
  private $originUrl;

  /**
   * DnbConnection constructor.
   *
   * @param \Guzzle\Http\ClientInterface $client
   *   The HTTP client to use.
   *
   * @TODO Use a PSR-7 HttpClientInterface and use dependency injection.
   */
  public function __construct(ClientInterface $client) {
    $this->client = $client;
    $this->originUrl = variable_get('dnb_api_origin', 'https://plus.dnb.com/');
    $this->apiSecret = variable_get('dnb_api_secret', '');
  }

  /**
   * Creates a new DnbConnection.
   *
   * @TODO Use a PSR-7 HttpClientInterface and use dependency injection.
   *
   * @return \DnbConnection
   *   A new DnbConnection.
   */
  public static function create() {
    $client = new Client();
    return new DnbConnection($client);
  }

  /**
   * Handles an exception from the HTTP Client.
   *
   * @param \Guzzle\Http\Exception\RequestException $exception
   * @param string $action
   */
  private function handleException(RequestException $exception, $action = 'using') {
    $response = $exception->getResponse();
    $body = json_decode($exception->getResponse()->getBody());
    $transcript = filter_xss(print_r($body->transactionDetail, TRUE));
    watchdog('dnb_api', "Error %code in $action DNB API: %error %msg !body",
      array(
        '%code' => $response->getStatusCode(),
        '%error' => isset($body->result->errorCode) ? $body->result->errorCode : '',
        '%msg' => isset($body->result->errorMessage) ? $body->result->errorMessage : 'Unable to produce a response.',
        '!body' => '<br/><pre>' . strlen($transcript) > 254 ? substr($transcript, 0, 251) . '...' : $transcript . '</pre>',
      ));
  }

  /**
   * Retrieves an access token for the current connection.
   *
   * @return string|null
   *   The current connection's access token.
   */
  private function getAccessToken() {
    // Resets.
    if ($this->accessToken && $this->accessExpiration && REQUEST_TIME < $this->accessExpiration->getTimestamp()) {
      return $this->accessToken;
    }
    $cache = cache_get('dnb_api');
    if (isset($cache->data['accessToken']) && REQUEST_TIME < $cache->data['accessExpirationTime']) {
      $this->setAccessToken($cache->data['accessToken']);
      return $this->accessToken;
    }
    if (!$this->reset()) {
      return NULL;
    }
    $result = array(
      'accessExpirationTime' => $this->accessExpiration->getTimestamp(),
      'accessToken' => $this->accessToken,
    );
    // Allow the cache to clear at any time by not setting an expire time.
    cache_set('dnb_api', $result, 'cache', CACHE_TEMPORARY);
    return $this->accessToken;
  }

  /**
   * Sets the access token.
   *
   * @param string $access_token
   *   The new access token.
   */
  private function setAccessToken($access_token) {
    $this->accessToken = $access_token;
  }

  /**
   * Resets the current DNB API Connection.
   *
   * @return bool
   *   True if the connection is reset successfully.
   */
  public function reset() {
    $r_headers = array(
      'Authorization' => 'Basic ' . $this->apiSecret,
      'Content-Type' => 'application/json',
      'Cache-Control' => 'no-cache',
    );
    $r_body = <<< JSON
{
    "grant_type": "client_credentials"
}
JSON;
    try {
      $request = $this->client
        ->post($this->originUrl . 'v2/token', $r_headers);
      $response = $request->setBody($r_body, 'application/json')
        ->send();
    }
    catch (RequestException $exception) {
      $response = $exception->getResponse();
      $this->handleException($exception, 'resetting');
    }
    if ($response->isSuccessful()) {
      $data = json_decode($response->getBody());
      $this->setAccessToken(isset($data->access_token) ? $data->access_token : NULL);
      $expiration = isset($data->expiresIn) ? $data->expiresIn : 86400;
      $this->accessExpiration = new DateTime("now + $expiration seconds");
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Queries the DNB REST API.
   *
   * @param string $query
   *   A REST query in the format 'request/path/data?options'.
   *
   * @return mixed
   *   The decoded JSON REST response.
   *
   * @example
   *   $dnb = DnbConnection::create();
   *   $result = dnb->query('duns-search/ip/216.55.149.9?view=standard');
   */
  public function query($query) {
    global $base_url;
    $r_headers = array(
      'Authorization' => 'Bearer ' . $this->getAccessToken(),
      'Origin' => $base_url,
    );
    $request = $this->client->get($this->originUrl . "v1/$query", $r_headers);
    try {
      $body = json_decode($request->send()->getBody());
    }
    catch (RequestException $exception) {
      $this->handleException($exception, 'querying');
      $body = json_decode($exception->getResponse()->getBody());
    }
    return $body;
  }

}
