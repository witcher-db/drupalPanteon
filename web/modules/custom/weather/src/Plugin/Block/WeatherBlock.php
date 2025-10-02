<?php

namespace Drupal\weather\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

use GuzzleHttp\Exception\RequestException;
/**
 * Provides a Weather Block.
 *
 */

#[Block(
 id : "custom_weather_block",
 admin_label : new TranslatableMarkup("Weather Block"),
 category : new TranslatableMarkup("Custom")
)]

class WeatherBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */

  public function build() {
    $markup = '';
    $weather = "";
    $city = "";
    $temp = "";

    $client = \Drupal::httpClient();
    $ip = \Drupal::request()->getClientIp();

    if ( !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) ) {
      $ip = file_get_contents('https://api.ipify.org'); //test for local IP if IP is local gives back server global IP
    }

    $response = unserialize(file_get_contents("http://ip-api.com/php/$ip"));

    $api_key = '392aa48f176b57093f1a42ab3ad222c1';

    $lat = $response["lat"];
    $lon = $response["lon"];

    $url = "https://api.openweathermap.org/data/2.5/weather?lat=$lat&lon=$lon&appid=$api_key&units=metric";

    try {
      $response = $client->get($url);
      $data = json_decode($response->getBody(), TRUE);
    } catch (RequestException $e) {
      $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : '';
      $data = !empty($responseBody) ? json_decode($responseBody, TRUE) : [];
      $message = $data['message'] ?? $e->getMessage();
      return ['#markup' => $message];
    } catch (\Exception $e) {
      return ['#markup' => "API failed"];
    }

    $weather = $data["weather"][0]["main"];
    $city = $data["name"];
    $temp = $data["main"]["temp"];


    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['weather-block']],
      'city' => [
        '#markup' => "<span> <h3>$city: </h3></span>",
      ],
      'weather' => [
        '#markup' => "<span> $weather </span>",
      ],
      'temp' => [
        '#markup' => "<span> $temp C</span>",
      ],
    ];
  }
}
