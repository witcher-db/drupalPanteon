<?php

namespace Drupal\weather\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a Weather Block.
 */
#[Block(
  id: "custom_weather_block",
  admin_label: new TranslatableMarkup("Weather Block"),
  category: new TranslatableMarkup("Custom")
)]
class WeatherBlock extends BlockBase implements ContainerFactoryPluginInterface {
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
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * Constructs a WeatherBlock instance.
   *
   * @param array $configuration
   *   Configuration array for the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the block.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Guzzle HTTP client service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   */
  public function __construct(
    $configuration,
    $plugin_id,
    $plugin_definition,
    ClientInterface $http_client,
    RequestStack $request_stack,
    ConfigFactoryInterface $config_factory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = $http_client;
    $this->requestStack = $request_stack;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('request_stack'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $request = $this->requestStack->getCurrentRequest();
    $ip = $request->getClientIp();

    // Test for local IP if IP is local gives back server global IP.
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
      $ip = file_get_contents('https://api.ipify.org');
    }

    $response = unserialize(file_get_contents("http://ip-api.com/php/$ip"));

    $config = $this->configFactory->get('weather.settings');
    $api_key = $config->get('api_key');

    $lat = $response["lat"];
    $lon = $response["lon"];

    $url = "https://api.openweathermap.org/data/2.5/weather?lat=$lat&lon=$lon&appid=$api_key&units=metric";

    try {
      $response = $this->httpClient->get($url);
      $data = json_decode($response->getBody(), TRUE);
    }
    catch (RequestException $e) {
      $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : '';
      $data = !empty($responseBody) ? json_decode($responseBody, TRUE) : [];
      $message = $data['message'] ?? $e->getMessage();
      return ['#markup' => $message];
    }
    catch (\Exception $e) {
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
