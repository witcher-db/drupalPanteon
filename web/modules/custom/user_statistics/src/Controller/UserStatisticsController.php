<?php

namespace Drupal\user_statistics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for managing User Statistics.
 */
class UserStatisticsController extends ControllerBase {
  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs the controller.
   */
  public function __construct(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * Clears all user statistics.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects back to the entity list page.
   */
  public function clearAll() {
    if ($this->currentUser->hasPermission('view and edit all user statistics')) {
      $storage = $this->entityTypeManager()->getStorage('user_statistics');

      $query = $storage->getQuery()
        ->accessCheck(TRUE);

      $ids = $query->execute();

      $entities = $storage->loadMultiple($ids);
      $storage->delete($entities);

      $this->messenger()->addStatus($this->t('All user statistics have been deleted.'));
    }
    else {
      $this->messenger()->addError($this->t('You do not have permission to clear user statistics.'));
    }

    return new RedirectResponse(Url::fromRoute('entity.user_statistics.collection')->toString());
  }

}
