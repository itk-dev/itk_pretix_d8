<?php

namespace Drupal\itk_pretix\Pretix;

use Drupal\Core\Database\Connection;
use Drupal\itk_pretix\Exception\SynchronizeException;
use Drupal\node\NodeInterface;
use ItkDev\Pretix\Client\Client;
use ItkDev\Pretix\Entity\Event;
use ItkDev\Pretix\Entity\Quota;

/**
 * Pretix helper.
 */
class EventHelper extends AbstractHelper {
  /**
   * The order helper.
   *
   * @var \Drupal\itk_pretix\Pretix\OrderHelper
   */
  private $orderHelper;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database.
   * @param \Drupal\itk_pretix\Pretix\OrderHelper $orderHelper
   *   The order helper.
   */
  public function __construct(Connection $database, OrderHelper $orderHelper) {
    parent::__construct($database);
    $this->orderHelper = $orderHelper;
  }

  /**
   * Synchronize pretix event with a node.
   */
  public function syncronizePretixEvent(NodeInterface $node, array $options) {
    $client = $this->getPretixClient($node);

    $info = $this->loadPretixEventInfo($node);
    $isNewEvent = NULL === $info;

    $dates = $options['dates'] ?? NULL;
    $settings = $options['settings'] ?? NULL;

    if (empty($dates)) {
      throw new SynchronizeException($this->t('No dates specified'));
    }

    if (!isset($settings['template_event'])) {
      throw new SynchronizeException($this->t('No template event specified'));
    }
    $templateEventSlug = $settings['template_event'];

    $name = $this->getEventName($node);
    // Get location from first date.
    $location = $this->getLocation(reset($dates));
    $startDate = reset($dates)['time_from'];

    // @TODO Handle locales?
    $data = [
      'name' => ['en' => $name],
      'currency' => 'DKK',
      'date_from' => $this->formatDate($startDate),
      'is_public' => $node->isPublished(),
      'location' => ['en' => $location],
    ];

    $eventData = [];
    if ($isNewEvent) {
      $data['slug'] = $this->getEventSlug($node);
      // has_subevents is not cloned from source event.
      $data['has_subevents'] = TRUE;
      try {
        $event = $client->cloneEvent($templateEventSlug, $data);
      }
      catch (\Exception $exception) {
        throw new SynchronizeException($this->t('Cannot clone event'), 0, $exception);
      }
      $eventData['template_event_slug'] = $templateEventSlug;
    }
    else {
      try {
        $event = $client->updateEvent($info['pretix_event_slug'], $data);
      }
      catch (\Exception $exception) {
        throw new SynchronizeException($this->t('Cannot update event'), 0, $exception);
      }
    }

    $eventData['event'] = $event->toArray();
    $info = $this->addPretixEventInfo($node, $event, $eventData);
    $subEvents = $this->synchronizePretixSubEvents($event, $node, $dates, $client);

    foreach ($subEvents as $subEvent) {
      if (isset($subEvent['error'])) {
        return $subEvent;
      }
    }

    return [
      'status' => $isNewEvent ? 'created' : 'updated',
      'info' => $info,
      'subevents' => $subEvents ?? NULL,
    ];
  }

  /**
   * Synchronize pretix sub-events.
   *
   * @param \ItkDev\Pretix\Entity\Event $event
   *   The event.
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   * @param array $dates
   *   The dates.
   * @param \ItkDev\Pretix\Client\Client $client
   *   The client.
   *
   * @return array
   *   The sub-event info.
   *
   * @throws \Exception
   */
  public function synchronizePretixSubEvents(Event $event, NodeInterface $node, array $dates, Client $client) {
    $info = [];
    $subEventIds = [];
    foreach ($dates as $date) {
      $result = $this->synchronizePretixSubEvent($date, $event, $node, $client);
      if (isset($result['info'])) {
        $subEventIds[] = $result['info']['pretix_subevent_id'];
      }
      $info[] = $result;
    }

    foreach ($info as $subEvent) {
      if (isset($subEvent['error'])) {
        return $info;
      }
    }

    // Delete pretix sub-events that no longer exist in Drupal.
    $pretixSubEventIds = [];
    try {
      $subEvents = $client->getSubEvents($event);
      foreach ($subEvents as $subEvent) {
        if (!in_array($subEvent->getId(), $subEventIds, TRUE)) {
          $client->deleteSubEvent($event, $subEvent);
        }
        $pretixSubEventIds[] = $subEvent->getId();
      }
    }
    catch (\Exception $exception) {
      // @TODO Do something clever here.
    }

    // @TODO Clean up info on pretix sub-events.
    return $info;
  }

  /**
   * Synchronize pretix sub-event.
   *
   * @param object $item
   *   The item.
   * @param \ItkDev\Pretix\Entity\Event $event
   *   The event.
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   * @param \ItkDev\Pretix\Client\Client $client
   *   The client.
   *
   * @return array
   *   On success, the sub-event info. Otherwise an error.
   *
   * @throws \Exception
   */
  private function synchronizePretixSubEvent($item, Event $event, NodeInterface $node, Client $client) {
    $itemInfo = $this->loadPretixSubEventInfo($item, TRUE);
    $isNewItem = NULL === $itemInfo;

    $templateEvent = $this->getPretixTemplateEventSlug($node);
    try {
      $templateSubEvents = $client->getSubEvents($templateEvent);
    }
    catch (\Exception $exception) {
      throw $this->clientException($this->t('Cannot get template event sub-events'), $exception->getCode(), $exception);
    }
    if (0 === $templateSubEvents->count()) {
      throw $this->clientException($this->t('Cannot get template event sub-events'));
    }
    $templateSubEvent = $templateSubEvents->first();

    $product = NULL;
    $data = [];
    if ($isNewItem) {
      // Get first sub-event from template event.
      try {
        $items = $client->getItems($event);
      }
      catch (\Exception $exception) {
        throw $this->clientException($this->t('Cannot get template event items'), $exception);
      }
      if (0 === $items->count()) {
        throw $this->clientException($this->t('Missing items on template event'));
      }

      // Always use the first product.
      $product = $items->first();

      $data = $templateSubEvent->toArray();
      // Remove the template id.
      unset($data['id']);

      $data['item_price_overrides'] = [
        [
          'item' => $product->getId(),
        ],
      ];
    }
    else {
      $data = $itemInfo['data']['subevent'];
    }

    $location = $this->getLocation($item);
    // @TODO Handle geo location.
    $geoLat = NULL;
    $geoLng = NULL;

    // @TODO Handle locales.
    $data = array_merge($data, [
      'name' => ['en' => $this->getEventName($node)],
      'date_from' => $this->formatDate($item['time_from']),
      'date_to' => $this->formatDate($item['time_to']),
      'location' => ['en' => $location],
      'geo_lat' => $geoLat,
      'get_lng' => $geoLng,
      'active' => TRUE,
      'is_public' => TRUE,
      'date_admission' => NULL,
      'presale_end' => NULL,
      'seating_plan' => NULL,
      'seat_category_mapping' => (object) [],
    ]);

    // @TODO Handle prices.
    $price = 0;
    $data['item_price_overrides'][0]['price'] = $price;

    // Important: meta_data value must be an object!
    $data['meta_data'] = (object) [];
    $subEventData = [];
    if ($isNewItem) {
      try {
        $subEvent = $client->createSubEvent($event, $data);
      }
      catch (\Exception $exception) {
        throw $this->clientException($this->t('Cannot create sub-event'), $exception);
      }
    }
    else {
      $subEventId = $itemInfo['pretix_subevent_id'];
      try {
        $subEvent = $client->updateSubEvent($event, $subEventId, $data);
      }
      catch (\Exception $exception) {
        throw $this->clientException($this->t('Cannot update sub-event'), $exception);
      }
    }

    // Get sub-event quotas.
    try {
      $quotas = $client->getQuotas($event,
        ['query' => ['subevent' => $subEvent->getId()]]);
    }
    catch (\Exception $exception) {
      throw $this->clientException($this->t('Cannot get sub-event quotas'), $exception);
    }

    if (0 === $quotas->count()) {
      // Create a new quota for the sub-event.
      try {
        $templateQuotas = $client->getQuotas(
          $templateEvent,
          ['subevent' => $templateSubEvent->getId()]
        );
      }
      catch (\Exception $exception) {
        throw $this->clientException($this->t('Cannot get template sub-event quotas'), $exception);
      }
      if (0 === $templateQuotas->count()) {
        throw $this->clientException($this->t('Missing sub-event quotas on template'));
      }

      $quotaData = $templateQuotas->first()->toArray();
      unset($quotaData['id']);
      $quotaData = array_merge($quotaData, [
        'subevent' => $subEvent->getId(),
        'items' => [$product->getId()],
        'size' => (int) $item['spots'],
      ]);
      try {
        $quota = $client->createQuota($event, $quotaData);
      }
      catch (\Exception $exception) {
        throw $this->clientException($this->t('Cannot create quota for sub-event'), $exception);
      }
    }

    $subEventData['subevent'] = $subEvent;
    try {
      $availabilities = $this->orderHelper
        ->setPretixClient($client)
        ->getSubEventAvailabilities($subEvent);
      $subEventData['availability'] = $availabilities->map(static function (Quota $quota) {
        return $quota->toArray();
      })->toArray();
    }
    catch (\Exception $exception) {
      $exception->getCode();
    }

    $info = $this->addPretixSubEventInfo($item, $subEvent, $subEventData);

    return [
      'status' => $isNewItem ? 'created' : 'updated',
      'info' => $info,
    ];
  }

  /**
   * Set event live (or not) in pretix.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   * @param bool $live
   *   The live-ness of the node's event.
   *
   * @return array
   *   The event info.
   *
   * @throws \InvalidMergeQueryException
   */
  public function setEventLive(NodeInterface $node, bool $live) {
    // Note: 'live' is updated for all events including the ones that are not
    // synchronized with pretix.
    $info = $this->loadPretixEventInfo($node);
    $client = $this->getPretixClient($node);
    try {
      $event = $client->getEvent($info['pretix_event_slug']);
    }
    catch (\Exception $exception) {
      throw $this->clientException($this->t('Cannot get event'), $exception);
    }

    try {
      $event = $client->updateEvent($event, ['live' => $live]);
    }
    catch (\Exception $exception) {
      throw $this->clientException($live ? $this->t('Cannot set pretix event live') : $this->t('Cannot set pretix event not live'), $exception);
    }

    $info = $this->addPretixEventInfo($node, $event, ['event' => $event->toArray()]);

    return [
      'status' => $live ? 'live' : 'not live',
      'info' => $info,
    ];
  }

  /**
   * Get pretix template event slug.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return string|null
   *   The template event slug if any.
   */
  private function getPretixTemplateEventSlug(NodeInterface $node) {
    $info = $this->loadPretixEventInfo($node);

    return $info['data']['template_event_slug'] ?? NULL;
  }

  /**
   * Get pretix event slug.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return string
   *   The event slug.
   */
  private function getEventSlug(NodeInterface $node) {
    $template = $this->configuration['pretix_event_slug_template'] ?? '!nid';

    return str_replace(['!nid'], [$node->id()], $template);
  }

  /**
   * Get event name from a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return string
   *   The event name.
   */
  private function getEventName(NodeInterface $node) {
    return $node->getTitle();
  }

  /**
   * Get item location.
   *
   * @param array|null $item
   *   The item.
   *
   * @return string
   *   The event location.
   */
  private function getLocation(array $item = NULL) {
    if (NULL === $item) {
      return NULL;
    }

    return implode(PHP_EOL, array_filter([
      $item['location'] ?? NULL,
      $item['address'] ?? NULL,
    ]));
  }

  /**
   * Get data from some value.
   *
   * @param mixed $value
   *   Something that may be converted to a DateTime.
   *
   * @return \DateTime|null
   *   The date.
   *
   * @throws \Exception
   */
  private function getDate($value) {
    if (NULL === $value) {
      return NULL;
    }

    if ($value instanceof \DateTime) {
      return $value;
    }

    if (is_numeric($value)) {
      return new \DateTime('@' . $value);
    }

    return new \DateTime($value);
  }

  /**
   * Format a date as a string.
   *
   * @param mixed|null $date
   *   The date.
   *
   * @return string|null
   *   The string representation of the date.
   *
   * @throws \Exception
   */
  private function formatDate($date = NULL) {
    $date = $this->getDate($date);

    if (NULL === $date) {
      return NULL;
    }

    return $date->format(\DateTime::ATOM);
  }

}
