<?php

namespace Drupal\Tests\groupmedia\Kernel;

use Drupal\Tests\group\Kernel\GroupKernelTestBase;

/**
 * Tests tracking behaviour.
 *
 * @group groupmedia
 */
class GroupMediaTestTracking extends GroupKernelTestBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group we will use to test methods on.
   *
   * @var \Drupal\group\Entity\Group
   */
  protected $group;

  /**
   * The media group content type.
   *
   * @var \Drupal\group\Entity\GroupContentType
   */
  protected $groupContentType;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'file',
    'image',
    'text',
    'system',
    'node',
    'gnode',
    'media',
    'groupmedia',
    'groupmedia_test_config',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installEntitySchema('node');

    $this->installSchema('file', 'file_usage');
    $this->installSchema('node', ['node_access']);

    $this->installConfig([
      'field',
      'system',
      'image',
      'file',
      'media',
      'groupmedia_test_config',
    ]);

    $this->group = $this->createGroup();
    $this->config = $this->container->get('plugin.manager.group_content_enabler');

    // Enable groupmedia remote video plugin and node plugin.
    $group_content_type_storage = $this->entityTypeManager->getStorage('group_content_type');
    $group_content_type_storage->save($group_content_type_storage->createFromPlugin($this->group->getGroupType(), 'group_media:remote_video'));
    $group_content_type_storage->save($group_content_type_storage->createFromPlugin($this->group->getGroupType(), 'group_node:group_media_content'));

    $this->pluginManager->clearCachedPluginMaps();

    $plugin = $this->pluginManager->createInstance('group_media:remote_video', ['group_type_id' => $this->group->getGroupType()->id()]);
    $this->groupContentType = $group_content_type_storage->load($plugin->getContentTypeConfigId());
  }

  /**
   * Test that media is added to group, when tracking is enabled.
   */
  public function testContentAddingWithEnabledTracking() {
    $media = $this->createMedia();
    $node = $this->createNode(['field_media' => $media->id()]);
    $this->group->addContent($node, 'group_node:group_media_content');

    $this->assertEquals(0, count($this->group->getContent('group_media:remote_video')));

    $configuration = $this->groupContentType->get('plugin_config');
    $configuration['tracking_enabled'] = 1;
    $this->groupContentType->set('plugin_config', $configuration);
    $this->groupContentType->save(TRUE);

    $this->pluginManager->clearCachedPluginMaps();

    $node = $this->createNode(['field_media' => $media->id()]);
    $this->group->addContent($node, 'group_node:group_media_content');

    $this->assertEquals(1, count($this->group->getContent('group_media:remote_video')));
  }

  /**
   * Create a media item.
   *
   * @param array $values
   *   Additional properties.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Media item.
   */
  protected function createMedia(array $values = []) {
    $storage = $this->entityTypeManager->getStorage('media');
    $media = $storage->create($values + [
      'bundle' => 'remote_video',
      'name' => $this->randomString(),
      'field_media_oembed_video' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    ]);
    $media->enforceIsNew();
    $storage->save($media);
    return $media;
  }

  /**
   * Create a node item.
   *
   * @param array $values
   *   Additional properties.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Media item.
   */
  protected function createNode(array $values = []) {
    $storage = $this->entityTypeManager->getStorage('node');
    $node = $storage->create($values + [
      'type' => 'group_media_content',
      'title' => $this->randomString(),
    ]);
    $node->enforceIsNew();
    $storage->save($node);
    return $node;
  }

}
