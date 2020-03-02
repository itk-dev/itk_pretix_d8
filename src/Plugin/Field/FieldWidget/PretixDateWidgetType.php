<?php

namespace Drupal\itk_pretix\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Plugin implementation of the 'pretix_date_widget_type' widget.
 *
 * @FieldWidget(
 *   id = "pretix_date_widget_type",
 *   module = "itk_pretix",
 *   label = @Translation("Pretix date widget type"),
 *   field_types = {
 *     "pretix_date_field_type"
 *   }
 * )
 */
class PretixDateWidgetType extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['uuid'] = [
      '#type' => 'hidden',
      '#default_value' => $items[$delta]->uuid ?? '',
    ];

    $element['location'] = [
      '#type' => 'textfield',
      '#title' => t('Location'),
      '#default_value' => isset($items[$delta]->location) ? $items[$delta]->location : '',
      '#size' => 45,
    ];
    $element['address'] = [
      '#type' => 'search',
      '#title' => t('Address'),
      '#default_value' => isset($items[$delta]->address) ? $items[$delta]->address : '',
      '#size' => 45,
      '#attached' => [
        'library' => [
          'itk_pretix/itk-pretix',
        ],
      ],
      '#attributes' => ['class' => ['js-dawa-element']],
    ];

    if ($items[$delta]->time_from) {
      $datePartsFrom = explode(' ', $items[$delta]->time_from);
    }
    $element['time_from'] = [
      '#type' => 'datetime',
      '#title' => t('Start time'),
      '#default_value' => isset($datePartsFrom) ? DrupalDateTime::createFromFormat('Y-m-d H:i:s', $datePartsFrom[0] . ' ' . $datePartsFrom[1], $datePartsFrom[2]) : DrupalDateTime::createFromTimestamp($this->roundedTime(time())),
      '#date_date_element' => 'date',
      '#date_time_element' => 'time',
      '#date_date_format' => 'd/m/Y',
      '#date_time_format' => 'H:i',
      '#size' => 15,
    ];

    if ($items[$delta]->time_to) {
      $datePartsTo = explode(' ', $items[$delta]->time_to);
    }
    $element['time_to'] = [
      '#type' => 'datetime',
      '#title' => t('End time'),
      '#default_value' => isset($datePartsTo) ? DrupalDateTime::createFromFormat('Y-m-d H:i:s', $datePartsTo[0] . ' ' . $datePartsTo[1], $datePartsTo[2]) : DrupalDateTime::createFromTimestamp($this->roundedTime(time())),
      '#date_date_element' => 'date',
      '#date_time_element' => 'time',
      '#date_date_format' => 'd/m/Y',
      '#date_time_format' => 'H:i',
      '#size' => 15,
    ];
    $element['spots'] = [
      '#type' => 'number',
      '#title' => t('Number of spots'),
      '#default_value' => isset($items[$delta]->spots) ? $items[$delta]->spots : '',
      '#size' => 3,
    ];

    if (isset($item->uuid)) {
      $pretixOrdersListUrl = $item->getEntity()->id()
        ? Url::fromRoute('itk_pretix.pretix_orders_date',
          [
            'node' => $item->getEntity()->id(),
            'uuid' => $item->uuid,
          ], [
            'absolute' => TRUE,
          ]
        )
        : NULL;

      $element['data'] = [
        '#theme' => 'itk_pretix_date_data',
        '#data' => array_merge(
          $item->data ?? [],
          $eventHelper->loadPretixSubEventInfo($item) ?? [],
          [
            'pretix_orders_list_url' => $pretixOrdersListUrl,
          ]
        ),
      ];
    }

    // If cardinality is 1, ensure a label is output for the field by wrapping
    // it in a details element.
    if ($this->fieldDefinition->getFieldStorageDefinition()->getCardinality() == 1) {
      $element += [
        '#type' => 'fieldset',
        '#attributes' => ['class' => ['container-inline']],
      ];
    }

    return $element;
  }

  /**
   * Round seconds to nearest hour.
   *
   * @param int $seconds
   *   A timestamp.
   *
   * @return float|int
   *   A rounded timestamp.
   */
  private function roundedTime($seconds) {
    return round($seconds / 3600) * 3600;
  }

}
