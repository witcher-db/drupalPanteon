<?php

namespace Drupal\user_statistics;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the UserStatistics entity.
 */
class UserStatisticsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess($entity, $operation, AccountInterface $account) {
    if ($account->hasPermission('view and edit all user statistics')) {
      return AccessResult::allowed()->addCacheContexts(['user.permissions']);
    }
    if ($account->hasPermission('view and edit own user statistics') && $entity->get('uid')->target_id === $account->id()) {
      return AccessResult::allowed()->addCacheContexts(['user.permissions']);
    }
    return AccessResult::forbidden()->addCacheContexts(['user.permissions']);
  }

}
