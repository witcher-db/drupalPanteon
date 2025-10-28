<?php

namespace Drupal\user_statistics\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\node\Entity\Node;

/**
 * Event triggered when a node is viewed or edited.
 *
 * This class encapsulates the node that was acted upon and provides
 * constants for different event types (edit and view).
 */
class NodeCustomEvent extends Event {
  /**
   * Event name for node edit.
   */
  const EDIT = 'custom_events_node_edited';

  /**
   * Event name for node view.
   */
  const VIEW = 'custom_events_node_viewed';

  /**
   * The node object associated with this event.
   */
  public Node $node;

  /**
   * Constructs a NodeCustomEvent.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node object that this event represents.
   */
  public function __construct(Node $node) {
    $this->node = $node;
  }

}
