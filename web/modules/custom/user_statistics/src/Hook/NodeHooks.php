<?php

namespace Drupal\user_statistics\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\user_statistics\Event\NodeCustomEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Drupal\node\NodeInterface;

/**
 * OOP hook implementations for user_statistics module.
 */
final class NodeHooks {

  /**
   * Event dispatcher service.
   */
  private EventDispatcherInterface $eventDispatcher;

  /**
   * Initializes the NodeHooks service.
   *
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher service injected via dependency injection.
   */
  public function __construct(EventDispatcherInterface $eventDispatcher) {
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * OOP implementation of hook_node_update().
   *
   * Triggered whenever a node is updated. If the node is of type "news",
   * a custom NodeCustomEvent::EDIT event is created and dispatched.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node being updated.
   */
  #[Hook('node_update')]
  public function userStatisticsNodeUpdate(NodeInterface $node): void {
    if ($node->bundle() === 'news') {
      $event = new NodeCustomEvent($node);
      $this->eventDispatcher->dispatch($event, NodeCustomEvent::EDIT);
    }
  }

  /**
   * OOP implementation of hook_node_view().
   *
   * Triggered whenever a node is being viewed. If the node is of type "news",
   * a custom NodeCustomEvent::VIEW event is created and dispatched.
   *
   * @param array $build
   *   The render array for the node.
   * @param \Drupal\node\NodeInterface $node
   *   The node being viewed.
   * @param mixed $view_mode
   *   The view mode of the node.
   *   Can be a string or a LayoutBuilderEntityViewDisplay object.
   */
  #[Hook('node_view')]
  public function userStatisticsNodeView(array &$build, NodeInterface $node, $view_mode): void {
    if ($node->bundle() === 'news') {
      $event = new NodeCustomEvent($node);
      $this->eventDispatcher->dispatch($event, NodeCustomEvent::VIEW);

      // @todo This is not the best implementation.
      // Better way is create AJAX callback function.
      $build['#cache']['max-age'] = 0;
    }
  }

}
