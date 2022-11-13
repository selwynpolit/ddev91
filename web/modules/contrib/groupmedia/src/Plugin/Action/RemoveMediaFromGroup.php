<?php

namespace Drupal\groupmedia\Plugin\Action;

use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\groupmedia\AttachMediaToGroup;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Remove media from Group.
 *
 * @Action(
 *   id = "remove_media_from_group",
 *   label = @Translation("Remove media from a Group"),
 *   type = "media"
 * )
 */
class RemoveMediaFromGroup extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\groupmedia\AttachMediaToGroup
   */
  protected $attachMediaToGroup;

  /**
   * AssignMediaToGroup constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\groupmedia\AttachMediaToGroup $attachMediaToGroup
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

}
