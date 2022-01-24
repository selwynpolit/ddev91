<?php

namespace Drupal\group_test_content\Plugin\GroupContentEnabler;

use Drupal\group\Plugin\GroupContentEnablerBase;

/**
 * Provides a content enabler for groups.
 *
 * @GroupContentEnabler(
 *   id = "integer_content_entity_as_content",
 *   label = @Translation("IntegerContent"),
 *   description = @Translation("Adds test integer identified content entities to groups."),
 *   entity_type_id = "group_test_content_entity_int",
 *   pretty_path_key = "content_entity_int",
 *   reference_label = @Translation("NA"),
 *   reference_description = @Translation("NA")
 * )
 */
class IntegerContentEntityAsContent extends GroupContentEnablerBase {
}
