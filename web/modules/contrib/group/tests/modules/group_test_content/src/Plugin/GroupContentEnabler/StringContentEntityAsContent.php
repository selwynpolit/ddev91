<?php

namespace Drupal\group_test_content\Plugin\GroupContentEnabler;

use Drupal\group\Plugin\GroupContentEnablerBase;

/**
 * Provides a content enabler for groups.
 *
 * @GroupContentEnabler(
 *   id = "string_content_entity_as_content",
 *   label = @Translation("StringContent"),
 *   description = @Translation("Adds test string identified config entities to groups."),
 *   entity_type_id = "group_test_content_entity_string",
 *   pretty_path_key = "content_entity_str",
 *   reference_label = @Translation("NA"),
 *   reference_description = @Translation("NA")
 * )
 */
class StringContentEntityAsContent extends GroupContentEnablerBase {
}
