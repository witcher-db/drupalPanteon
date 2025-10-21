<?php

namespace Drupal\custom_register\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Login Link' block.
 */
#[Block(
  id: "custom_register_login_link_block",
  admin_label: new TranslatableMarkup("Login Link Block"),
  category: new TranslatableMarkup("Custom")
)]
class LoginLinkBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  public function __construct(
    $configuration,
    $plugin_id,
    $plugin_definition,
    AccountProxyInterface $currentUser,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($this->currentUser->isAuthenticated()) {
      return [];
    }

    $url = Url::fromRoute('custom_register.login_modal');
    $url->setOptions([
      'attributes' => [
        'class' => ['use-ajax', 'login-link'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => json_encode(['width' => 400]),
      ],
    ]);

    $link = Link::fromTextAndUrl($this->t('Login'), $url)->toRenderable();

    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['login-link-container', 'btn', 'btn-primary', 'px-3']],
      'link' => $link,
      '#attached' => [
        'library' => ['core/drupal.dialog.ajax'],
      ],
    ];
  }

}
