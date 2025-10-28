<?php

namespace Drupal\drush_news_update_title\Commands;

use Drupal\Core\Utility\Token;
use Drupal\node\NodeStorageInterface;
use Drupal\auto_entitylabel\AutoEntityLabelManager;
use Drush\Attributes as CLI;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for updating News node titles via Auto Entity Label.
 */
final class DrushNewsUpdateTitleCommands extends DrushCommands {

  use AutowireTrait;

  /**
   * Constructs the DrushNewsUpdateTitleCommands object.
   */
  public function __construct(
    private readonly Token $token,
    private readonly AutoEntityLabelManager $autoEntityLabelGenerator,
    private readonly NodeStorageInterface $nodeStorage,
  ) {
    parent::__construct();
  }

  /**
   * Update titles for all news nodes via Auto Entity Label.
   */
  #[CLI\Command(name: 'drush_news_update_title:update', aliases: ['nut'])]
  #[CLI\Usage(name: 'drush_news_update_title:update', description: 'Update all news node titles using Auto Entity Label.')]
  public function updateTitles(): void {
    $nids = $this->nodeStorage->getQuery()
      ->condition('type', 'news')
      ->execute();

    $this->output()->writeln("Found " . count($nids) . " news nodes.");

    foreach ($nids as $nid) {
      $node = $this->nodeStorage->load($nid);
      if ($node) {
        $this->autoEntityLabelGenerator->setLabel($node, 'title');
        $this->nodeStorage->save($node);
        $this->output()->writeln("Updated node #$nid: " . $node->getTitle());
      }
    }

    $this->logger()->success('All news nodes updated successfully.');
  }

}
