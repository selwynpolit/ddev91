<?php

namespace Drupal\group\Field;

use Drupal\Core\Field\BaseFieldDefinition;

/**
 * A custom field storage definition class.
 *
 * This class is used as a workaround until core issue is fixed.
 *
 * @todo Implement FieldStorageDefinition (https://www.drupal.org/node/2280639).
 */
class GroupContentReferenceDefinition extends BaseFieldDefinition {

  /**
   * {@inheritdoc}
   */
  public static function create($type) {
    if ($type !== 'entity_reference') {
      throw new \LogicException('GroupContentReferenceDefinition should always be of type entity_reference');
    }
    return parent::create($type)
      ->setTargetEntityTypeId('group_content')
      ->setTargetBundle(NULL)
      ->setLabel(t('Content'))
      ->setDescription(t('The entity to add to the group.'))
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setRequired(TRUE);
  }

  /**
   * Creates a new group content reference field definition for numerical IDs.
   *
   * @return static
   *   A new group content reference field definition object.
   */
  public static function createNumericalReference() {
    return static::create('entity_reference')->setName('entity_id');
  }

  /**
   * Creates a new group content reference field definition for string IDs.
   *
   * @return static
   *   A new group content reference field definition object.
   */
  public static function createStringReference() {
    return static::create('entity_reference')
      ->setName('entity_id_str')
      // This can be replaced by the right entity type when defining the group
      // content bundle fields, but needs to be set to a string ID entity type
      // for \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem to
      // do the hard work for us.
      ->setSetting('target_type', 'menu');
  }

  /**
   * {@inheritdoc}
   */
  public function isBaseField() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchema() {
    if (!isset($this->schema)) {
      parent::getSchema();

      // Entity reference items usually determine the schema column length by
      // the target entity type. If it's a bundle entity type, the length is far
      // shorter (32) than for other entity types (255). We can't know for sure
      // what the target entity type will be, so we forcibly set it to 255.
      //
      // This is essentially also achieved by setting the target_type to menu in
      // ::createStringReference(), but we can't rely on the fact that the menu
      // entity type does not act as a bundle as that behavior might change in
      // future Drupal releases.
      if ($this->schema['columns']['target_id']['type'] == 'varchar_ascii') {
        $this->schema['columns']['target_id']['length'] = 255;
      }
    }

    return $this->schema;
  }

}
