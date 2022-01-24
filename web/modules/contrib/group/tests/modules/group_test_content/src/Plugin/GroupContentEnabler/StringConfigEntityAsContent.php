<?php

namespace Drupal\group_test_content\Plugin\GroupContentEnabler;

use Drupal\group\Plugin\GroupContentEnablerBase;

/**
 * Provides a content enabler for groups.
 *
 * @GroupContentEnabler(
 *   id = "string_config_entity_as_content",
 *   label = @Translation("StringConfig"),
 *   description = @Translation("Adds test string identified content entities to groups."),
 *   entity_type_id = "group_test_config_entity_string",
 *   pretty_path_key = "config_entity_str",
 *   reference_label = @Translation("NA"),
 *   reference_description = @Translation("NA")
 * )
 */
class StringConfigEntityAsContent extends GroupContentEnablerBase {
}
