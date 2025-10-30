<?php

namespace Drupal\drush_news_update_title\Commands;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Attributes as CLI;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for updating news titles.
 */
class DrushNewsUpdateTitleCommands extends DrushCommands {
  use AutowireTrait;

  /**
   * Constructs a new DrushNewsUpdateTitleCommands object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManagerInterface
   *   The entity type manager service, used to load and query entities.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheDiscovery
   *   The cache discovery service, used to clear cached field definitions.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManagerInterface,
    protected CacheBackendInterface $cacheDiscovery,
  ) {
    parent::__construct();
  }

  /**
   * Gets the entity type manager service.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  public function getEntity(): EntityTypeManagerInterface {
    return $this->entityTypeManagerInterface;
  }

  /**
   * Gets the cache discovery service.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface
   *   The cache discovery backend service.
   */
  public function getCache(): CacheBackendInterface {
    return $this->cacheDiscovery;
  }

  /**
   * Updates news nodes: copies title to field_custom_title.
   */
  #[CLI\Command(name: 'drush_news_update_title:update', aliases: ['nut'])]
  public function updateTitles(): void {
    $node_storage = $this->getEntity()->getStorage('node');
    $this->getCache()->deleteAll();

    $nids = $node_storage
      ->getQuery()
      ->condition('type', 'news')
      ->accessCheck(FALSE)
      ->execute();

    $this->output()->writeln('Found ' . count($nids) . ' news nodes.');

    foreach ($nids as $nid) {
      $node = $node_storage->load($nid);
      if (empty($node->field_custom_title->value)) {
        $node->set('field_custom_title', $node->getTitle());
        $node_storage->save($node);
        $this->output()->writeln('Updated node #$nid: ' . $node->getTitle());
      }
    }

    $this->logger()->success('All news nodes updated successfully.');
  }

  /**
   * Updates news nodes: copies title to field_custom_title using batch.
   */
  #[CLI\Command(name: 'drush_news_update_title:batch_update', aliases: ['nutb'])]
  public function testBatch(): void {
    $node_storage = $this->getEntity()->getStorage('node');
    $nids = $node_storage
      ->getQuery()
      ->condition('type', 'news')
      ->accessCheck(FALSE)
      ->execute();

    $operations = [];
    foreach ($nids as $nid) {
      $operations[] = [self::class . '::batchUpdateNode', [$nid]];
    }

    $batch = [
      'title' => 'Test Batch',
      'operations' => $operations,
      'init_message' => 'Batch starting...',
      'error_message' => 'An error occurred.',
    ];

    batch_set($batch);
    drush_backend_batch_process();
    $this->output()->writeln('Batch finished.');
  }

  /**
   * Batch callback function.
   */
  public static function batchUpdateNode($nid, array &$context) {
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    $node = $node_storage->load($nid);
    if (empty($node->get('field_custom_title')->value)) {
      $node->set('field_custom_title', $node->getTitle());
      $node_storage->save($node);
      $context['message'] = "Updated node #$nid";
    }
  }

}
