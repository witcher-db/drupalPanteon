<?php

namespace Drupal\custom_register\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the login modal form.
 */
class LoginModalForm extends FormBase {
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
    $instance->formValidator = $container->get('custom_register.custome_form_validator');
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
    $form['#prefix'] = '<div id="login-modal-wrapper">';
    $form['#suffix'] = '</div>';

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

    $form['messages'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'login-modal-messages',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $email_error = $this->formValidator->isEmailRegistred($form_state, 'email');
    $password_error = $this->formValidator->comparePassword($form_state, 'password', 'email');

    if ($email_error) {
      $response->addCommand(new HtmlCommand('#email-validation-message', '<div style="color: red;" class="messages messages--error">' . $email_error . '</div>'));
      return $response;
    }
    else {
      $response->addCommand(new HtmlCommand('#email-validation-message', ''));
    }

    if ($password_error) {
      $response->addCommand(new HtmlCommand('#password-validation-message', '<div style="color: red;" class="messages messages--error">' . $password_error . '</div>'));
      return $response;
    }
    else {
      $response->addCommand(new HtmlCommand('#password-validation-message', ''));
    }

    if (!$email_error && !$password_error) {
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
