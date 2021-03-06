<?php

namespace Drupal\group\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a Group content entity.
 *
 * @ingroup group
 */
interface GroupContentInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Returns the group content type entity the group content uses.
   *
   * @return \Drupal\group\Entity\GroupContentTypeInterface
   */
  public function getGroupContentType();

  /**
   * Returns the group the group content belongs to.
   *
   * @return \Drupal\group\Entity\GroupInterface
   */
  public function getGroup();

  /**
   * Returns the entity that was added as group content.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getEntity();

  /**
   * Returns the name of the entity reference field for a given entity type.
   *
   * @param string $entity_type_id
   *   The ID of the entity type to retrieve the field name for.
   *
   * @return string
   *   The name of the field referencing group content.
   */
  public static function getEntityFieldNameForEntityType($entity_type_id);

  /**
   * Returns the name of the group content entity reference field.
   *
   * @return string
   *   The name of the field referencing group content.
   *
   * @see \Drupal\group\Entity\GroupContentInterface::getEntityFieldNameForEntityType()
   */
  public function getEntityFieldName();

  /**
   * Returns the content enabler plugin that handles the group content.
   *
   * @return \Drupal\group\Plugin\GroupContentEnablerInterface
   */
  public function getContentPlugin();

  /**
   * Loads group content entities by their responsible plugin ID.
   *
   * @param string $plugin_id
   *   The ID of the content enabler plugin.
   *
   * @return \Drupal\group\Entity\GroupContentInterface[]
   *   An array of group content entities indexed by their IDs.
   */
  public static function loadByContentPluginId($plugin_id);

  /**
   * Loads group content entities which reference a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity which may be within one or more groups.
   *
   * @return \Drupal\group\Entity\GroupContentInterface[]
   *   An array of group content entities which reference the given entity.
   */
  public static function loadByEntity(EntityInterface $entity);

}
