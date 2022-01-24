<?php

/**
 * @file
 * Contains Views hooks.
 */

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Field\GroupContentReferenceDefinition;

/**
 * Implements hook_views_data_alter().
 */
function group_views_data_alter(array &$data) {
  $entity_type_manager = \Drupal::entityTypeManager();
  $entity_types = $entity_type_manager->getDefinitions();

  /** @var \Drupal\group\Entity\Storage\GroupContentStorageInterface $group_content_storage */
  $group_content_storage = $entity_type_manager->getStorage('group_content');
  if (!$group_content_storage instanceof SqlContentEntityStorage) {
    // Unlike \Drupal\group\Entity\Views\GroupContentViewsData::getViewsData(),
    // the entity storage is not guaranteed to use SQL backend. Skip processing
    // in this case since the code below is SQL specific.
    return;
  }

  /** @var \Drupal\Core\Entity\Sql\TableMappingInterface $table_mapping */
  $table_mapping = $group_content_storage->getTableMapping();

  /** @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager */
  $plugin_manager = \Drupal::service('plugin.manager.group_content_enabler');

  /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
  foreach ($plugin_manager->getAll() as $plugin) {
    $entity_type_id = $plugin->getEntityTypeId();
    if (!isset($entity_types[$entity_type_id])) {
      continue;
    }
    $entity_type = $entity_types[$entity_type_id];
    $entity_data_table = $entity_type->getDataTable() ?: $entity_type->getBaseTable();

    if (isset($data[$entity_data_table]['group_content'])) {
      // Skip further processing if the relationship is already defined by a
      // different plugin.
      continue;
    }

    $field_name = GroupContent::getEntityFieldNameForEntityType($entity_type_id);
    $field_definition = $field_name === 'entity_id'
      ? GroupContentReferenceDefinition::createNumericalReference()
      : GroupContentReferenceDefinition::createStringReference();

    // This relationship will allow a content entity to easily map to the group
    // content entity that ties it to a group, optionally filtering by plugin.
    $t_args = ['@entity_type' => $entity_type->getLabel()];
    $data[$entity_data_table]['group_content'] = [
      'title' => t('Group content for @entity_type', $t_args),
      'help' => t('Relates to the group content entities that represent the @entity_type.', $t_args),
      'relationship' => [
        'group' => t('Group content'),
        'base' => $group_content_storage->getDataTable(),
        'base field' => 'id',
        'entity_type' => 'group_content',
        'field table' => 'group_content__' . $field_name,
        'field field' => $table_mapping->getFieldColumnName($field_definition->getFieldStorageDefinition(), 'target_id'),
        'field_name' => $field_name,
        'relationship field' => $entity_type->getKey('id'),
        'id' => 'group_content_to_entity_reverse',
        'label' => t('@entity_type group content', $t_args),
      ],
    ];
  }
}
