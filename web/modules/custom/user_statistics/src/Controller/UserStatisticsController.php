<?php

namespace Drupal\user_statistics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Controller for managing User Statistics.
 */
class UserStatisticsController extends ControllerBase {

  /**
   * Clears all user statistics.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects back to the entity list page.
   */
  public function clearAll() {
    $storage = $this->entityTypeManager()->getStorage('user_statistics');

    $query = $storage->getQuery()
      ->accessCheck(TRUE);

    $ids = $query->execute();

    if (!empty($ids)) {
      $entities = $storage->loadMultiple($ids);
      $storage->delete($entities);
    }

    $this->messenger()->addStatus($this->t('All user statistics have been deleted.'));

    return new RedirectResponse(Url::fromRoute('entity.user_statistics.collection')->toString());
  }

}
