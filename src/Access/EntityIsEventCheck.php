<?php

/**
 * @file
 * Contains \Drupal\rng\Access\EntityIsEventCheck.
 */

namespace Drupal\rng\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\rng\EventManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Checks that an entity is an event type.
 */
class EntityIsEventCheck implements AccessInterface {

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Constructs a new EntityIsEventCheck object.
   *
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(EventManagerInterface $event_manager) {
    $this->eventManager = $event_manager;
  }

  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    if ($event = $route->getDefault('event')) {
      $event = $route_match->getParameter($event);
      if ($this->eventManager->event_type($event->getEntityTypeId(), $event->bundle())) {
        return AccessResult::allowed();
      }
    }
    return AccessResult::neutral();
  }

}