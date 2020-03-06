<?php

namespace Drupal\itk_pretix\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'pretix_date_formatter_type' formatter.
 *
 * @FieldFormatter(
 *   id = "pretix_date_formatter",
 *   label = @Translation("pretix date formatter"),
 *   field_types = {
 *     "pretix_date"
 *   }
 * )
 */
class PretixDateFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    // Sort descending by time_from.
    $indexed = [];
    foreach ($items as $item) {
      $indexed[$item->time_from] = $item;
    }
    krsort($indexed);
    $items = array_values($indexed);

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#theme' => 'itk_pretix_date_entry',
        '#variables' => [
          'location' => $item->location,
          'address' => $item->address,
          'time_from' => $item->time_from,
          'time_to' => $item->time_to,
          'spots' => $item->spots,
          'data' => array_merge(
            $item->data ?? [],
            \Drupal::service('itk_pretix.event_helper')->loadPretixSubEventInfo($item) ?? []
          ),
        ],
      ];
    }

    return $elements;
  }

}
