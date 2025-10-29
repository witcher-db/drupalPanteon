<?php

namespace Drupal\user_statistics\EventSubscriber;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user_statistics\Event\NodeCustomEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Event subscriber for tracking user statistics on node actions.
 *
 * This subscriber listens to custom node events (edit and view)
 * and records the corresponding statistics in the 'user_statistics' entity.
 */
class UserStatisticsSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager service.
   *
   * Used to get the storage handler for custom entities.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The time service.
   *
   * Used to get the current request timestamp.
   */
  protected TimeInterface $time;

  /**
   * The current user service.
   *
   * Used to get the currently logged-in user ID.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    TimeInterface $time,
    AccountProxyInterface $current_user,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->time = $time;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   *
   * Returns an array of events this subscriber wants to listen to.
   *
   * @return array
   *   Event names as keys and corresponding callback methods as values.
   */
  public static function getSubscribedEvents() {
    return [
      NodeCustomEvent::EDIT => 'onEntityUpdate',
      NodeCustomEvent::VIEW => 'onEntityView',
    ];
  }

  /**
   * Handles node edit events.
   *
   * Records an 'edit' action in the user_statistics entity.
   *
   * @param \Drupal\user_statistics\Event\NodeCustomEvent $event
   *   The dispatched event containing the node.
   */
  public function onEntityUpdate(NodeCustomEvent $event) {
    $node = $event->node;

    $storage = $this->entityTypeManager->getStorage('user_statistics');

    $nid = $node->id();
    $uid = $node->getRevisionUserId();

    $storage->create([
      'uid' => $uid,
      'node_id' => $nid,
      'action_type' => 'edit',
      'timestamp' => $this->time->getRequestTime(),
    ])->save();
  }

  /**
   * Handles node view events.
   *
   * Records a 'view' action in the user_statistics entity.
   *
   * @param \Drupal\user_statistics\Event\NodeCustomEvent $event
   *   The dispatched event containing the node.
   */
  public function onEntityView(NodeCustomEvent $event) {
    $node = $event->node;

    $storage = $this->entityTypeManager->getStorage('user_statistics');

    $nid = $node->id();
    $uid = $this->currentUser->id();

    $storage->create([
      'uid' => $uid,
      'node_id' => $nid,
      'action_type' => 'view',
      'timestamp' => $this->time->getRequestTime(),
    ])->save();
  }

}
