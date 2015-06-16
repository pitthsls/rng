<?php

/**
 * @file
 * Contains \Drupal\rng\Plugin\Condition\RuleScheduler.
 */

namespace Drupal\rng\Plugin\Condition;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rng\Entity\RuleSchedule;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Schedules rule execution based on the configured date.
 *
 * @Condition(
 *   id = "rng_rule_scheduler",
 *   label = @Translation("Rule scheduler")
 * )
 */
class RuleScheduler extends CurrentTime implements ContainerFactoryPluginInterface {

  protected $schedulerStorage;

  /**
   * Constructs a RegistrantBasicEmail object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->schedulerStorage = $entity_manager->getStorage('rng_rule_scheduler');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   - rng_rule_component: integer: Associated rule component ID.
   *   - rng_rule_scheduler: integer: ID of a rng_rule_schedule entity.
   *     If the ID is of a non-existent rng_rule_schedule entity, assume the
   *     rule has been executed successfully.
   *     The parent date configuration value is mirrored to the
   *     rng_rule_schedule entity when this form is saved.
   *   - negate: has no effect.
   */
  public function defaultConfiguration() {
    return [
      'rng_rule_component' => NULL,
      'rng_rule_scheduler' => NULL,
    ] + parent::defaultConfiguration();
  }


  public function getRuleComponentId() {
    if (isset($this->configuration['rng_rule_component'])) {
      return $this->configuration['rng_rule_component'];
    }
    return NULL;
  }

  public function getRuleScheduler() {
    if (isset($this->configuration['rng_rule_scheduler'])) {
      return RuleSchedule::load($this->configuration['rng_rule_scheduler']);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['date']['#description'] = t('Rule will trigger once on this date.');
    unset ($form['negate']);
    return $form;
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['negate'] = FALSE;

    // Create new scheduler if rule_component is provided
    if ($this->getRuleComponentId() && !$this->getRuleScheduler()) {
      $rule_scheduler = RuleSchedule::create([
        'component' => $this->getRuleComponentId(),
      ]);
      $rule_scheduler->save();
      $this->configuration['rng_rule_scheduler'] = $rule_scheduler->id();
    }

    // Mirror the date into the scheduler
    if ($rule_scheduler = $this->getRuleScheduler()) {
      $rule_scheduler->setDate($this->configuration['date']);
      $rule_scheduler->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $rule_scheduler = $this->getRuleScheduler();
    if ($rule_scheduler) {
      return $this->t('Current date is after @date', [
        '@date' => DrupalDateTime::createFromTimestamp($rule_scheduler->getDate()),
      ]);
    }
    else {
      return $this->t('Current date is after a date');
    }
  }

}