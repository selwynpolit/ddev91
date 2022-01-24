<?php

namespace Drupal\group\Plugin\views\relationship;

use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\GroupContentType;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Drupal\views\Plugin\ViewsHandlerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A relationship handler base for group content entity references.
 */
abstract class GroupContentToEntityBase extends RelationshipPluginBase {

  /**
   * The Views join plugin manager.
   *
   * @var \Drupal\views\Plugin\ViewsHandlerManager
   */
  protected $joinManager;

  /**
   * The group content enabler plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs an GroupContentToEntityBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\views\Plugin\ViewsHandlerManager $join_manager
   *   The views plugin join manager.
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager
   *   The group content enabler plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ViewsHandlerManager $join_manager, GroupContentEnablerManagerInterface $plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->joinManager = $join_manager;
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.views.join'),
      $container->get('plugin.manager.group_content_enabler')
    );
  }

  /**
   * Retrieves the entity type ID this plugin targets.
   *
   * Do not return 'group_content', but the actual entity type ID you're trying
   * to link up to the group_content entity type.
   *
   * @return string
   *   The target entity type ID.
   */
  abstract protected function getTargetEntityType();

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['group_content_plugins']['default'] = [];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Retrieve all of the plugins that can serve this entity type.
    $options = [];
    foreach ($this->pluginManager->getAll() as $plugin_id => $plugin) {
      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      if ($plugin->getEntityTypeId() === $this->getTargetEntityType()) {
        $options[$plugin_id] = $plugin->getLabel();
      }
    }

    $form['group_content_plugins'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Filter by plugin'),
      '#description' => $this->t('Refine the result by plugin. Leave empty to select all plugins, including those that could be added after this relationship was configured.'),
      '#options' => $options,
      '#weight' => -2,
      '#default_value' => $this->options['group_content_plugins'],
    ];
  }

  /**
   * Returns the group content types this relationship should filter on.
   *
   * This checks if any plugins were selected on the option form and, in that
   * case, loads only those group content types available to the selected
   * plugins. Otherwise, all possible group content types for the relationship's
   * entity type are loaded.
   *
   * This needs to happen live to cover the use case where a group content
   * plugin is installed on a group type after this relationship has been
   * configured on a view without any plugins selected.
   *
   * @todo Could be cached even more, I guess.
   *
   * @return string[]
   *   The group content type IDs to filter on.
   */
  protected function getGroupContentTypeIds() {
    $plugin_ids = array_filter($this->options['group_content_plugins']);

    $group_content_type_ids = [];
    foreach ($plugin_ids as $plugin_id) {
      $group_content_type_ids = array_merge($group_content_type_ids, $this->pluginManager->getGroupContentTypeIds($plugin_id));
    }

    return $plugin_ids ? $group_content_type_ids : array_keys(GroupContentType::loadByEntityTypeId($this->getTargetEntityType()));
  }

  /**
   * Returns the list of group content types for a query.
   *
   * We can't run an IN-query on an empty array. So if there are no group
   * content types yet, we need to make sure the JOIN does not return any GCT
   * that does not serve the entity type that was configured for this handler
   * instance.
   *
   * @return array
   *   The list of group content types to be used as extra JOIN condition.
   */
  protected function getGroupContentTypesValue() {
    $group_content_type_ids = $this->getGroupContentTypeIds();
    if (empty($group_content_type_ids)) {
      $group_content_type_ids = ['***'];
    }
    return $group_content_type_ids;
  }

}
