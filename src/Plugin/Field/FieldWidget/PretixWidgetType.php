<?php

namespace Drupal\itk_pretix\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Plugin implementation of the 'pretix_widget_type' widget.
 *
 * @FieldWidget(
 *   id = "pretix_widget_type",
 *   module = "itk_pretix",
 *   label = @Translation("Pretix widget type"),
 *   field_types = {
 *     "pretix_field_type"
 *   }
 * )
 */
class PretixWidgetType extends WidgetBase {
  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['location'] = array(
      '#type' => 'textfield',
      '#title' => t('Location'),
      '#default_value' => isset($items[$delta]->location) ? $items[$delta]->location : '',
      '#size' => 45,
    );
    $element['address'] = array(
      '#type' => 'search',
      '#title' => t('Address'),
      '#default_value' => isset($items[$delta]->address) ? $items[$delta]->address : '',
      '#size' => 45,
      '#attached' => array(
        'library' => array(
          'itk_pretix/itk-pretix'
        )
      ),
      '#attributes' => array('class' => array('js-dawa-element'))
    );

    if ($items[$delta]->time_from) {
      $datePartsFrom = explode(' ', $items[$delta]->time_from);
    }
    $element['time_from'] = array(
      '#type' => 'datetime',
      '#title' => t('Start time'),
      '#default_value' => isset($datePartsFrom) ? DrupalDateTime::createFromFormat('Y-m-d H:i:s', $datePartsFrom[0] . ' ' . $datePartsFrom[1], $datePartsFrom[2]) : DrupalDateTime::createFromTimestamp($this->roundedTime(time())),
      '#date_date_element' => 'date',
      '#date_time_element' => 'time',
      '#date_date_format' => 'd/m/Y',
      '#date_time_format' => 'H:i',
      '#size' => 15,
    );

    if ($items[$delta]->time_to) {
      $datePartsTo = explode(' ', $items[$delta]->time_to);
    }
    $element['time_to'] = array(
      '#type' => 'datetime',
      '#title' => t('End time'),
      '#default_value' => isset($datePartsTo) ? DrupalDateTime::createFromFormat('Y-m-d H:i:s', $datePartsTo[0] . ' ' . $datePartsTo[1], $datePartsTo[2]) : DrupalDateTime::createFromTimestamp($this->roundedTime(time())),
      '#date_date_element' => 'date',
      '#date_time_element' => 'time',
      '#date_date_format' => 'd/m/Y',
      '#date_time_format' => 'H:i',
      '#size' => 15,
    );
    $element['spots'] = array(
      '#type' => 'number',
      '#title' => t('Number of spots'),
      '#default_value' => isset($items[$delta]->spots) ? $items[$delta]->spots : '',
      '#size' => 3,
    );

    // If cardinality is 1, ensure a label is output for the field by wrapping
    // it in a details element.
    if ($this->fieldDefinition->getFieldStorageDefinition()->getCardinality() == 1) {
      $element += array(
        '#type' => 'fieldset',
        '#attributes' => array('class' => array('container-inline')),
      );
    }

    return $element;
  }

  /**
   * Round seconds to nearest hour.
   *
   * @param $seconds
   *   A timestamp.
   * @return float|int
   *   A rounded timestamp.
   */
  private function roundedTime($seconds) {
    return round($seconds / 3600)*3600;
  }
}
