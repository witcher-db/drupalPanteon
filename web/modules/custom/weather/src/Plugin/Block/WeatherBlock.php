<?php

namespace Drupal\weather\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\weather\Service\OpenWeatherClient;
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
   * The weather API client service.
   *
   * @var \Drupal\weather\Service\OpenWeatherClient
   */
  protected OpenWeatherClient $weatherClient;

  /**
   * Constructs a WeatherBlock instance.
   *
   * @param array $configuration
   *   Configuration array for the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the block.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   * @param \Drupal\weather\Service\OpenWeatherClient $weather_client
   *   Our custom service to call OpenWeather API.
   */
  public function __construct(
    $configuration,
    $plugin_id,
    $plugin_definition,
    RequestStack $request_stack,
    ConfigFactoryInterface $config_factory,
    OpenWeatherClient $weather_client,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
    $this->configFactory = $config_factory;
    $this->weatherClient = $weather_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('config.factory'),
      $container->get('weather.openweather_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configFactory->get('weather.settings');
    $use_ip = (bool) $config->get('use_ip');
    $city = (string) $config->get('city');
    $api_key = (string) $config->get('api_key');

    if (empty($api_key)) {
      return [
        '#markup' => $this->t('API key is not set. Please configure it in Weather settings.'),
      ];
    }

    if ($use_ip) {
      $request = $this->requestStack->getCurrentRequest();
      $ip = $request->getClientIp();

      // Test for local IP if IP is local gives back server global IP.
      if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        $ip = file_get_contents('https://api.ipify.org');
      }

      $response = unserialize(file_get_contents("http://ip-api.com/php/$ip"));

      $lat = $response['lat'];
      $lon = $response['lon'];

      $data = $this->weatherClient->getWeatherByCoordinates($lat, $lon);
    }
    else {
      if (empty($city)) {
        return [
          '#markup' => $this->t('City name is not set. Please configure it in Weather settings.'),
        ];
      }

      $data = $this->weatherClient->getWeatherByCityName($city);
    }

    if (!is_array($data)) {
      return ['#markup' => 'Unexpected error'];
    }

    $weather = $data['weather'][0]['main'];
    $city = $data['name'];
    $temp = $data['main']['temp'];

    return [
      '#theme' => 'weather_block',
      '#city_name' => $city,
      '#weather' => $weather,
      '#temp' => $temp,
      '#cache' => [
        'max-age' => 3600,
        'tags' => ['config:weather.settings'],
      ],
    ];
  }

}
