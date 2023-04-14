<?php

namespace Drupal\groupmedia_vbo\Plugin\Action;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\groupmedia\AttachMediaToGroup;
use Drupal\media\MediaInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Remove media from Group.
 *
 * @Action(
 *   id = "vbo_remove_media_from_group",
 *   label = @Translation("VBO: Remove media from a Group"),
 *   type = "media"
 * )
 */
class RemoveMediaFromGroup extends ViewsBulkOperationsActionBase implements PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Attach to media service.
   *
   * @var \Drupal\groupmedia\AttachMediaToGroup
   */
  protected $attachMediaToGroup;

  /**
   * AssignMediaToGroup constructor.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\groupmedia\AttachMediaToGroup $attachMediaToGroup
   *   Attach to media service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, AttachMediaToGroup $attachMediaToGroup) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->attachMediaToGroup = $attachMediaToGroup;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('groupmedia.attach_group')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute(MediaInterface $media = NULL) {
    $plugin_id = 'group_media:' . $media->bundle();
    $group_content_types = $this->entityTypeManager
      ->getStorage('group_content_type')
      ->loadByContentPluginId($plugin_id);
    if (empty($group_content_types)) {
      return;
    }
    $group_contents = $this->entityTypeManager
      ->getStorage('group_content')
      ->loadByProperties([
        'type' => array_keys($group_content_types),
        'entity_id' => $media->id(),
        'gid' => $this->configuration['group_id'],
      ]);
    foreach ($group_contents as $group_content) {
      $group_content->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['group_id'] = [
      '#title' => $this->t('Remove from Group'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'group',
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['group_id'] = $form_state->getValue('group_id');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\media\MediaInterface $object */
    $result = $object->access('update', $account, TRUE);

    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Method required by base class.
  }

}
