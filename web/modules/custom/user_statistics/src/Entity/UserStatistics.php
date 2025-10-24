<?php

namespace Drupal\user_statistics\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the UserStatistics entity.
 *
 * This class represents an entity for logging user actions on the site.
 * The entity stores:
 * - the user who performed the action,
 * - the node that was interacted with,
 * - the type of action (edit, view, etc.),
 * - the timestamp of the action.
 */
#[ContentEntityType(
  id: 'user_statistics',
  label: new TranslatableMarkup('User statistics'),
  base_table: 'user_statistics',
  entity_keys: [
    'id' => 'id',
    'uid' => 'uid',
  ]
)]
class UserStatistics extends ContentEntityBase implements ContentEntityInterface {

  /**
   * Defines the base fields for the entity.
   *
   * Base fields always exist for each entity instance, unlike configurable
   * fields which can be added through the UI.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type object.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition[]
   *   An array of BaseFieldDefinition objects for the entity.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = [];

    // Entity ID, automatically generated and read-only.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setReadOnly(TRUE);

    // Reference to the user who performed the action.
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setSetting('target_type', 'user');

    // Reference to the node that the user interacted with.
    $fields['node_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Node'))
      ->setSetting('target_type', 'node');

    // Field for the action type, e.g., 'edit' or 'view'.
    $fields['action_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Action type'))
      ->setSettings(['max_length' => 16]);

    // Field for the timestamp of the action.
    $fields['datetime'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Datetime'));

    return $fields;
  }

}
