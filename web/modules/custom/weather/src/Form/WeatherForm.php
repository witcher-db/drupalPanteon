<?php

namespace Drupal\weather\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a configuration form for the Weather module.
 */
class WeatherForm extends ConfigFormBase {

  /**
   * The weather API client service.
   *
   * @var \Drupal\weather\Service\OpenWeatherClient
   */
  protected $weatherClient;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->weatherClient = $container->get('weather.openweather_client');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['weather.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'weather_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('weather.settings');

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('api_key') ?: '',
      '#description' => $this->t('Enter your API key for the weather service.'),
      '#required' => TRUE,
    ];

    $form['city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City name'),
      '#default_value' => $config->get('city') ?: '',
      '#description' => $this->t('Enter your city name'),
    ];

    $form['use_ip'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use IP address for location'),
      '#default_value' => $config->get('use_ip') ?? TRUE,
      '#description' => $this->t('If checked, the block will attempt to detect the user location by IP. If unchecked, the configured city will be used.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Get form input values.
    $city = $form_state->getValue('city');
    $api_key = $form_state->getValue('api_key');
    $use_ip = $form_state->getValue('use_ip');

    // 1. Ensure API key is provided.
    // The API key is required for all requests to the OpenWeather API.
    if (empty($api_key)) {
      $form_state->setErrorByName('api_key', $this->t('API key is missing.'));
    }

    // 2. Ensure city name is provided when IP detection is disabled.
    // If the user chooses not to use IP-based location detection,
    // they must manually specify a city name.
    if (empty($city) && !$use_ip) {
      $form_state->setErrorByName('city', $this->t('City name is missing.'));
    }

    // 3. Validate city name format (letters, spaces, hyphens only).
    // Skip this check if IP detection is enabled.
    if (!preg_match('/^[a-zA-Z\s\-]+$/u', $city) && !empty($city)) {
      $form_state->setErrorByName('city', $this->t('City name must contain only letters.'));
    }

    // 4. Validate API key by sending a test request.
    // The form uses the OpenWeather client service to confirm the key is valid.
    if (!$this->weatherClient->testApiKey($api_key)) {
      $form_state->setErrorByName('api_key', $this->t('API key isn’t working.'));
    }

    // 5. Check whether the provided city is supported by the API.
    // This step is skipped if IP detection is enabled.
    if (!$this->weatherClient->getWeatherByCityName($city) && !empty($city)) {
      $form_state->setErrorByName('city', $this->t('The city is not supported.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('weather.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('city', $form_state->getValue('city'))
      ->set('use_ip', (bool) $form_state->getValue('use_ip'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
