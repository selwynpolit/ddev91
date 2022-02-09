<?php

namespace Drupal\groupmedia\Plugin\GroupContentEnabler;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media\Entity\MediaType;
use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Class GroupMediaDeriver.
 *
 * @package Drupal\groupmedia\Plugin\GroupContentEnabler
 */
class GroupMediaDeriver extends DeriverBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach (MediaType::loadMultiple() as $name => $media_type) {
      $label = $media_type->label();

      $this->derivatives[$name] = [
        'entity_bundle' => $name,
        'label' => $this->t('Group media (@type)', ['@type' => $label]),
        'description' => $this->t('Adds %type content to groups both publicly and privately.', ['%type' => $label]),
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
