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
   * Validates an email address.
   *
   * This method performs two checks:
   *  1. Checks if the email format is valid using EmailValidator service.
   *  2. Checks if the email already exists in the user database.
   *
   * @param string $email
   *   The email address to validate.
   *
   * @return array
   *   An array containing:
   *    -[0]: A message string describing the validation result.
   *    -[1]: A boolean indicating if the email is valid (TRUE) or not (FALSE).
   */
  public function validateEmail($email) {
    if (!$this->emailValidator->isValid($email)) {
      return ['Invalid email format.', FALSE];
    }

    $user_storage = $this->entityTypeManager->getStorage('user');
    $existing_users = $user_storage->loadByProperties(['mail' => $email]);

    if (empty($existing_users)) {
      return ['Email is valid.', TRUE];
    }
    else {
      return ['This email is already registered.', FALSE];
    }
  }

  /**
   * AJAX callback for validating the email field on blur.
   *
   * This method is triggered when the user leaves (blurs) the email input.
   * It calls validateEmail() to check format and uniqueness, then returns
   * an AJAX response with a color-coded message:
   *   - Green for valid email.
   *   - Red for invalid or already registered email.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AjaxResponse object containing the message HTML.
   */
  public function validateEmailAjax(array &$form, FormStateInterface $form_state) {
    $colorCodes = [TRUE => 'green', FALSE => 'red'];
    $response = new AjaxResponse();
    $email = $form_state->getValue('email');
    $message = $this->validateEmail($email);

    // Wrap the message in a colored <span>.
    $template = '<span style="color:' . $colorCodes[$message[1]] . ';">' . $message[0] . '.</span>';

    $response->addCommand(new HtmlCommand('#email-validation-message', $template));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email');
    $message = $this->validateEmail($email);

    if (!$message[1]) {
      $form_state->setErrorByName('email', $this->t($message[0]));
      return;
    }
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
