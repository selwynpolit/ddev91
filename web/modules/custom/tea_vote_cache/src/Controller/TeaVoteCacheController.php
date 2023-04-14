<?php

namespace Drupal\tea_vote_cache\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for TEA Vote Cache routes.
 */
class TeaVoteCacheController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
