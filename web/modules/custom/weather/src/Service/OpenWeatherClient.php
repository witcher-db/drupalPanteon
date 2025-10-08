<?php

namespace Drupal\weather\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Service to communicate with the OpenWeather API.
 */
class OpenWeatherClient {
  /**
   * The HTTP client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;
  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a WeatherBlock instance.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Guzzle HTTP client service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   */
  public function __construct(ClientInterface $http_client, ConfigFactoryInterface $config_factory) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
  }

  /**
   * Retrieves weather data based on latitude and longitude.
   *
   * @param float $lat
   *   The latitude coordinate.
   * @param float $lon
   *   The longitude coordinate.
   *
   * @return array|bool
   *   The decoded weather data from the API response,
   *   or the string FALSE if the request fails.
   */
  public function getWeatherByCoordinates(float $lat, float $lon) {
    $url = "https://api.openweathermap.org/data/2.5/weather?lat=$lat&lon=$lon&appid=%s&units=metric";
    return $this->openWeatherRequest($url);
  }

  /**
   * Retrieves weather data by city name.
   *
   * @param string $city
   *   The name of the city.
   *
   * @return array|bool
   *   The decoded weather data from the API response,
   *   or the string FALSE if the request fails.
   */
  public function getWeatherByCityName(string $city) {
    $url = "https://api.openweathermap.org/data/2.5/weather?q=$city&appid=%s&units=metric";
    return $this->openWeatherRequest($url);
  }

  /**
   * Tests whether the provided API key is valid.
   *
   * Makes a simple request to verify that the API key works.
   *
   * @param string $api_key
   *   The API key to test.
   *
   * @return array|bool
   *   The decoded API response if valid, or FALSE if invalid.
   */
  public function testApiKey(string $api_key) {
    $url = "https://api.openweathermap.org/data/2.5/weather?lat=40&lon=40&appid=$api_key&units=metric";
    return $this->openWeatherRequest($url);
  }

  /**
   * Sends a request to the OpenWeather API and returns the result.
   *
   * @param string $url
   *   The formatted API request URL, with a placeholder for the API key.
   *
   * @return array|bool
   *   The decoded response data from the API, or FALSE on failure.
   */
  protected function openWeatherRequest(string $url) {
    $config = $this->configFactory->get('weather.settings');
    $api_key = $config->get('api_key');

    if (empty($api_key)) {
      return FALSE;
    }

    try {
      $response = $this->httpClient->get(sprintf($url, $api_key));
      return json_decode($response->getBody()->getContents(), TRUE);
    }
    catch (\Exception) {
      return FALSE;
    }
  }

}
