<?php

namespace Drupal\group_test_content\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * String identified content entity type for GroupContent tests.
 *
 * @ContentEntityType(
 *   id = "group_test_content_entity_string",
 *   label = @Translation("String identified test content entity type"),
 *   base_table = "group_test_content_entity_string",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class StringContent extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields[$entity_type->getKey('id')] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('String ID'))
      ->setSetting('is_ascii', TRUE);

    return $fields;
  }

}
