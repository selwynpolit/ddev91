<?php

/**
 * @file
 * Post update functions for Group.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\group\Entity\GroupType;
use Drupal\group\Entity\GroupContentType;
use Drupal\group\Entity\Storage\GroupStorage;
use Drupal\user\Entity\Role;

/**
 * Recalculate group type and group content type dependencies after moving the
 * plugin configuration from the former to the latter in group_update_8006().
 */
function group_post_update_group_type_group_content_type_dependencies() {
  foreach (GroupType::loadMultiple() as $group_type) {
    $group_type->save();
  }

  foreach (GroupContentType::loadMultiple() as $group_type) {
    $group_type->save();
  }
}

/**
 * Recalculate group content type dependencies after updating the group content
 * enabler base plugin dependency logic.
 */
function group_post_update_group_content_type_dependencies() {
  foreach (GroupContentType::loadMultiple() as $group_type) {
    $group_type->save();
  }
}

/**
 * Grant the new 'access group overview' permission.
 */
function group_post_update_grant_access_overview_permission() {
  /** @var \Drupal\user\RoleInterface $role */
  foreach (Role::loadMultiple() as $role) {
    if ($role->hasPermission('administer group')) {
      $role->grantPermission('access group overview');
      $role->save();
    }
  }
}

/**
 * Fix cache contexts in views.
 */
function group_post_update_view_cache_contexts(&$sandbox) {
  if (!\Drupal::moduleHandler()->moduleExists('views')) {
    return;
  }
  // This will trigger the catch-all fix in group_view_presave().
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'view', function ($view) {
    return TRUE;
  });
}

/**
 * Update groups to be revisionable.
 */
function group_post_update_make_group_revisionable(&$sandbox) {
  $definition_update_manager = \Drupal::entityDefinitionUpdateManager();
  /** @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $last_installed_schema_repository */
  $last_installed_schema_repository = \Drupal::service('entity.last_installed_schema.repository');

  $entity_type = $definition_update_manager->getEntityType('group');
  $field_storage_definitions = $last_installed_schema_repository->getLastInstalledFieldStorageDefinitions('group');

  // Update the entity type definition.
  $entity_keys = $entity_type->getKeys();
  $entity_keys['revision'] = 'revision_id';
  $entity_keys['revision_translation_affected'] = 'revision_translation_affected';
  $entity_type->set('entity_keys', $entity_keys);
  $entity_type->set('revision_table', 'groups_revision');
  $entity_type->set('revision_data_table', 'groups_field_revision');
  $revision_metadata_keys = [
    'revision_default' => 'revision_default',
    'revision_user' => 'revision_user',
    'revision_created' => 'revision_created',
    'revision_log_message' => 'revision_log_message',
  ];
  $entity_type->set('revision_metadata_keys', $revision_metadata_keys);

  // Some revision data does not get set by core properly, this fixes it.
  $entity_type->setHandlerClass('storage', GroupStorage::class);

  // Update the field storage definitions and add the new ones required by a
  // revisionable entity type.
  $field_storage_definitions['label']->setRevisionable(TRUE);
  $field_storage_definitions['uid']->setRevisionable(TRUE);
  $field_storage_definitions['langcode']->setRevisionable(TRUE);
  $field_storage_definitions['created']->setRevisionable(TRUE);
  $field_storage_definitions['changed']->setRevisionable(TRUE);

  $field_storage_definitions['revision_id'] = BaseFieldDefinition::create('integer')
    ->setName('revision_id')
    ->setTargetEntityTypeId('group')
    ->setTargetBundle(NULL)
    ->setLabel(new TranslatableMarkup('Revision ID'))
    ->setReadOnly(TRUE)
    ->setSetting('unsigned', TRUE);
  $field_storage_definitions['revision_default'] = BaseFieldDefinition::create('boolean')
    ->setName('revision_default')
    ->setTargetEntityTypeId('group')
    ->setTargetBundle(NULL)
    ->setLabel(new TranslatableMarkup('Default revision'))
    ->setDescription(new TranslatableMarkup('A flag indicating whether this was a default revision when it was saved.'))
    ->setStorageRequired(TRUE)
    ->setInternal(TRUE)
    ->setTranslatable(FALSE)
    ->setRevisionable(TRUE);
  $field_storage_definitions['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
    ->setName('revision_translation_affected')
    ->setTargetEntityTypeId('group')
    ->setTargetBundle(NULL)
    ->setLabel(new TranslatableMarkup('Revision translation affected'))
    ->setDescription(new TranslatableMarkup('Indicates if the last edit of a translation belongs to current revision.'))
    ->setReadOnly(TRUE)
    ->setRevisionable(TRUE)
    ->setTranslatable(TRUE);

  $field_storage_definitions['revision_created'] = BaseFieldDefinition::create('created')
    ->setName('revision_created')
    ->setTargetEntityTypeId('group')
    ->setTargetBundle(NULL)
    ->setLabel(new TranslatableMarkup('Revision create time'))
    ->setDescription(new TranslatableMarkup('The time that the current revision was created.'))
    ->setRevisionable(TRUE);
  $field_storage_definitions['revision_user'] = BaseFieldDefinition::create('entity_reference')
    ->setName('revision_user')
    ->setTargetEntityTypeId('group')
    ->setTargetBundle(NULL)
    ->setLabel(new TranslatableMarkup('Revision user'))
    ->setDescription(new TranslatableMarkup('The user ID of the author of the current revision.'))
    ->setSetting('target_type', 'user')
    ->setRevisionable(TRUE);
  $field_storage_definitions['revision_log_message'] = BaseFieldDefinition::create('string_long')
    ->setName('revision_log_message')
    ->setTargetEntityTypeId('group')
    ->setTargetBundle(NULL)
    ->setLabel(new TranslatableMarkup('Revision log message'))
    ->setDescription(new TranslatableMarkup('Briefly describe the changes you have made.'))
    ->setRevisionable(TRUE)
    ->setDefaultValue('');

  $definition_update_manager->updateFieldableEntityType($entity_type, $field_storage_definitions, $sandbox);

  return new TranslatableMarkup('Groups have been converted to be revisionable.');
}

/**
 * Restore the data for group_content entity_id.
 */
function group_post_update_restore_entity_id_data(&$sandbox) {
  $query = \Drupal::database()
    ->select('group_content_entity_id_update', 'g')
    ->fields('g', ['id', 'entity_id']);

  // Initialize the update process, install the field schema.
  if (!isset($sandbox['total'])) {
    $sandbox['total'] = $query->countQuery()->execute()->fetchField();
    $sandbox['current'] = 0;
  }

  // We're now inserting new fields data which may be tricky. We're updating
  // group_content entities instead of inserting fields data directly to make
  // sure field data is stored correctly.
  $rows_per_operation = 50;
  $query->condition('id', $sandbox['current'], '>');
  $query->range(0, $rows_per_operation);
  $query->orderBy('id', 'ASC');

  $rows = $query->execute()->fetchAllKeyed();
  if ($rows) {
    /** @var \Drupal\group\Entity\GroupContentInterface[] $group_contents */
    $group_contents = \Drupal::entityTypeManager()
      ->getStorage('group_content')
      ->loadMultiple(array_keys($rows));

    foreach ($group_contents as $id => $group_content) {
      $group_content->entity_id->target_id = $rows[$id];
      $group_content->save();
    }

    end($rows);
    $sandbox['current'] = key($rows);
    $moved_rows = Drupal::database()
      ->select('group_content__entity_id')
      ->countQuery()->execute()->fetchField();
    $sandbox['#finished'] = ($moved_rows / $sandbox['total']);
  }
  else {
    $sandbox['#finished'] = 1;
  }

  if ($sandbox['#finished'] >= 1) {
    // Delete the temporary table once data is copied.
    \Drupal::database()->schema()->dropTable('group_content_entity_id_update');
  }
}

/**
 * Fix existing group content views relationships.
 */
function group_post_update_fix_group_content_views_relations() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('views.view.') as $name) {
    $view = $config_factory->getEditable($name);
    $changed = FALSE;
    foreach ($view->get('display') as $display_id => $display) {
      if (isset($display['display_options']['relationships'])) {
        foreach ($display['display_options']['relationships'] as $relation_id => $relation) {
          if ($relation['table'] == 'group_content_field_data') {
            $trail = "display.$display_id.display_options.relationships.$relation_id.table";
            $view->set($trail, 'group_content__entity_id')->save();
            $changed = TRUE;
          }
        }
      }
    }
    if ($changed) {
      $view->save();
    }
  }
}
