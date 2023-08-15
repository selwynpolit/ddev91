<?php

namespace Drupal\test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

/**
 * Returns responses for Test routes.
 */
class TestController extends ControllerBase {

  /**
   * Builds the response.srp
   */
  public function build() {

    $node = Node::load(1);
    $text = $node->get('body')->value;
    $text = date('l jS \of F Y h:i:s A') . "<br>" . $text;
    $node->set('body', [
      'value' => $text,
//      'format' => 'full_html',
      'format' => 'partial_html',
//        'html' => TRUE,
      ]
    );
//    $node->body = [];
//    $node->body = NULL;
    $node->save();

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t($text),
    ];

    return $build;
  }

}
