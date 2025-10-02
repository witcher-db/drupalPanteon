<?php

namespace Drupal\weather\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a Weather Block.
 *
 * @Block(
 *   id = "weather",
 *   admin_label = @Translation("Weather Block"),
 *   category = @Translation("Custom")
 * )
 */
class WeatherBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */

  public function build() {
    $markap = '';


    $api_key = '392aa48f176b57093f1a42ab3ad222c1';

    $lat = 50.7472;
    $lon = 25.3254;

    $url = "https://api.openweathermap.org/data/2.5/weather?lat=$lat&lon=$lon&appid=$api_key&units=metric";

    $client = \Drupal::httpClient();
    // $ip = \Drupal::request()->getClientIp();
    // var_dump($ip); //test

    $response = $client->get($url);

    $data = json_decode($response->getBody(), TRUE);

    // var_dump($data);

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
