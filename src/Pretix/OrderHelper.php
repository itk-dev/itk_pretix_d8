<?php

namespace Drupal\itk_pretix\Pretix;

use Drupal\node\NodeInterface;
use ItkDev\Pretix\Entity\SubEvent;

/**
 * Pretix order helper.
 */
class OrderHelper extends AbstractHelper {

  const PRETIX_EVENT_ORDER_PLACED = 'pretix.event.order.placed';

  const PRETIX_EVENT_ORDER_PLACED_REQUIRE_APPROVAL = 'pretix.event.order.placed.require_approval';

  const PRETIX_EVENT_ORDER_PAID = 'pretix.event.order.paid';

  const PRETIX_EVENT_ORDER_CANCELED = 'pretix.event.order.canceled';

  const PRETIX_EVENT_ORDER_EXPIRED = 'pretix.event.order.expired';

  const PRETIX_EVENT_ORDER_MODIFIED = 'pretix.event.order.modified';

  const PRETIX_EVENT_ORDER_CONTACT_CHANGED = 'pretix.event.order.contact.changed';

  const PRETIX_EVENT_ORDER_CHANGED = 'pretix.event.order.changed.*';

  const PRETIX_EVENT_ORDER_REFUND_CREATED_EXTERNALLY = 'pretix.event.order.refund.created.externally';

  const PRETIX_EVENT_ORDER_APPROVED = 'pretix.event.order.approved';

  const PRETIX_EVENT_ORDER_DENIED = 'pretix.event.order.denied';

  const PRETIX_EVENT_CHECKIN = 'pretix.event.checkin';

  const PRETIX_EVENT_CHECKIN_REVERTED = 'pretix.event.checkin.reverted';

  /**
   * Get pretix order augmented with quota information and expanded sub-events.
   *
   * @param string|object $organizer
   *   The organizer (slug).
   * @param string|object $event
   *   The event (slug).
   * @param string $orderCode
   *   The order code.
   */
  public function getOrder($organizer, $event, $orderCode) {
    throw new \RuntimeException(__METHOD__ . ' not implemented');
  }

  /**
   * Get order lines grouped by sub-event.
   *
   * @param object $order
   *   The pretix order.
   *
   * @throws \Exception
   */
  public function getOrderLines($order) {
    throw new \RuntimeException(__METHOD__ . ' not implemented');
  }

  /**
   * Get availability information for a pretix event.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   */
  public function getAvailability(NodeInterface $node) {
    throw new \RuntimeException(__METHOD__ . ' not implemented');
  }

  /**
   * Get sub-event availability from pretix.
   *
   * @param \ItkDev\Pretix\Entity\SubEvent $subEvent
   *   The sub-event.
   *
   * @return \Doctrine\Common\Collections\Collection
   *   A pretix API result with quotas enriched with availability information.
   */
  public function getSubEventAvailabilities(SubEvent $subEvent) {
    $event = $subEvent->getEvent();
    try {
      $quotas = $this->pretixClient
        ->getQuotas($event, ['query' => ['subevent' => $subEvent->getId()]]);
    }
    catch (\Exception $exception) {
      throw $this->clientException($this->t('Cannot get quotas for sub-event'), $exception);
    }

    foreach ($quotas as $quota) {
      try {
        $availability = $this->pretixClient->getQuotaAvailability($event, $quota);
      }
      catch (\Exception $exception) {
        throw $this->clientException($this->t('Cannot get quota availability'), $exception);
      }
      $quota->setAvailability($availability);
    }

    return $quotas;
  }

}
