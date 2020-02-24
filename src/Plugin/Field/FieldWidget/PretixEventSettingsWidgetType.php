<?php

namespace Drupal\itk_pretix\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'pretix_event_settings_widget_type' widget.
 *
 * @FieldWidget(
 *   id = "pretix_event_settings_widget_type",
 *   module = "itk_pretix",
 *   label = @Translation("pretix event settings"),
 *   field_types = {
 *     "pretix_event_settings_field_type"
 *   }
 * )
 */
class PretixEventSettingsWidgetType extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $form_state
  ) {
    // @TODO Get template events from pretix.
    $templateEvents = [];
    $templateEventOptions = array_combine($templateEvents, $templateEvents);

    $element['pretix_event_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('pretix settings'),
      // Collapse when editing a node.
      '#open' => NULL === $items->getParent()->getEntity()->id(),

      'template_event' => [
        '#type' => 'select',
        '#options' => $templateEventOptions,
        '#title' => $this->t('Template event'),
        '#description' => $this->t('Select the template event to clone when creating the pretix event'),
        '#default_value' => $items[$delta]->template_event ?? NULL,
        '#empty_option' => t('Select template event'),
        '#required' => TRUE,
      ],

      'synchronize_event' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Synchronize event in pretix'),
        '#description' => $this->t('If set, the pretix event will be updated when changes are made to the dates on this node'),
        '#default_value' => $items[$delta]->synchronize_event ?? NULL,
      ],
    ];

    return $element;
  }

}
