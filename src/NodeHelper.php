<?php

namespace Drupal\itk_pretix;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\Entity\Node;

class NodeHelper {
  public function sync(Node $node, string $action) {
    $dates = $this->getPretixDates($node);
    if (null !== $dates) {
      $settings = $this->getPretixSettings($node);
    }

//    header('content-type: text/plain'); echo var_export($node->get('field_pretix_dates')->getValue(), true); die(__FILE__.':'.__LINE__.':'.__METHOD__);

    $fields = $node->getFields();
    foreach ($fields as $field) {
      $definition = $field->getFieldDefinition();
      if ('pretix_date_field_type' === $definition->getType()) {
        $dates = $field->getValue();

        $cardinality = $definition->getFieldStorageDefinition()->getCardinality();
        foreach ($dates as $date) {
          //        header('content-type: text/plain'); echo var_export(is_string($value['time_from']), true); die(__FILE__.':'.__LINE__.':'.__METHOD__);
          if (isset($date['time_from']) && is_string($date['time_from'])) {
            $date['time_from'] = new DrupalDateTime($date['time_from']);
          }
        }

        header('content-type: text/plain'); echo var_export($dates, true); die(__FILE__.':'.__LINE__.':'.__METHOD__);

           }
    }

    // @TODO Check if node has a pretix_date field
    // @TODO Check if the pretix_date field is a single date or allows multiple dates
    // @TODO Get pretix settings from node (pretix_settings field)

    // $wrapper = entity_metadata_wrapper('node', $node);
    // if (!isset($wrapper->field_pretix_enable) || TRUE !== $wrapper->field_pretix_enable->value()) {
    //   return;
    // }
    // $helper = EventHelper::create();
    // if ($helper->isPretixEventNode($node)) {
    //   $result = $helper->syncronizePretixEvent($node);
    //   if ($helper->isError($result)) {
    //     drupal_set_message(t('There was a problem updating the event in pretix. Please verify in pretix that all settings for the event are correct.'), 'error');
    //   }
    //   else {
    //     $pretix_event_url = $helper->getPretixEventUrl($node);
    //     drupal_set_message(t('Successfully updated <a href="@pretix_event_url">the event in pretix</a>.', [
    //       '@pretix_event_url' => $pretix_event_url,
    //     ]), 'status', FALSE);

    //     $live = $node->status;
    //     $result = $helper->setEventLive($node, $live);
    //     if ($helper->isError($result)) {
    //       $data = $helper->getErrorData($result);
    //       $errors = isset($data->live) ? implode('; ', $data->live) : NULL;
    //       $message = $live
    //         ? t('Error setting <a href="@pretix_event_url">the pretix event</a> live: @errors', [
    //           '@pretix_event_url' => $pretix_event_url,
    //           '@errors' => $errors,
    //         ])
    //         : t('Error setting <a href="@pretix_event_url">the pretix event</a> not live: @errors', [
    //           '@pretix_event_url' => $pretix_event_url,
    //           '@errors' => $errors,
    //         ]);
    //       drupal_set_message($message, 'error', FALSE);
    //     }
    //     else {
    //       $message = $live
    //         ? t('Successfully set <a href="@pretix_event_url">the pretix event</a> live.', [
    //           '@pretix_event_url' => $pretix_event_url,
    //         ])
    //         : t('Successfully set <a href="@pretix_event_url">the pretix event</a> not live.', [
    //           '@pretix_event_url' => $pretix_event_url,
    //         ]);
    //       drupal_set_message($message, 'status', FALSE);
    //     }
    //   }
    // }
  }
}
