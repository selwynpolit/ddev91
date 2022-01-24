<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\group\Entity\GroupContentInterface;

/**
 * Tests the behavior of group content storage handler.
 *
 * @coversDefaultClass \Drupal\group\Entity\Storage\GroupContentStorage
 * @group group
 */
class GroupContentStorageTest extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group_test_plugin', 'group_test_content'];

  /**
   * The group content storage handler.
   *
   * @var \Drupal\group\Entity\Storage\GroupContentStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->storage = $this->entityTypeManager->getStorage('group_content');

    // Enable the test plugins on the default group type.
    /** @var \Drupal\group\Entity\Storage\GroupContentTypeStorageInterface $storage */
    $group_type = $this->entityTypeManager->getStorage('group_type')->load('default');
    $storage = $this->entityTypeManager->getStorage('group_content_type');
    $storage->createFromPlugin($group_type, 'user_as_content')->save();
    $storage->createFromPlugin($group_type, 'group_as_content')->save();

    $this->installConfig(['group_test_content']);
    $storage->createFromPlugin($group_type, 'integer_content_entity_as_content')->save();
    $storage->createFromPlugin($group_type, 'string_config_entity_as_content')->save();
    $storage->createFromPlugin($group_type, 'string_content_entity_as_content')->save();

    $this->installEntitySchema('group_test_content_entity_int');
    $this->installEntitySchema('group_test_config_entity_string');
    $this->installEntitySchema('group_test_content_entity_string');
  }

  /**
   * Creates an unsaved group.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\group\Entity\Group
   *   The created group entity.
   */
  protected function createUnsavedGroup($values = []) {
    $group = $this->entityTypeManager->getStorage('group')->create($values + [
      'type' => 'default',
      'label' => $this->randomMachineName(),
    ]);
    return $group;
  }

  /**
   * Creates an unsaved user.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\user\Entity\User
   *   The created user entity.
   */
  protected function createUnsavedUser($values = []) {
    $account = $this->entityTypeManager->getStorage('user')->create($values + [
      'name' => $this->randomMachineName(),
      'status' => 1,
    ]);
    return $account;
  }

  /**
   * Tests the creation of a GroupContent entity using an unsaved group.
   *
   * @covers ::createForEntityInGroup
   */
  public function testCreateForUnsavedGroup() {
    $group = $this->createUnsavedGroup();
    $account = $this->createUser();

    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage('Cannot add an entity to an unsaved group.');
    $this->storage->createForEntityInGroup($account, $group, 'user_as_content');
  }

  /**
   * Tests the creation of a GroupContent entity using an unsaved entity.
   *
   * @covers ::createForEntityInGroup
   */
  public function testCreateForUnsavedEntity() {
    $group = $this->createGroup();
    $account = $this->createUnsavedUser();

    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage('Cannot add an unsaved entity to a group.');
    $this->storage->createForEntityInGroup($account, $group, 'user_as_content');
  }

  /**
   * Tests the creation of a GroupContent entity using an incorrect plugin ID.
   *
   * @covers ::createForEntityInGroup
   */
  public function testCreateForInvalidPluginId() {
    $group = $this->createGroup();
    $account = $this->createUser();

    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage('Invalid plugin provided for adding the entity to the group.');
    $this->storage->createForEntityInGroup($account, $group, 'group_as_content');
  }

  /**
   * Tests the creation of a GroupContent entity using an incorrect bundle.
   *
   * @covers ::createForEntityInGroup
   */
  public function testCreateForInvalidBundle() {
    $group = $this->createGroup();
    $subgroup = $this->createGroup(['type' => 'other']);

    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage("The provided plugin provided does not support the entity's bundle.");
    $this->storage->createForEntityInGroup($subgroup, $group, 'group_as_content');
  }

  /**
   * Tests the creation of a GroupContent entity using a bundle.
   *
   * @covers ::createForEntityInGroup
   */
  public function testCreateWithBundle() {
    $group = $this->createGroup();
    $subgroup = $this->createGroup();
    $group_content = $this->storage->createForEntityInGroup($subgroup, $group, 'group_as_content');
    $this->assertInstanceOf('\Drupal\group\Entity\GroupContentInterface', $group_content, 'Created a GroupContent entity using a bundle-specific plugin.');
  }

  /**
   * Tests the creation of a GroupContent entity using no bundle.
   *
   * @covers ::createForEntityInGroup
   */
  public function testCreateWithoutBundle() {
    $group = $this->createGroup();
    $account = $this->createUser();
    $group_content = $this->storage->createForEntityInGroup($account, $group, 'user_as_content');
    $this->assertInstanceOf('\Drupal\group\Entity\GroupContentInterface', $group_content, 'Created a GroupContent entity using a bundle-independent plugin.');
  }

  /**
   * Tests the loading of GroupContent entities for an unsaved group.
   *
   * @covers ::loadByGroup
   */
  public function testLoadByUnsavedGroup() {
    $group = $this->createUnsavedGroup();
    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage('Cannot load GroupContent entities for an unsaved group.');
    $this->storage->loadByGroup($group);
  }

  /**
   * Tests the loading of GroupContent entities for a group.
   *
   * @covers ::loadByGroup
   */
  public function testLoadByGroup() {
    $group = $this->createGroup();
    $this->assertCount(1, $this->storage->loadByGroup($group), 'Managed to load the group creator membership by group.');
  }

  /**
   * Tests the loading of GroupContent entities for an unsaved entity.
   *
   * @covers ::loadByEntity
   */
  public function testLoadByUnsavedEntity() {
    $group = $this->createUnsavedGroup();
    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage('Cannot load GroupContent entities for an unsaved entity.');
    $this->storage->loadByEntity($group);
  }

  /**
   * Tests the loading of GroupContent entities for an entity.
   *
   * @covers ::loadByEntity
   */
  public function testLoadByEntity() {
    $this->createGroup();
    $account = $this->getCurrentUser();
    $this->assertCount(1, $this->storage->loadByEntity($account), 'Managed to load the group creator membership by user.');
  }

  /**
   * Tests the loading of GroupContent entities for an entity.
   *
   * @covers ::loadByContentPluginId
   */
  public function testLoadByContentPluginId() {
    $this->createGroup();
    $this->assertCount(1, $this->storage->loadByContentPluginId('group_membership'), 'Managed to load the group creator membership by plugin ID.');
  }

  /**
   * Tests the loading of GroupContent entities for a group.
   *
   * @dataProvider testEntityTypesDataProvider
   * @covers ::loadByGroup
   */
  public function testLoadContentByGroup($entity_type, $plugin_id) {
    $group = $this->createGroup();
    $entity = $this->createTestEntity($entity_type);
    $this->storage->createForEntityInGroup($entity, $group, $plugin_id)->save();
    $loaded_entities = $this->storage->loadByGroup($group);
    $this->assertCount(2, $loaded_entities, 'Managed to load the group contents by group.');
    $loaded_users_contents = array_filter($loaded_entities, function (GroupContentInterface $group_content) {
      return $group_content->getEntity()->getEntityTypeId() == 'user';
    });
    $loaded_group_contents = array_filter($loaded_entities, function (GroupContentInterface $group_content) use ($entity_type) {
      return $group_content->getEntity()->getEntityTypeId() == $entity_type;
    });
    $this->assertCount(1, $loaded_users_contents);
    $this->assertCount(1, $loaded_group_contents);

    $group_content = reset($loaded_group_contents);
    $this->assertSameEntity($entity, $group_content->getEntity());
  }

  /**
   * Tests the loading of GroupContent entities for an entity.
   *
   * @dataProvider testEntityTypesDataProvider
   * @covers ::loadByEntity
   */
  public function testLoadContentByEntity($entity_type, $plugin_id) {
    $group = $this->createGroup();
    $entity = $this->createTestEntity($entity_type);
    $this->storage->createForEntityInGroup($entity, $group, $plugin_id)->save();
    $loaded_entities = $this->storage->loadByEntity($entity);
    $this->assertCount(1, $loaded_entities, 'Managed to load the group content by entity.');
    $group_content = reset($loaded_entities);
    $this->assertSameEntity($entity, $group_content->getEntity());
  }

  /**
   * Tests the loading of GroupContent entities for an entity.
   *
   * @dataProvider testEntityTypesDataProvider
   * @covers ::loadByContentPluginId
   */
  public function testLoadContentByContentPluginId($entity_type, $plugin_id) {
    $group = $this->createGroup();
    $entity = $this->createTestEntity($entity_type);
    $this->storage->createForEntityInGroup($entity, $group, $plugin_id)->save();
    $loaded_entities = $this->storage->loadByContentPluginId($plugin_id);
    $this->assertCount(1, $loaded_entities, 'Managed to load the group content by plugin ID.');
    $group_content = reset($loaded_entities);
    $this->assertSameEntity($entity, $group_content->getEntity());
  }

  /**
   * Data provider returning test entity types and corresponding plugins.
   */
  public function testEntityTypesDataProvider() {
    return [
      ['group_test_content_entity_int', 'integer_content_entity_as_content'],
      ['group_test_content_entity_string', 'string_content_entity_as_content'],
      ['group_test_config_entity_string', 'string_config_entity_as_content'],
    ];
  }

  /**
   * Creates test entity to be used as a test group content.
   *
   * @param string $entity_type
   *   Entity type.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The new saved entity of a given type.
   */
  protected function createTestEntity($entity_type) {
    $storage = $this->entityTypeManager->getStorage($entity_type);
    $values = [];

    switch ($entity_type) {
      case 'group_test_content_entity_string':
      case 'group_test_config_entity_string':
        $values['id'] = $this->randomMachineName();
        break;
    }

    $entity = $storage->create($values);
    $entity->save();
    return $entity;
  }

  /**
   * Asserts two entity objects are same.
   *
   * @param \Drupal\Core\Entity\EntityInterface $expected
   *   The entity which was expected.
   * @param \Drupal\Core\Entity\EntityInterface $actual
   *   The entity which was retrieved during the test.
   */
  protected function assertSameEntity(EntityInterface $expected, EntityInterface $actual) {
    $this->assertEquals($expected->getEntityTypeId(), $actual->getEntityTypeId());
    $this->assertEquals($expected->id(), $actual->id());
  }

}
