<?php

namespace Drupal\groupmedia;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Drupal\media\MediaInterface;

/**
 * Class AttachMediaToGroup.
 *
 * @package Drupal\groupmedia
 */
class AttachMediaToGroup {

  /**
   * The media finder plugin manager.
   *
   * @var \Drupal\groupmedia\MediaFinderManager
   */
  protected $mediaFinder;

  /**
   * Group enabler plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $groupEnabler;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Group content storage.
   *
   * @var \Drupal\group\Entity\Storage\GroupContentStorageInterface
   */
  protected $groupContentStorage;

  /**
   * Groupmedia config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * AttachMediaToGroup constructor.
   *
   * @param \Drupal\groupmedia\MediaFinderManager $mediaFinderManager
   *   Media finder plugin manager.
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $groupEnablerManager
   *   Group content enabler plugin manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   */
  public function __construct(MediaFinderManager $mediaFinderManager, GroupContentEnablerManagerInterface $groupEnablerManager, ModuleHandlerInterface $moduleHandler, EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory) {
    $this->mediaFinder = $mediaFinderManager;
    $this->groupEnabler = $groupEnablerManager;
    $this->moduleHandler = $moduleHandler;
    $this->groupContentStorage = $entityTypeManager->getStorage('group_content');
    $this->config = $configFactory->get('groupmedia.settings');
  }

  /**
   * Attach media items from given entity to the same group(s).
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to process.
   */
  public function attach(EntityInterface $entity) {
    if (!$this->config->get('tracking_enabled')) {
      return FALSE;
    }
    $groups = $this->getContentGroups($entity);
    if (empty($groups)) {
      return FALSE;
    }
    $items = [];
    // Get the list of installed group content instance IDs.
    $group_content_instance_ids = $this->groupEnabler
      ->getInstalled()
      ->getInstanceIds();
    $plugins = $this->mediaFinder->getDefinitions();
    foreach ($plugins as $plugin_id => $definition) {
      /** @var \Drupal\groupmedia\MediaFinderInterface $pluginInstance */
      $pluginInstance = $this->mediaFinder->createInstance($plugin_id);
      if ($pluginInstance && $pluginInstance->applies($entity)) {
        $found_items = $pluginInstance->process($entity);
        $items = array_merge($items, $found_items);
        if ($entity instanceof GroupContentInterface) {
          $childEntity = $entity->getEntity();
          $found_items = $pluginInstance->process($childEntity);
          $items = array_merge($items, $found_items);
        }
      }
    }
    $plugins_by_group_type = [];
    foreach ($items as $item) {
      // Build the instance ID.
      $instance_id = 'group_media:' . $item->bundle();
      // Check if this media type should be group content or not.
      if (in_array($instance_id, $group_content_instance_ids)) {
        // Check what relations already exist for this media to control the
        // group cardinality.
        $group_contents = $this->groupContentStorage->loadByEntity($item);
        $group_ids = [];
        /** @var \Drupal\group\Entity\GroupContentInterface $instance */
        foreach ($group_contents as $instance) {
          $group_ids[] = $instance->getGroup()->id();
        }
        $group_count = count(array_unique($group_ids));
        foreach ($groups as $group) {
          if (!$this->shouldBeAttached($item, $group)) {
            continue;
          }
          if (!isset($plugins_by_group_type[$group->bundle()])) {
            $plugins_by_group_type[$group->bundle()] = $this->groupEnabler->getInstalled($group->getGroupType());
          }
          // Check if the group type supports the plugin of type $instance_id.
          if ($plugins_by_group_type[$group->bundle()]->has($instance_id)) {
            $plugin = $plugins_by_group_type[$group->bundle()]->get($instance_id);
            $group_cardinality = $plugin->getGroupCardinality();
            // Check if group cardinality still allows to create relation.
            if ($group_cardinality == 0 || $group_count < $group_cardinality) {
              $group_relations = $group->getContentByEntityId($instance_id, $item->id());
              $entity_cardinality = $plugin->getEntityCardinality();
              // Add this media as group content if cardinality allows.
              if ($entity_cardinality == 0 || count($group_relations) < $plugin->getEntityCardinality()) {
                $group->addContent($item, $instance_id);
              }
            }
          }
        }
      }
    }
  }

  /**
   * Gets the groups by entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to check.
   *
   * @return \Drupal\group\Entity\GroupInterface[]
   *   Groups that the current entity belongs too.
   */
  public function getContentGroups(EntityInterface $entity) {
    $groups = [];
    if ($entity instanceof GroupContentInterface) {
      $groups[] = $entity->getGroup();
    }
    elseif ($entity instanceof ContentEntityInterface) {
      $group_contents = $this->groupContentStorage->loadByEntity($entity);
      foreach ($group_contents as $group_content) {
        $groups[] = $group_content->getGroup();
      }
    }
    // Allow other modules to alter.
    $this->moduleHandler->alter('groupmedia_entity_group', $groups, $entity);
    return $groups;
  }

  /**
   * Allow other modules to check whether media should be attached to group.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media item to check.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group item to check.
   *
   * @return bool
   *   Returns TRUE if the media should be attached to the group, FALSE in other
   *   case.
   */
  private function shouldBeAttached(MediaInterface $media, GroupInterface $group) {
    $result = [];
    $this->moduleHandler->alter('groupmedia_attach_group', $result, $media, $group);
    if (!is_array($result)) {
      return FALSE;
    }
    // If at least 1 module says "No", the media will not be attached.
    foreach ($result as $item) {
      if (!$item) {
        return FALSE;
      }
    }
    // Otherwise - process.
    return TRUE;
  }

}
