<?php

namespace Drupal\itk_pretix\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;

/**
 * Access check.
 */
class AccessCheck implements AccessInterface {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, RouteMatchInterface $routeMatch) {
    $node = $routeMatch->getParameter('node');
    if ($node instanceof NodeInterface && $account->isAuthenticated()) {
      $user = User::load($account->id());
      if ($node->access('update', $user)) {
        return AccessResult::allowed();
      }
    }

    return AccessResult::forbidden();
  }

}
