<?php

namespace Drupal\custom_register\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the basic Custom Register form.
 */
class CustomRegisterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_register_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#required' => TRUE,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
    ];

    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#required' => TRUE,
    ];

    $form['confirm_password'] = [
      '#type' => 'password',
      '#title' => $this->t('Confirm password'),
      '#required' => TRUE,
    ];

    $form['additional'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Additional information'),
    ];

    // Using Drupal Form API #states to dynamically control field visibility.
    // Here we make the "Age", "Country", and "About yourself" fields visible
    // only when the "Additional information" checkbox is checked.
    $states = [
      'visible' => [
        ':input[name="additional"]' => ['checked' => TRUE],
      ],
    ];

    $form['age'] = [
      '#type' => 'number',
      '#title' => $this->t('Age'),
      '#min' => 0,
      '#max' => 150,
      '#states' => $states,
    ];

    $form['country'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Country'),
      '#states' => $states,
    ];

    $form['about'] = [
      '#type' => 'textarea',
      '#title' => $this->t('About yourself'),
      '#states' => $states,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo implement submission and validation logic
  }

}
