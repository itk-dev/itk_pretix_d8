<?php

namespace Drupal\itk_pretix\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Plugin implementation of the 'pretix_formatter_type' formatter.
 *
 * @FieldFormatter(
 *   id = "pretix_formatter_type",
 *   label = @Translation("Pretix formatter type"),
 *   field_types = {
 *     "pretix_field_type"
 *   }
 * )
 */
class PretixFormatterType extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#theme' => 'itk_pretix_date_entry',
        '#variables' => array (
          'location' => $item->location,
          'address' => $item->address,
          'time_from' => $item->time_from,
          'time_to' => $item->time_to,
          'spots' => $item->spots
        ),
      ];
    }

    return $elements;
  }
}
