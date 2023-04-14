<?php

namespace Drupal\Tests\groupmedia\Functional;

use Drupal\group\Entity\GroupType;
use Drupal\Tests\group\Functional\EntityOperationsTest as GroupEntityOperationsTest;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Tests that entity operations (do not) show up on the group overview.
 *
 * @see groupmedia_entity_operation()
 *
 * @group groupmedia
 */
class EntityOperationsTest extends GroupEntityOperationsTest {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['groupmedia'];

  /**
   * Checks for entity operations under given circumstances.
   *
   * @param array $visible
   *   A list of visible link labels, keyed by path.
   * @param array $invisible
   *   A list of invisible link labels, keyed by path.
   * @param string[] $permissions
   *   A list of group permissions to assign to the user.
   * @param string[] $modules
   *   A list of modules to enable.
   * @param bool $has_media
   *   Whether there are any media types enabled as group content.
   *
   * @dataProvider provideEntityOperationScenarios
   */
  public function testEntityOperations($visible, $invisible, $permissions = [], $modules = [], $has_media = FALSE) {
    if (!$has_media) {
      parent::testEntityOperations($visible, $invisible, $permissions, $modules);
      return;
    }

    // Create a media type and enable it as group content.
    $media_type = $this->createMediaType('image');
    $media_type->save();
    \Drupal::entityTypeManager()
      ->getStorage('group_content_type')
      ->createFromPlugin(GroupType::load('default'), 'group_media:' . $media_type->id(), [
        'group_cardinality' => 0,
        'entity_cardinality' => 1,
        'use_creation_wizard' => FALSE,
      ])
      ->save();

    parent::testEntityOperations($visible, $invisible, $permissions, $modules);
  }

  /**
   * {@inheritdoc}
   */
  public function provideEntityOperationScenarios() {
    $scenarios['withoutAccess'] = [
      [],
      ['group/1/media' => 'Media'],
    ];

    $scenarios['withAccess'] = [
      [],
      ['group/1/media' => 'Media'],
      ['access group_media overview'],
    ];

    $scenarios['withAccessAndViewsNoMedia'] = [
      [],
      ['group/1/media' => 'Media'],
      ['access group_media overview'],
      ['views'],
    ];

    $scenarios['withAccessAndViewsAndMedia'] = [
      ['group/1/media' => 'Media'],
      [],
      ['access group_media overview'],
      ['views'],
      TRUE,
    ];

    return $scenarios;
  }

}
