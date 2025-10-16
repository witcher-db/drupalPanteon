<?php

namespace Drupal\custom_register\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the basic Custom Register form.
 */
class CustomRegisterForm extends FormBase {
  /**
   * Email validation.
   *
   * @var Drupal\Core\Mail\EmailValidatorInterface
   */
  protected $emailValidator;
  /**
   * The mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->emailValidator = $container->get('email.validator');
    $instance->mailManager = $container->get('plugin.manager.mail');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

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
      '#ajax' => [
        'callback' => '::validateEmailAjax',
        'event' => 'blur',
        'wrapper' => 'email-validation-message',
      ],
    ];

    $form['email_message'] = [
      '#type' => 'markup',
      '#markup' => '<div id="email-validation-message"></div>',
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
   * AJAX callback for validating the email field on blur.
   *
   * This function is triggered when the user leaves (blurs) the email input.
   * It performs the following checks:
   *   1. Validates the email format using Drupal's EmailValidator service.
   *   2. (To be implemented) Checks the uniqueness of the email in the database.
   *
   * Depending on the result, it returns an appropriate message to the user:
   *   - Red message if the email format is invalid.
   *   - Green message if the email is valid.
   *
   * The message is injected dynamically into the page using an AjaxResponse
   * and the HtmlCommand, targeting the element with ID 'email-validation-message'.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An Ajax response containing the validation message.
   */
  public function validateEmailAjax(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $email = $form_state->getValue('email');
    $message = '';

    if (!$this->emailValidator->isValid($email)) {
      $message = '<span style="color:red;">Invalid email format.</span>';
    }
    else {
      $user_storage = $this->entityTypeManager->getStorage('user');
      $existing_users = $user_storage->loadByProperties(['mail' => $email]);

      if (empty($existing_users)) {
        $message = '<span style="color:green;">Email is valid.</span>';
      }
      else {
        $message = '<span style="color:red;">This email is already registered.</span>';
      }
    }

    $response->addCommand(new HtmlCommand('#email-validation-message', $message));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email');
    $username = $form_state->getValue('username');
    $additional = $form_state->getValue('additional');
    $age = $form_state->getValue('age');
    $country = $form_state->getValue('country');
    $about = $form_state->getValue('about');

    if (!$this->emailValidator->isValid($email)) {
      $this->messenger()->addError($this->t('Invalid email address.'));
      return;
    }

    $user_storage = $this->entityTypeManager->getStorage('user');
    $existing_users = $user_storage->loadByProperties(['mail' => $email]);

    if ($existing_users) {
      $this->messenger()->addError($this->t('This email is already registered.'));
      return;
    }

    $module = 'custom_register';
    $key = 'registration_confirmation';
    $lan = 'en';
    $params['subject'] = $this->t('Registration confirmation');
    if ($additional) {
      $params['message'] = $this->t('Email: @email, Username: @username, Age: @age Country: @country, About: @about',
        ['@email' => $email, '@username' => $username, '@country' => $country, '@age' => $age, '@about' => $about]
      );
    }
    else {
      $params['message'] = $this->t('Email: @email, Username: @username',
        ['@email' => $email, '@username' => $username]
      );
    }

    $result = $this->mailManager->mail($module, $key, $email, $lan, $params, NULL, TRUE);

    if ($result['result'] !== TRUE) {
      $this->messenger()->addError($this->t('There was a problem sending your confirmation email.'));
    }
    else {
      $this->messenger()->addStatus($this->t('A confirmation email has been sent to @email.', ['@email' => $email]));
    }
  }

}
