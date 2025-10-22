<?php

namespace Drupal\custom_register\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the login modal form.
 */
class LoginModalForm extends FormBase {
  /**
   * The custom form validator service.
   *
   * @var \Drupal\custom_register\Service\CustomFormValidator
   */
  protected $customFormValidator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->customFormValidator = $container->get('custom_register.custom_form_validator');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_register_login_modal_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
    ];

    $form['email_error'] = [
      '#type' => 'markup',
      '#markup' => '<div id="email-validation-message"></div>',
    ];

    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#required' => TRUE,
    ];

    $form['password_error'] = [
      '#type' => 'markup',
      '#markup' => '<div id="password-validation-message"></div>',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Login'),
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * Handles the AJAX submission of the login modal form.
   *
   * This method performs the following steps:
   * 1. Creates a new AjaxResponse object to send AJAX commands to the client.
   * 2. Uses the custom form validator service to check:
   *    - Whether the provided email is registered.
   *    - Whether the provided password matches the stored hash.
   * 3. Clears any previous validation messages from the form.
   * 4. If the email or password validation fails, adds an HTML command
   *    to display an error message next to the corresponding field
   *    and returns the response immediately.
   * 5. If both validations pass, adds a command to close the modal dialog
   *    and reload the page.
   *
   * @param array $form
   *   The renderable form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state containing submitted values.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response object containing commands to update the page.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $email_error = $this->customFormValidator->isEmailRegistred($form_state, 'email');
    $password_error = $this->customFormValidator->comparePassword($form_state, 'password', 'email');

    $response->addCommand(new HtmlCommand('#email-validation-message', ''));
    $response->addCommand(new HtmlCommand('#password-validation-message', ''));

    if ($email_error) {
      $response->addCommand(new HtmlCommand('#email-validation-message', '<div style="color: red;" class="messages messages--error">' . $email_error . '</div>'));
      return $response;
    }

    if ($password_error) {
      $response->addCommand(new HtmlCommand('#password-validation-message', '<div style="color: red;" class="messages messages--error">' . $password_error . '</div>'));
      return $response;
    }

    $response->addCommand(new CloseModalDialogCommand());

    setcookie('custom_user_email', $form_state->getValue('email'), time() + 3600 * 4, '/');

    $current_url = Url::fromRoute('<front>')->toString();

    $response->addCommand(new RedirectCommand($current_url));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
