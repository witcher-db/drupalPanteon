<?php

namespace Drupal\custom_register\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Provides the basic Custom Register form.
 */
class CustomeFormValidator {
  /**
   * Email validation.
   *
   * @var Drupal\Core\Mail\EmailValidatorInterface
   */
  protected $emailValidator;
  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * {@inheritdoc}
   */
  public function __construct($email_validator, Connection $database, TranslationInterface $string_translation) {
    $this->emailValidator = $email_validator;
    $this->database = $database;
    $this->stringTranslation = $string_translation;
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
        'message' => $this->stringTranslation->translate('Invalid email format'),
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
        'message' => $this->stringTranslation->translate('Email is valid'),
      ];
    }
    else {
      return [
        'is_valid' => FALSE,
        'message' => $this->stringTranslation->translate('This email is already registered'),
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
        $this->stringTranslation->translate('Password must be between 8 and 32 characters long'),
      );
      $form_state->setErrorByName(
        $confirmPasswordKey,
        $this->stringTranslation->translate('Password must be between 8 and 32 characters long'),
      );
      return;
    }

    if ($password !== $confirmPassword) {
      $form_state->setErrorByName(
        $passwordKey,
        $this->stringTranslation->translate('Passwords do not match. Please confirm your password again'),
       );
      $form_state->setErrorByName(
        $confirmPasswordKey,
        $this->stringTranslation->translate('Passwords do not match. Please confirm your password again'),
      );
      return;
    }
  }

  /**
   * Validates the submitted password against the stored hash for a given email.
   *
   * Retrieves the password hash from the `custom_user_data`
   * table for the provided email
   * and verifies it using `password_verify()`.
   * If the database query fails or the password
   * does not match, a user-friendly message is returned.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object containing submitted values.
   * @param string $passwordKey
   *   The key of the password field in the form.
   * @param string $emailKey
   *   The key of the email field in the form.
   *
   * @return string|null
   *   Returns a translated error message if validation fails, otherwise NULL.
   */
  public function comparePassword($form_state, $passwordKey, $emailKey) {
    $password = $form_state->getValue($passwordKey);
    $email = $form_state->getValue($emailKey);

    try {
      $hash = $this->database->select('custom_user_data', 'c')
        ->fields('c', ['password'])
        ->condition('email', $email)
        ->execute()
        ->fetchField();
    }
    catch (\Exception $e) {
      return $this->stringTranslation->translate('An unexpected error occurred. Please try again later.');
    }

    if (!password_verify($password, $hash)) {
      return $this->stringTranslation->translate('The password you entered is incorrect.');
    }
  }

  /**
   * Checks if the provided email is registered in the custom user table.
   *
   * This method queries the `custom_user_data`
   * table to determine whether an account
   * exists with the given email address.
   * If the database query fails or no account
   * is found, a user-friendly message is returned.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object containing submitted values.
   * @param string $emailKey
   *   The key of the email field in the form.
   *
   * @return string|null
   *   Returns a translated error message if the email is not found
   *   or if a database error occurs, otherwise NULL.
   */
  public function isEmailRegistred($form_state, $emailKey) {
    $email = $form_state->getValue($emailKey);

    try {
      // Retrieve the stored password hash for the provided email.
      $existing_users = $this->database->select('custom_user_data', 'c')
        ->fields('c', ['id'])
        ->condition('email', $email)
        ->execute()
        ->fetchField();
    }
    catch (\Exception) {
      // If something goes wrong with the database query.
      return $this->stringTranslation->translate('An unexpected error occurred. Please try again later.');
    }

    if (empty($existing_users)) {
      return $this->stringTranslation->translate('No account found with this email address.');
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
        $this->stringTranslation->translate('This field cannot be empty.'),
      );
      return;
    }

    if (strlen($string) > $len) {
      $form_state->setErrorByName(
        $stringKey,
        $this->stringTranslation->translate('This field cannot exceed @len characters.', ['@len' => $len]),
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
        $this->stringTranslation->translate('Please enter a valid age (0–150).'),
      );
      return;
    }
  }

}
