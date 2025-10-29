<?php

namespace Drupal\custom_register\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'Login Link' block.
 */
#[Block(
  id: 'custom_register_login_link_block',
  admin_label: new TranslatableMarkup('Login Link Block'),
  category: new TranslatableMarkup('Custom')
)]
class LoginLinkBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * Request stack that controls the lifecycle of requests.
   */
  protected RequestStack $requestStack;

  public function __construct(
    $configuration,
    $plugin_id,
    $plugin_definition,
    RequestStack $requestStack,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $request = $this->requestStack->getCurrentRequest();
    $user_email = $request->cookies->get('custom_user_email');

    if ($user_email) {
      return [
        '#cache' => [
          'contexts' => ['cookies:custom_user_email'],
        ],
      ];
    }

    return [
      '#type' => 'link',
      '#title' => $this->t('Login'),
      '#attributes' => [
        'class' => ['btn', 'btn-primary', 'px-3'],
      ],
      '#url' => Url::fromRoute('custom_register.login_modal')->setOptions([
        'attributes' => [
          'class' => ['use-ajax', 'login-link'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => json_encode(['width' => 400]),
        ],
      ]),
      '#cache' => ['contexts' => ['cookies:custom_user_email']],
      '#attached' => ['library' => ['core/drupal.dialog.ajax']],
    ];
  }

}
