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
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;
  /**
   * The custom form validator service.
   *
   * @var \Drupal\custom_register\Service\CustomeFormValidator
   */
  protected $formValidator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->database = $container->get('database');
    $instance->emailValidator = $container->get('email.validator');
    $instance->formValidator = $container->get('custom_register.custome_form_validator');
    $instance->mailManager = $container->get('plugin.manager.mail');
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
      '#max' => 15,
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
      '#min' => 8,
      '#max' => 32,
    ];

    $form['confirm_password'] = [
      '#type' => 'password',
      '#title' => $this->t('Confirm password'),
      '#required' => TRUE,
      '#min' => 8,
      '#max' => 32,
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
      '#states' => [
        'visible' => [
          ':input[name="additional"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="additional"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['country'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Country'),
      '#states' => $states,
      '#max' => 32,
    ];

    $form['about'] = [
      '#type' => 'textarea',
      '#title' => $this->t('About yourself'),
      '#states' => $states,
      '#max' => 256,
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
   * This method is triggered when the user leaves (blurs) the email input.
   * It calls getEmailValidationInfo() to check format and uniqueness,
   * then returns an AJAX response with a color-coded message:
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
    $validation_array = $this->formValidator->getEmailValidationInfo($email);

    // Wrap the message in a colored <span>.
    $template = '<span style="color:' .
    $colorCodes[$validation_array['is_valid']] .
    ';">' .
    $validation_array['message'] . '.</span>';

    $response->addCommand(new HtmlCommand('#email-validation-message', $template));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $validation_array = $this->formValidator->getEmailValidationInfo($form_state->getValue('email'));

    if (!$validation_array['is_valid']) {
      $form_state->setErrorByName('email', $validation_array['message']);
    }

    $this->formValidator->passwordValidationError($form_state, 'password', 'confirm_password');
    $this->formValidator->stringValidationError($form_state, 'username', 32, TRUE);
    if ($form_state->getValue('additional')) {
      $this->formValidator->ageValidationError($form_state, 'age');
      $this->formValidator->stringValidationError($form_state, 'country', 32, FALSE);
      $this->formValidator->stringValidationError($form_state, 'country', 256, FALSE);
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
    $password = $form_state->getValue('password');
    $fields = [
      'username' => $username,
      'password' => password_hash($password, PASSWORD_DEFAULT),
      'email' => $email,
      'age' => (!empty($age) ? (int) $age : NULL),
      'country' => $country ?? NULL,
      'about' => $about ?? NULL,
    ];

    try {
      $this->database->insert('custom_user_data')
        ->fields($fields)
        ->execute();
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Failed to save user data: @message', ['@message' => $e->getMessage()]));
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
