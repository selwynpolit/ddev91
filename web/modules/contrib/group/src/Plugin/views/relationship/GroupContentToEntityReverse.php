<?php

namespace Drupal\group\Plugin\views\relationship;

use Drupal\views\Views;

/**
 * A relationship handler which reverses group content entity references.
 *
 * This handler is mostly a copy-paste of core "entity_reverse" relationship
 * handler. We are not extending the EntityReverse class because we'd have to
 * replace the query() method with a 90% copy of the method so it's worthless.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("group_content_to_entity_reverse")
 */
class GroupContentToEntityReverse extends GroupContentToEntityBase {

  /**
   * {@inheritdoc}
   */
  protected function getTargetEntityType() {
    return $this->definition['entity_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    // First, relate our base table to the current base table to the
    // field, using the base table's id field to the field's column.
    $views_data = Views::viewsData()->get($this->table);
    $left_field = $views_data['table']['base']['field'];

    $first = [
      'left_table' => $this->tableAlias,
      'left_field' => $left_field,
      'table' => $this->definition['field table'],
      'field' => $this->definition['field field'],
      'adjusted' => TRUE,
    ];
    if (!empty($this->options['required'])) {
      $first['type'] = 'INNER';
    }

    if (!empty($this->definition['join_extra'])) {
      $first['extra'] = $this->definition['join_extra'];
    }

    // Add our own join condition, namely the group content type IDs.
    // This is the only thing which differs this handler from core
    // "entity_reverse".
    $first['extra'][] = [
      'field' => 'bundle',
      'value' => $this->getGroupContentTypesValue(),
    ];

    if (!empty($def['join_id'])) {
      $id = $def['join_id'];
    }
    else {
      $id = 'standard';
    }
    $first_join = $this->joinManager->createInstance($id, $first);

    $this->first_alias = $this->query->addTable($this->definition['field table'], $this->relationship, $first_join);

    // Second, relate the field table to the entity specified using
    // the entity id on the field table and the entity's id field.
    $second = [
      'left_table' => $this->first_alias,
      'left_field' => 'entity_id',
      'table' => $this->definition['base'],
      'field' => $this->definition['base field'],
      'adjusted' => TRUE,
    ];

    if (!empty($this->options['required'])) {
      $second['type'] = 'INNER';
    }

    if (!empty($def['join_id'])) {
      $id = $def['join_id'];
    }
    else {
      $id = 'standard';
    }
    $second_join = $this->joinManager->createInstance($id, $second);
    $second_join->adjusted = TRUE;

    // Use a short alias.
    $alias = $this->definition['field_name'] . '_' . $this->table;

    $this->alias = $this->query->addRelationship($alias, $second_join, $this->definition['base'], $this->relationship);
  }

}
