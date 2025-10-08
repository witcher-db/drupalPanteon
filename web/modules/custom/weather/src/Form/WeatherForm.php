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
    $city = $form_state->getValue('city');
    $api_key = $form_state->getValue('api_key');

    if (empty($api_key)) {
      $form_state->setErrorByName('custom_weather_block', $this->t('API key is missing'));
    }

    if (!preg_match('/^[a-zA-Z\s\-]+$/u', $city)) {
      $form_state->setErrorByName('custom_weather_block', $this->t('City name must contain only letters'));
    }

    // Test for valid API key.
    echo empty($this->weatherClient->testApiKey($api_key));
    if (empty($this->weatherClient->testApiKey($api_key))) {
      $form_state->setErrorByName('custom_weather_block', $this->t('API key isn`t working'));
    }

    // Test for support of the city by API.
    if (empty($this->weatherClient->getWeatherByCityName($city))) {
      $form_state->setErrorByName('custom_weather_block', $this->t('City is not sported'));
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
