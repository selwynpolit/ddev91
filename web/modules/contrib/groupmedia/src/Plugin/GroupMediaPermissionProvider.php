<?php

namespace Drupal\groupmedia\Plugin;

use Drupal\group\Plugin\GroupContentPermissionProvider;

/**
 * Provides group permissions for group_media GroupContent entities.
 */
class GroupMediaPermissionProvider extends GroupContentPermissionProvider {

  /**
   * {@inheritdoc}
   */
  public function getEntityViewUnpublishedPermission($scope = 'any') {
    if ($scope === 'any') {
      // Backwards compatible permission name for 'any' scope.
      return "view unpublished $this->pluginId entity";
    }
    return parent::getEntityViewUnpublishedPermission($scope);
  }

}
