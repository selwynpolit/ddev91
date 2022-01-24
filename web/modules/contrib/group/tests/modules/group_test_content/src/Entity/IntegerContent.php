<?php

namespace Drupal\group_test_content\Entity;

use Drupal\Core\Entity\ContentEntityBase;

/**
 * Integer identified content entity type for GroupContent tests.
 *
 * @ContentEntityType(
 *   id = "group_test_content_entity_int",
 *   label = @Translation("Integer identified test content entity type"),
 *   base_table = "group_test_content_entity_int",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class IntegerContent extends ContentEntityBase {

}
