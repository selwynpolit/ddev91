<?php

namespace Drupal\group\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\group\Field\GroupContentReferenceDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the Group content entity.
 *
 * @ingroup group
 *
 * @ContentEntityType(
 *   id = "group_content",
 *   label = @Translation("Group content"),
 *   label_singular = @Translation("group content item"),
 *   label_plural = @Translation("group content items"),
 *   label_count = @PluralTranslation(
 *     singular = "@count group content item",
 *     plural = "@count group content items"
 *   ),
 *   bundle_label = @Translation("Group content type"),
 *   handlers = {
 *     "storage" = "Drupal\group\Entity\Storage\GroupContentStorage",
 *     "storage_schema" = "Drupal\group\Entity\Storage\GroupContentStorageSchema",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\group\Entity\Views\GroupContentViewsData",
 *     "list_builder" = "Drupal\group\Entity\Controller\GroupContentListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\group\Entity\Routing\GroupContentRouteProvider",
 *     },
 *     "form" = {
 *       "add" = "Drupal\group\Entity\Form\GroupContentForm",
 *       "edit" = "Drupal\group\Entity\Form\GroupContentForm",
 *       "delete" = "Drupal\group\Entity\Form\GroupContentDeleteForm",
 *       "group-join" = "Drupal\group\Form\GroupJoinForm",
 *       "group-leave" = "Drupal\group\Form\GroupLeaveForm",
 *     },
 *     "access" = "Drupal\group\Entity\Access\GroupContentAccessControlHandler",
 *   },
 *   base_table = "group_content",
 *   data_table = "group_content_field_data",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *     "langcode" = "langcode",
 *     "bundle" = "type",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/group/{group}/content/add/{plugin_id}",
 *     "add-page" = "/group/{group}/content/add",
 *     "canonical" = "/group/{group}/content/{group_content}",
 *     "collection" = "/group/{group}/content",
 *     "create-form" = "/group/{group}/content/create/{plugin_id}",
 *     "create-page" = "/group/{group}/content/create",
 *     "delete-form" = "/group/{group}/content/{group_content}/delete",
 *     "edit-form" = "/group/{group}/content/{group_content}/edit"
 *   },
 *   bundle_entity_type = "group_content_type",
 *   field_ui_base_route = "entity.group_content_type.edit_form",
 *   permission_granularity = "bundle",
 *   constraints = {
 *     "GroupContentCardinality" = {}
 *   }
 * )
 */
class GroupContent extends ContentEntityBase implements GroupContentInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function getGroupContentType() {
    return $this->type->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    return $this->gid->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    return $this->{$this->getEntityFieldName()}->entity;
  }

  /**
   * {@inheritdoc}
   */
  public static function getEntityFieldNameForEntityType($entity_type_id) {
    // If the entity type is fieldable and has a numeric ID field, use an entity
    // reference field with an integer-based schema.
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    if ($entity_type->entityClassImplements(FieldableEntityInterface::class)) {
      /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $base_fields */
      $base_fields = \Drupal::service('entity_field.manager')->getBaseFieldDefinitions($entity_type_id);
      if ($base_fields[$entity_type->getKey('id')]->getType() === 'integer') {
        return 'entity_id';
      }
    }

    // Default to a string reference.
    return 'entity_id_str';
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFieldName() {
    $entity_type_id = $this->getContentPlugin()->getPluginDefinition()['entity_type_id'];
    return static::getEntityFieldNameForEntityType($entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getContentPlugin() {
    return $this->getGroupContentType()->getContentPlugin();
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByContentPluginId($plugin_id) {
    /** @var \Drupal\group\Entity\Storage\GroupContentStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('group_content');
    return $storage->loadByContentPluginId($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByEntity(EntityInterface $entity) {
    /** @var \Drupal\group\Entity\Storage\GroupContentStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('group_content');
    return $storage->loadByEntity($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getContentPlugin()->getContentLabel($this);
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    $uri_route_parameters['group'] = $this->getGroup()->id();
    // These routes depend on the plugin ID.
    if (in_array($rel, ['add-form', 'create-form'])) {
      $uri_route_parameters['plugin_id'] = $this->getContentPlugin()->getPluginId();
    }
    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Set the label so the DB also reflects it.
    $this->set('label', $this->label());
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // For memberships, we generally need to rebuild the group role cache for
    // the member's user account in the target group.
    $rebuild_group_role_cache = $this->getContentPlugin()->getPluginId() == 'group_membership';

    if ($update === FALSE) {
      // We want to make sure that the entity we just added to the group behaves
      // as a grouped entity. This means we may need to update access records,
      // flush some caches containing the entity or perform other operations we
      // cannot possibly know about. Lucky for us, all of that behavior usually
      // happens when saving an entity so let's re-save the added entity.
      $this->getEntity()->save();
    }

    // If a membership gets updated, but the member's roles haven't changed, we
    // do not need to rebuild the group role cache for the member's account.
    elseif ($rebuild_group_role_cache) {
      $new = array_column($this->group_roles->getValue(), 'target_id');
      $old = array_column($this->original->group_roles->getValue(), 'target_id');
      sort($new);
      sort($old);
      $rebuild_group_role_cache = ($new != $old);
    }

    if ($rebuild_group_role_cache) {
      /** @var \Drupal\group\Entity\Storage\GroupRoleStorageInterface $role_storage */
      $role_storage = \Drupal::entityTypeManager()->getStorage('group_role');
      $role_storage->resetUserGroupRoleCache($this->getEntity(), $this->getGroup());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    /** @var GroupContentInterface[] $entities */
    foreach ($entities as $group_content) {
      if ($entity = $group_content->getEntity()) {
        // For the same reasons we re-save entities that are added to a group,
        // we need to re-save entities that were removed from one. See
        // ::postSave(). We only save the entity if it still exists to avoid
        // trying to save an entity that just got deleted and triggered the
        // deletion of its group content entities.
        // @todo Revisit when https://www.drupal.org/node/2754399 lands.
        $entity->save();

        // If a membership gets deleted, we need to reset the internal group
        // roles cache for the member in that group, but only if the user still
        // exists. Otherwise, it doesn't matter as the user ID will become void.
        if ($group_content->getContentPlugin()->getPluginId() == 'group_membership') {
          /** @var \Drupal\group\Entity\Storage\GroupRoleStorageInterface $role_storage */
          $role_storage = \Drupal::entityTypeManager()->getStorage('group_role');
          $role_storage->resetUserGroupRoleCache($group_content->getEntity(), $group_content->getGroup());
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getListCacheTagsToInvalidate() {
    $tags = parent::getListCacheTagsToInvalidate();

    $field_name = $this->getEntityFieldName();
    $group_id = $this->get('gid')->target_id;
    $entity_id = $this->get($field_name)->target_id;
    $plugin_id = $this->getGroupContentType()->getContentPluginId();

    // A specific group gets any content, regardless of plugin used.
    // E.g.: A group's list of entities can be flushed with this.
    $tags[] = "group_content_list:group:$group_id";

    // A specific entity gets added to any group, regardless of plugin used.
    // E.g.: An entity's list of groups can be flushed with this.
    $tags[] = "group_content_list:entity:$entity_id";

    // Any entity gets added to any group using a specific plugin.
    // E.g.: A list of all memberships anywhere can be flushed with this.
    $tags[] = "group_content_list:plugin:$plugin_id";

    // A specific group gets any content using a specific plugin.
    // E.g.: A group's list of members can be flushed with this.
    $tags[] = "group_content_list:plugin:$plugin_id:group:$group_id";

    // A specific entity gets added to any group using a specific plugin.
    // E.g.: A user's list of memberships can be flushed with this.
    $tags[] = "group_content_list:plugin:$plugin_id:entity:$entity_id";

    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['gid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parent group'))
      ->setDescription(t('The group containing the entity.'))
      ->setSetting('target_type', 'group')
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setReadOnly(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ]);

    $fields['uid']
      ->setLabel(t('Group content creator'))
      ->setDescription(t('The username of the group content creator.'))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created on'))
      ->setDescription(t('The time that the group content was created.'))
      ->setTranslatable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed on'))
      ->setDescription(t('The time that the group content was last edited.'))
      ->setTranslatable(TRUE);

    if (\Drupal::moduleHandler()->moduleExists('path')) {
      $fields['path'] = BaseFieldDefinition::create('path')
        ->setLabel(t('URL alias'))
        ->setTranslatable(TRUE)
        ->setDisplayOptions('form', [
          'type' => 'path',
          'weight' => 30,
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setComputed(TRUE);
    }

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    /** @var \Drupal\group\Field\GroupContentReferenceDefinition[] $fields */
    $fields = [];

    // Depending on whether the entity type that can be grouped by this bundle
    // has a numerical or string ID field, we add an entity reference field that
    // has either an integer or varchar schema, respectively.
    /** @var \Drupal\group\Entity\GroupContentTypeInterface $group_content_type */
    if ($group_content_type = GroupContentType::load($bundle)) {
      $plugin = $group_content_type->getContentPlugin();

      $field_name = static::getEntityFieldNameForEntityType($plugin->getEntityTypeId());
      $fields[$field_name] = $field_name === 'entity_id'
        ? GroupContentReferenceDefinition::createNumericalReference()
        : GroupContentReferenceDefinition::createStringReference();

      if ($label = $plugin->getEntityReferenceLabel()) {
        $fields[$field_name]->setLabel($label);
      }

      if ($description = $plugin->getEntityReferenceDescription()) {
        $fields[$field_name]->setDescription($description);
      }

      foreach ($plugin->getEntityReferenceSettings() as $name => $setting) {
        $fields[$field_name]->setSetting($name, $setting);
      }
    }

    return $fields;
  }

}
