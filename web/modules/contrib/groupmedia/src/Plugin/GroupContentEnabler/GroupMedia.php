<?php

namespace Drupal\groupmedia\Plugin\GroupContentEnabler;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\GroupContentEnablerBase;
use Drupal\media\Entity\MediaType;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a content enabler for nodes.
 *
 * @GroupContentEnabler(
 *   id = "group_media",
 *   label = @Translation("Group media"),
 *   description = @Translation("Adds media items to groups both publicly and privately."),
 *   entity_type_id = "media",
 *   entity_access = TRUE,
 *   reference_label = @Translation("Title"),
 *   reference_description = @Translation("The title of the media to add to the group"),
 *   deriver = "Drupal\groupmedia\Plugin\GroupContentEnabler\GroupMediaDeriver",
 *   handlers = {
 *     "access" = "Drupal\group\Plugin\GroupContentAccessControlHandler",
 *     "permission_provider" = "Drupal\groupmedia\Plugin\GroupMediaPermissionProvider",
 *   }
 * )
 */
class GroupMedia extends GroupContentEnablerBase {

  /**
   * Retrieves the media type this plugin supports.
   *
   * @return \Drupal\media\MediaTypeInterface
   *   The media type this plugin supports.
   */
  protected function getMediaType() {
    return MediaType::load($this->getEntityBundle());
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    $account = \Drupal::currentUser();
    $plugin_id = $this->getPluginId();
    $type = $this->getEntityBundle();
    $operations = [];

    if ($group->hasPermission("create $plugin_id entity", $account)) {
      $route_params = ['group' => $group->id(), 'plugin_id' => $plugin_id];
      $operations["groupmedia-create-$type"] = [
        'title' => $this->t('Create @type', ['@type' => $this->getMediaType()->label()]),
        'url' => new Url('entity.group_content.create_form', $route_params),
        'weight' => 30,
      ];
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['entity_cardinality'] = 1;
    $config['tracking_enabled'] = 0;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['tracking_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable media tracking'),
      '#description' => $this->t('If enabled the system would try to attach media items referenced in group content to corresponding group'),
      '#default_value' => $this->configuration['tracking_enabled'],
    ];

    // Disable the entity cardinality field as the functionality of this module
    // relies on a cardinality of 1. We don't just hide it, though, to keep a UI
    // that's consistent with other content enabler plugins.
    $info = $this->t("This field has been disabled by the plugin to guarantee the functionality that's expected of it.");
    $form['entity_cardinality']['#disabled'] = TRUE;
    $form['entity_cardinality']['#description'] .= '<br /><em>' . $info . '</em>';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $dependencies['config'][] = 'media.type.' . $this->getEntityBundle();
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function isTrackingEnabled() {
    return $this->configuration['tracking_enabled'] == 1;
  }

}
