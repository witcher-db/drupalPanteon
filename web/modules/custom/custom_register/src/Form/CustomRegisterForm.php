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
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->emailValidator = $container->get('email.validator');
    $instance->mailManager = $container->get('plugin.manager.mail');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->database = $container->get('database');
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
  public function getEmailValidationInfo($email) {
    if (!$this->emailValidator->isValid($email)) {
      return [
        'is_valid' => FALSE,
        'message' => $this->t('Invalid email format'),
      ];
    }

    $existing_users = $this->database->select('custom_user_data', 'c')
      ->fields('c', ['id'])
      ->condition('email', $email)
      ->execute()
      ->fetchField();

    if (empty($existing_users)) {
      return [
        'is_valid' => TRUE,
        'message' => $this->t('Email is getEmailValidationInfo'),
      ];
    }
    else {
      return [
        'is_valid' => FALSE,
        'message' => $this->t('This email is already registered'),
      ];
    }
  }

  /**
   * Validates the password and its confirmation field.
   *
   * This method ensures that the password meets the required length
   * and matches the confirmation value. Specifically:
   *   - The password must be between 8 and 32 characters long.
   *   - The password and confirmation must be identicalgetEmailValidationInfo
   * If validation fails, appropriate error messages are added
   * to both password fields in the form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state containing submitted values.
   * @param string $passwordKey
   *   The machine name of the password field.
   * @param string $confirmPasswordKey
   *   The machine name of the password confirmation field.
   *
   * @return void
   *   Adds validation errors to the form state if validation fails.
   */
  public function passwordValidationError($form_state, $passwordKey, $confirmPasswordKey) {
    $password = $form_state->getValue($passwordKey);
    $confirmPassword = $form_state->getValue($confirmPasswordKey);

    if (strlen($password) < 8 || strlen($password) > 32) {
      $form_state->setErrorByName(
        $passwordKey,
        $this->t('Password must be between 8 and 32 characters long'),
      );
      $form_state->setErrorByName(
        $confirmPasswordKey,
        $this->t('Password must be between 8 and 32 characters long'),
      );
      return;
    }

    if ($password !== $confirmPassword) {
      $form_state->setErrorByName(
        $passwordKey,
        $this->t('Passwords do not match. Please confirm your password again'),
       );
      $form_state->setErrorByName(
        $confirmPasswordKey,
        $this->t('Passwords do not match. Please confirm your password again'),
      );
      return;
    }
  }

  /**
   * Validates a generic string field.
   *
   * This method checks whether a string field meets the specified
   * validation rules:
   *   - Ensures the field is not empty (if $allowEmpty is FALSE).
   *   - Ensures the string length does not exceed the given limit ($len).
   *
   * If validation fails, it sets a corresponding error message in
   * the form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state containing submitted values.
   * @param string $stringKey
   *   The machine name of the string form field.
   * @param int $len
   *   The maximum allowed string length.
   * @param bool $allowEmpty
   *   Whether the field can be empty (TRUE to allow, FALSE to require).
   *
   * @return void
   *   Adds validation errors to the form state if the field is invalid.
   */
  public function stringValidationError($form_state, $stringKey, $len, $allowEmpty) {
    $string = $form_state->getValue($stringKey);

    if (!$allowEmpty && empty($string)) {
      $form_state->setErrorByName(
        $stringKey,
        $this->t('This field cannot be empty.'),
      );
      return;
    }

    if (strlen($string) > $len) {
      $form_state->setErrorByName(
        $stringKey,
        $this->t('This field cannot exceed @len characters.', ['@len' => $len]),
      );
      return;
    }
  }

  /**
   * Validates the user's age field.
   *
   * This method ensures that the provided age value is within
   * an acceptable range. It checks whether the field is empty
   * or exceeds a logical upper limit (150 years).
   * If validation fails, an error message is added to the form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state containing submitted values.
   * @param string $ageKey
   *   The machine name of the form field representing the age.
   *
   * @return void
   *   Adds a form validation error if the age is invalid.
   */
  public function ageValidationError($form_state, $ageKey) {
    $age = $form_state->getValue($ageKey);
    if (empty($age) || $age > 150) {
      $form_state->setErrorByName(
        $ageKey,
        $this->t('Please enter a valid age (0–150).'),
      );
      return;
    }
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
    $validation_array = $this->getEmailValidationInfo($email);

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
    $validation_array = $this->getEmailValidationInfo($form_state->getValue('email'));

    if (!$validation_array['is_valid']) {
      $form_state->setErrorByName('email', $validation_array['message']);
    }

    $this->passwordValidationError($form_state, 'password', 'confirm_password');
    $this->stringValidationError($form_state, 'username', 32, TRUE);
    if ($form_state->getValue('additional')) {
      $this->ageValidationError($form_state, 'age');
      $this->stringValidationError($form_state, 'country', 32, FALSE);
      $this->stringValidationError($form_state, 'country', 256, FALSE);
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
      'age' => ($age !== '' ? (int) $age : NULL),
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
