<?php

namespace Drupal\user_statistics\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a UserStatistics entity.
 */
class UserStatisticsDeleteForm extends ContentEntityDeleteForm {

  /**
   * The current user service.
   *
   * Used to determine if the user is admin
   * or should see only their own records.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Constructs a new UserStatisticsListBuilder.
   */
  public static function create(ContainerInterface $container): self {
    $form = parent::create($container);
    $form->currentUser = $container->get('current_user');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->getEntity()->toUrl('collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   *
   * Controls who can access this delete form.
   * - Admins (permission: 'view and edit all user statistics') can delete any.
   * - Regular users (permission: 'view and edit own user statistics')
   *  can delete only their own.
   */
  public function access() {
    $entity = $this->getEntity();
    $uid = $entity->get('uid')->target_id;

    // Admin permission — can delete any entity.
    if ($this->currentUser->hasPermission('view and edit all user statistics')) {
      return AccessResult::allowed();
    }

    if ($this->currentUser->hasPermission('view and edit own user statistics') && $this->currentUser->id() === $uid) {
      return AccessResult::allowed();
    }

    // Otherwise — access denied.
    return AccessResult::forbidden();
  }

}
