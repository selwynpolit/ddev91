<?php

namespace Drupal\group_test_content\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * String identified config entity type for GroupContent tests.
 *
 * @ConfigEntityType(
 *   id = "group_test_config_entity_string",
 *   label = @Translation("String identified test config entity type"),
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   config_export = {
 *     "id"
 *   }
 * )
 */
class StringConfig extends ConfigEntityBase {

  /**
   * The test ID.
   *
   * @var string
   */
  public $id;

}
