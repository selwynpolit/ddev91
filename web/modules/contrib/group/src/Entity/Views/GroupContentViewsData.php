<?php

namespace Drupal\group\Entity\Views;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Field\GroupContentReferenceDefinition;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Drupal\views\EntityViewsData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the views data for the group content entity type.
 */
class GroupContentViewsData extends EntityViewsData {

  /**
   * The group content enabler plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $pluginManager;

  /**
   * TODO.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   * @inheritDoc
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    SqlEntityStorageInterface $storage_controller,
    EntityTypeManagerInterface $entity_type_manager,
    ModuleHandlerInterface $module_handler,
    TranslationInterface $translation_manager,
    EntityFieldManagerInterface $entity_field_manager,
    GroupContentEnablerManagerInterface $content_enabler_manager,
    FieldTypePluginManagerInterface $field_type_plugin_manager
  ) {
    parent::__construct(
      $entity_type,
      $storage_controller,
      $entity_type_manager,
      $module_handler,
      $translation_manager,
      $entity_field_manager
    );

    $this->pluginManager = $content_enabler_manager;
    $this->fieldTypePluginManager = $field_type_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('string_translation'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.group_content_enabler'),
      $container->get('plugin.manager.field.field_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Add a custom numeric argument for the parent group ID that allows us to
    // use replacement titles with the parent group's label.
    $data['group_content_field_data']['gid']['argument'] = [
      'id' => 'group_id',
      'numeric' => TRUE,
    ];

    $table_mapping = $this->storage->getTableMapping();
    $entity_types = $this->entityTypeManager->getDefinitions();

    // Add views data for all defined plugins so modules can provide default
    // views even though their plugins may not have been installed yet.
    foreach ($this->pluginManager->getAll() as $plugin) {
      $entity_type_id = $plugin->getEntityTypeId();
      if (!isset($entity_types[$entity_type_id])) {
        continue;
      }
      $entity_type = $entity_types[$entity_type_id];
      $entity_data_table = $entity_type->getDataTable() ?: $entity_type->getBaseTable();

      // Create a unique field name for this views field.
      // This is a Views field, not entity field!
      $views_field_name = 'gc__' . $entity_type_id;
      $field_name = GroupContent::getEntityFieldNameForEntityType($entity_type_id);
      $field_table = $this->getFieldTableName($field_name);

      if (isset($data[$field_table][$views_field_name])) {
        // Skip further processing if the relationship is already defined by a
        // different plugin.
        continue;
      }

      $field_definition = $field_name === 'entity_id'
        ? GroupContentReferenceDefinition::createNumericalReference()
        : GroupContentReferenceDefinition::createStringReference();

      // Avoid reload field default views data to preserve existing relations.
      if (!isset($data[$field_table])) {
        // Fill in default Views data for a field, just like Views does
        // for fields defined via config entities.
        $default_data = $this->defaultFieldViewsData($field_definition);
        $data[$field_table] = $default_data[$field_table];
      }

      // This relationship will allow a content entity to easily map to the
      // group content entity that ties it to a group, optionally filtering by
      // plugin.
      $t_args = ['@entity_type' => $entity_type->getLabel()];
      $data[$field_table][$views_field_name] = [
        'title' => $this->t('@entity_type from group content', $t_args),
        'help' => $this->t('Relates to the @entity_type entity the group content represents.', $t_args),
        'relationship' => [
          'group' => $entity_type->getLabel(),
          'base' => $entity_data_table,
          'base field' => $entity_type->getKey('id'),
          'relationship field' => $table_mapping->getFieldColumnName($field_definition->getFieldStorageDefinition(), 'target_id'),
          'id' => 'group_content_to_entity',
          'label' => $this->t('Group content @entity_type', $t_args),
          'target_entity_type' => $entity_type_id,
        ],
      ];
    }

    // Add the entity type metadata to each table generated.
    $entity_type_id = $this->entityType->id();
    array_walk($data, function (&$table_data) use ($entity_type_id) {
      $table_data['table']['entity type'] = $entity_type_id;
      $table_data['table']['entity revision'] = FALSE;
    });

    return $data;
  }

  /**
   * Defaults Views data implementation for the group content field.
   *
   * This method is mostly a copy-paste of views_field_default_views_data().
   * Unfortunately, we can't re-use is since it only accepts
   * FieldStorageConfigInterface as an argument.
   *
   * Group content does not support revisions so everything related to
   * revisions is stripped for simplicity.
   * The group content entity reference field is not translatable so everything
   * related to translations is stripped as well.
   *
   * @see views_field_default_views_data()
   *
   * @param \Drupal\group\Field\GroupContentReferenceDefinition $field_storage
   *   Group content reference field definition.
   *
   * @return array
   *   The default views data for the field.
   *
   * @TODO Review this code once https://www.drupal.org/node/3016026 lands.
   */
  protected function defaultFieldViewsData(GroupContentReferenceDefinition $field_storage) {
    $data = [];

    // Check the field type is available.
    if (!$this->fieldTypePluginManager->hasDefinition($field_storage->getType())) {
      return $data;
    }

    $field_name = $field_storage->getName();
    $field_columns = $field_storage->getColumns();

    // Grab information about the entity type tables.
    // We need to join to both the base table and the data table, if available.
    // Check whether the entity type storage is supported.
    $storage = $this->entityTypeManager->getStorage($field_storage->getTargetEntityTypeId());
    if (!$storage) {
      return $data;
    }

    $entity_type_id = $field_storage->getTargetEntityTypeId();
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    if (!$base_table = $entity_type->getBaseTable()) {
      // We cannot do anything if for some reason there is no base table.
      return $data;
    }
    // Some entities may not have a data table.
    $data_table = $entity_type->getDataTable();

    // Description of the field tables.
    /** @var \Drupal\Core\Entity\Sql\TableMappingInterface $table_mapping */
    $table_mapping = $storage->getTableMapping();
    $field_tables = [
      EntityStorageInterface::FIELD_LOAD_CURRENT => [
        'table' => $table_mapping->getDedicatedDataTableName($field_storage),
        'alias' => "{$entity_type_id}__{$field_name}",
      ],
    ];

    // Build the relationships between the field table and the entity tables.
    $table_alias = $field_tables[EntityStorageInterface::FIELD_LOAD_CURRENT]['alias'];
    if ($data_table) {
      // Tell Views how to join to the base table, via the data table.
      $data[$table_alias]['table']['join'][$data_table] = [
        'table' => $table_mapping->getDedicatedDataTableName($field_storage),
        'left_field' => $entity_type->getKey('id'),
        'field' => 'entity_id',
        'extra' => [
          ['field' => 'deleted', 'value' => 0, 'numeric' => TRUE],
        ],
      ];
    }
    else {
      // If there is no data table, just join directly.
      $data[$table_alias]['table']['join'][$base_table] = [
        'table' => $table_mapping->getDedicatedDataTableName($field_storage),
        'left_field' => $entity_type->getKey('id'),
        'field' => 'entity_id',
        'extra' => [
          ['field' => 'deleted', 'value' => 0, 'numeric' => TRUE],
        ],
      ];
    }

    $group_name = $entity_type->getLabel();

    // Build the list of additional fields to add to queries.
    $add_fields = ['delta', 'langcode', 'bundle'];
    foreach (array_keys($field_columns) as $column) {
      $add_fields[] = $table_mapping->getFieldColumnName($field_storage, $column);
    }
    // Determine the label to use for the field. We don't have a label available
    // at the field level, so we just go through all fields and take the one
    // which is used the most frequently.
    [$label, $all_labels] = views_entity_field_label($entity_type_id, $field_name);

    // Expose data for the field as a whole.
    foreach ($field_tables as $type => $table_info) {
      $table = $table_info['table'];
      $table_alias = $table_info['alias'];

      if ($type == EntityStorageInterface::FIELD_LOAD_CURRENT) {
        $group = $group_name;
        $field_alias = $field_name;
      }
      else {
        $group = t('@group (historical data)', ['@group' => $group_name]);
        $field_alias = $field_name . '-revision_id';
      }

      $data[$table_alias][$field_alias] = [
        'group' => $group,
        'title' => $label,
        'title short' => $label,
      ];

      // Go through and create a list of aliases for all possible combinations
      // of entity type + name.
      $aliases = [];
      $also_known = [];
      foreach ($all_labels as $label_name => $true) {
        if ($type == EntityStorageInterface::FIELD_LOAD_CURRENT && $label != $label_name) {
          $aliases[] = [
            'base' => $base_table,
            'group' => $group_name,
            'title' => $label_name,
            'help' => t('This is an alias of @group: @field.', ['@group' => $group_name, '@field' => $label]),
          ];
          $also_known[] = t('@group: @field', ['@group' => $group_name, '@field' => $label_name]);
        }
      }

      if ($aliases) {
        $data[$table_alias][$field_alias]['aliases'] = $aliases;
        // The $also_known variable contains markup that is HTML escaped and
        // that loses safeness when imploded. The help text is used in
        // #description and therefore XSS admin filtered by default. Escaped
        // HTML is not altered by XSS filtering, therefore it is safe to just
        // concatenate the strings. Afterwards we mark the entire string as
        // safe, so it won't be escaped, no matter where it is used.
        // Considering the dual use of this help data (both as metadata and as
        // help text), other patterns such as use of #markup would not be
        // correct here.
        $data[$table_alias][$field_alias]['help'] = Markup::create($data[$table_alias][$field_alias]['help'] . ' ' . t('Also known as:') . ' ' . implode(', ', $also_known));
      }

      $keys = array_keys($field_columns);
      $real_field = reset($keys);
      $data[$table_alias][$field_alias]['field'] = [
        'table' => $table,
        'id' => 'field',
        'field_name' => $field_name,
        'entity_type' => $entity_type_id,
        // Provide a real field for group by.
        'real field' => $field_alias . '_' . $real_field,
        'additional fields' => $add_fields,
        // Default the element type to div, let the UI change it if necessary.
        'element type' => 'div',
        'is revision' => $type == EntityStorageInterface::FIELD_LOAD_REVISION,
      ];
    }

    // Expose data for each field property individually.
    foreach ($field_columns as $column => $attributes) {
      $allow_sort = TRUE;

      // Identify likely filters and arguments for each column based on field
      // type.
      switch ($attributes['type']) {
        case 'int':
        case 'mediumint':
        case 'tinyint':
        case 'bigint':
        case 'serial':
        case 'numeric':
        case 'float':
          $filter = 'numeric';
          $argument = 'numeric';
          $sort = 'standard';
          if ($field_storage->getType() == 'boolean') {
            $filter = 'boolean';
          }
          break;

        case 'text':
        case 'blob':
          // It does not make sense to sort by blob or text.
          $allow_sort = FALSE;

        default:
          $filter = 'string';
          $argument = 'string';
          $sort = 'standard';
          break;
      }

      if (count($field_columns) == 1 || $column == 'value') {
        $title = t('@label (@name)', ['@label' => $label, '@name' => $field_name]);
        $title_short = $label;
      }
      else {
        $title = t('@label (@name:@column)', [
          '@label' => $label,
          '@name' => $field_name,
          '@column' => $column,
        ]);
        $title_short = t('@label:@column', ['@label' => $label, '@column' => $column]);
      }

      // Expose data for the property.
      foreach ($field_tables as $type => $table_info) {
        $table = $table_info['table'];
        $table_alias = $table_info['alias'];

        if ($type == EntityStorageInterface::FIELD_LOAD_CURRENT) {
          $group = $group_name;
        }
        else {
          $group = t('@group (historical data)', ['@group' => $group_name]);
        }
        $column_real_name = $table_mapping->getFieldColumnName($field_storage, $column);

        // Load all the fields from the table by default.
        $additional_fields = $table_mapping->getAllColumns($table);

        $data[$table_alias][$column_real_name] = [
          'group' => $group,
          'title' => $title,
          'title short' => $title_short,
        ];

        // Go through and create a list of aliases for all possible
        // combinations of entity type + name.
        $aliases = [];
        $also_known = [];
        foreach ($all_labels as $label_name => $true) {
          if ($label != $label_name) {
            if (count($field_columns) == 1 || $column == 'value') {
              $alias_title = t('@label (@name)', ['@label' => $label_name, '@name' => $field_name]);
            }
            else {
              $alias_title = t('@label (@name:@column)', [
                '@label' => $label_name,
                '@name' => $field_name,
                '@column' => $column,
              ]);
            }
            $aliases[] = [
              'group' => $group_name,
              'title' => $alias_title,
              'help' => t('This is an alias of @group: @field.', ['@group' => $group_name, '@field' => $title]),
            ];
            $also_known[] = t('@group: @field', ['@group' => $group_name, '@field' => $title]);
          }
        }
        if ($aliases) {
          $data[$table_alias][$column_real_name]['aliases'] = $aliases;
          // The $also_known variable contains markup that is HTML escaped and
          // that loses safeness when imploded. The help text is used in
          // #description and therefore XSS admin filtered by default. Escaped
          // HTML is not altered by XSS filtering, therefore it is safe to just
          // concatenate the strings. Afterwards we mark the entire string as
          // safe, so it won't be escaped, no matter where it is used.
          // Considering the dual use of this help data (both as metadata and as
          // help text), other patterns such as use of #markup would not be
          // correct here.
          $data[$table_alias][$column_real_name]['help'] = Markup::create($data[$table_alias][$column_real_name]['help'] . ' ' . t('Also known as:') . ' ' . implode(', ', $also_known));
        }

        $data[$table_alias][$column_real_name]['argument'] = [
          'field' => $column_real_name,
          'table' => $table,
          'id' => $argument,
          'additional fields' => $additional_fields,
          'field_name' => $field_name,
          'entity_type' => $entity_type_id,
          'empty field name' => t('- No value -'),
        ];
        $data[$table_alias][$column_real_name]['filter'] = [
          'field' => $column_real_name,
          'table' => $table,
          'id' => $filter,
          'additional fields' => $additional_fields,
          'field_name' => $field_name,
          'entity_type' => $entity_type_id,
          'allow empty' => TRUE,
        ];
        if (!empty($allow_sort)) {
          $data[$table_alias][$column_real_name]['sort'] = [
            'field' => $column_real_name,
            'table' => $table,
            'id' => $sort,
            'additional fields' => $additional_fields,
            'field_name' => $field_name,
            'entity_type' => $entity_type_id,
          ];
        }

        // Set click sortable if there is a field definition.
        if (isset($data[$table_alias][$field_name]['field'])) {
          $data[$table_alias][$field_name]['field']['click sortable'] = $allow_sort;
        }
      }
    }

    return $data;
  }

  /**
   * Returns the SQL table name for a given field.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return string
   *   The field table name.
   */
  protected function getFieldTableName($field_name) {
    // The getTableMapping() method assumes field storage definitions are
    // static. This is causing an error while installing the gnode module.
    // As a workaround, we're field storage definitions from the entity manager
    // to bypass cache.
    // @see https://www.drupal.org/project/drupal/issues/3016059
    $table_mapping = $this->storage->getTableMapping($this->getFieldStorageDefinitions('group_content'));
    return $table_mapping->getFieldTableName($field_name);
  }

}
