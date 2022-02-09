<?php

namespace Drupal\groupmedia\Plugin\GroupConfigEnabler;

use Drupal\group\Plugin\GroupConfigEnablerBase;

/**
 * Provides a content enabler for media items.
 *
 * @GroupConfigEnabler(
 *   id = "group_media_type",
 *   label = @Translation("Group media type"),
 *   description = @Translation("Adds media type to groups both publicly and privately."),
 *   entity_type_id = "media_type",
 * )
 */
class GroupMediaType extends GroupConfigEnablerBase {}
