<?php

namespace Drupal\custom_register\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * OOP implementation of hook_mail for the custom_register module.
 */
class MailHook {

  /**
   * Handles hook_mail invocation.
   *
   * @param string $key
   *   The mail key.
   * @param array &$message
   *   The message array to be filled.
   * @param array $params
   *   The parameters passed to mail().
   */
  #[Hook('mail')]
  public function mail(string $key, array &$message, array $params) {
    $message['subject'] = $params['subject'];
    $message['body'][] = $params['message'];
  }

}
