<?php

namespace Drupal\itk_pretix;

use Doctrine\Common\Collections\ArrayCollection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\itk_pretix\Pretix\EventHelper;

/**
 * Node helper.
 */
class NodeHelper {
  use StringTranslationTrait;
  use MessengerTrait;

  /**
   * The event helper.
   *
   * @var \Drupal\itk_pretix\Pretix\EventHelper
   */
  private $eventHelper;

  /**
   * Constructor.
   *
   * @param \Drupal\itk_pretix\Pretix\EventHelper $eventHelper
   *   The event helper.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(EventHelper $eventHelper, MessengerInterface $messenger) {
    $this->eventHelper = $eventHelper;
    $this->setMessenger($messenger);
  }

  /**
   * Get template events available for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return \Doctrine\Common\Collections\Collection
   *   A collection of template events.
   */
  public function getTemplateEvents(NodeInterface $node) {
    $field = $this->getFieldByType($node, 'pretix_date_field_type');
    $dateCardinality = $field->getFieldDefinition()->getFieldStorageDefinition()->getCardinality();
    $events = [];

    $client = $this->eventHelper->getPretixClient($node);
    $config = $this->eventHelper->getPretixConfiguration();
    $templateEventSlugs = array_unique(array_filter(array_map('trim', explode(PHP_EOL, $config['template_event_slugs'] ?? ''))));
    foreach ($templateEventSlugs as $slug) {
      try {
        $event = $client->getEvent($slug);
        $events[] = $event;
      }
      catch (\Exception $exception) {
      }
    }

    return new ArrayCollection($events);
  }

  /**
   * Synchronize a node with an event in pretix.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   * @param string $action
   *   The action triggering the synchronization.
   */
  public function synchronizeEvent(NodeInterface $node, string $action) {
    $dates = $this->getPretixDates($node);

    if (NULL !== $dates) {
      $settings = $this->getPretixSettings($node);
      if (!isset($settings['synchronize_event'])) {
        return;
      }

      try {
        $this->eventHelper->syncronizePretixEvent(
          $node,
          [
            'dates' => $dates,
            'settings' => $settings,
          ]
        );
      }
      catch (\Exception $exception) {
        $this->messenger->addError($this->t('There was a problem updating the event in pretix. Please verify in pretix that all settings for the event are correct.'));
        $this->messenger->addError($exception->getMessage());
        return;
      }

      $pretixEventUrl = $this->eventHelper->getPretixEventUrl($node);
      $this->messenger->addStatus($this->t('Successfully updated <a href="@pretix_event_url">the event in pretix</a>.', [
        '@pretix_event_url' => $pretixEventUrl,
      ]));

      $live = $node->isPublished();
      try {
        $result = $this->eventHelper->setEventLive($node, $live);

        $message = $live
          ? t('Successfully set <a href="@pretix_event_url">the pretix event</a> live.', [
            '@pretix_event_url' => $pretixEventUrl,
          ])
            : t('Successfully set <a href="@pretix_event_url">the pretix event</a> not live.', [
              '@pretix_event_url' => $pretixEventUrl,
            ]);
        $this->messenger->addStatus($message);
      }
      catch (\Exception $exception) {
        $error = $exception->getMessage();
        $message = $live
          ? t('Error setting <a href="@pretix_event_url">the pretix event</a> live: @error', [
            '@pretix_event_url' => $pretixEventUrl,
            '@error' => $error,
          ])
              : t('Error setting <a href="@pretix_event_url">the pretix event</a> not live: @error', [
                '@pretix_event_url' => $pretixEventUrl,
                '@errors' => $error,
              ]);
        $this->messenger->addError($message);
      }
    }
  }

  /**
   * Get dates from a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return array|null
   *   A list of dates if a pretix_date field exists on the node.
   */
  private function getPretixDates(NodeInterface $node) {
    $field = $this->getFieldByType($node, 'pretix_date_field_type');

    if (NULL !== $field) {
      $dates = $field->getValue();

      foreach ($dates as &$date) {
        foreach (['time_from', 'time_to'] as $key) {
          if (isset($date[$key]) && is_string($date[$key])) {
            $date[$key] = new DrupalDateTime($date[$key]);
          }
        }
      }

      return $dates;
    }

    return NULL;
  }

  /**
   * Get pretix settings for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return array|null
   *   The settings if a pretix_event_settings field exists on the node.
   */
  public function getPretixSettings(NodeInterface $node) {
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
   * Decide if node must be synchronized with pretix.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return bool
   *   The result.
   */
  public function getSynchronizeWithPretix(NodeInterface $node) {
    return $this->getPretixSettings($node)['synchronize_event'] ?? FALSE;
  }

  /**
   * Get a node field by type.
   *
   * Returns only the first matching field found.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   * @param string $fieldType
   *   The field type.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|null
   *   A field with the specified type if found.
   */
  private function getFieldByType(NodeInterface $node, string $fieldType) {
    $fields = $node->getFields();
    foreach ($fields as $field) {
      if ($fieldType === $field->getFieldDefinition()->getType()) {
        return $field;
      }
    }

    return NULL;
  }

}
