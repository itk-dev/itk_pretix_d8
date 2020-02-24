<?php

namespace Drupal\itk_pretix;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\Entity\Node;
use ItkDev\Pretix\Client\Client;
use ItkDev\Pretix\Entity\Event;
use Drupal\itk_pretix\Pretix\EventHelper;

/**
 *
 */
class NodeHelper {
  /**
   * @var \Drupal\itk_pretix\Pretix\EventHelper*/
  private $eventHelper;

  /**
   * @var \ItkDev\Pretix\Client\Client
   */
  private $pretixClient;

  /**
   * @param \Drupal\itk_pretix\Pretix\EventHelper $eventHelper
   */
  public function __construct(EventHelper $eventHelper) {
    $this->eventHelper = $eventHelper;
  }

  /**
   * Get template events available for a node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node.
   *
   * @return \Doctrine\Common\Collections\Collection
   *   A collection of template events.
   */
  public function getTemplateEvents(Node $node) {
    $field = $this->getFieldByType($node, 'pretix_date_field_type');
    $dateCardinality = $field->getFieldDefinition()->getFieldStorageDefinition()->getCardinality();
    $events = $this->getPretixClient()->getEvents([
      'query' => [
        'has_subevents' => 1 !== $dateCardinality,
      ],
    ]);

    // @TODO Filter events to get template events.
    return $events->filter(static function (Event $event) {
      return TRUE;
    });
  }

  /**
   * Synchronize a node with an event in pretix.
   *
   * @param \Drupal\node\Entity\Node $node
   * @param string $action
   */
  public function sync(Node $node, string $action) {
    $dates = $this->getPretixDates($node);
    if (NULL !== $dates) {
      $settings = $this->getPretixSettings($node);
      if (!isset($settings['synchronize_event'])) {
        return;
      }
    }
  }

  /**
   * @param \Drupal\node\Entity\Node $node
   *   The node.
   *
   * @return array|null
   *   A list of dates if a pretix_date field exists.
   */
  private function getPretixDates(Node $node) {
    $field = $this->getFieldByType($node, 'pretix_date_field_type');

    if (NULL !== $field) {
      $dates = $field->getValue();

      foreach ($dates as $date) {
        if (isset($date['time_from']) && is_string($date['time_from'])) {
          $date['time_from'] = new DrupalDateTime($date['time_from']);
        }
      }

      return $dates;
    }

    return NULL;
  }

  /**
   * @param \Drupal\node\Entity\Node $node
   *   The node.
   *
   * @return array|null
   *   The settings if a pretix_event_settings field exists.
   */
  private function getPretixSettings(Node $node) {
    $field = $this->getFieldByType($node, 'pretix_event_settings_field_type');

    if (NULL !== $field) {
      return [
        'template_event' => 'template-series',
        'synchronize_event' => TRUE,
      ];
    }

    return NULL;
  }

  /**
   * @param \Drupal\node\Entity\Node $node
   * @param string $fieldType
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|null
   */
  private function getFieldByType(Node $node, string $fieldType) {
    $fields = $node->getFields();
    foreach ($fields as $field) {
      if ($fieldType === $field->getFieldDefinition()->getType()) {
        return $field;
      }
    }

    return NULL;
  }

  /**
   *
   */
  private function getPretixClient() {
    if (NULL === $this->pretixClient) {
      $config = \Drupal::config('itk_pretix.pretixconfig');

      $this->pretixClient = new Client([
        'url' => $config->get('pretix_url'),
        'organizer' => $config->get('organizer_slug'),
        'api_token' => $config->get('api_token'),
      ]);
    }

    return $this->pretixClient;
  }

}
