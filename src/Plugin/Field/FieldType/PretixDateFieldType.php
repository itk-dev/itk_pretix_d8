<?php

namespace Drupal\itk_pretix\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\datetime\DateTimeComputed;
use Nicoeg\Dawa\Dawa;

/**
 * Plugin implementation of the 'pretix_date_field_type' field type.
 *
 * @FieldType(
 *   id = "pretix_date_field_type",
 *   label = @Translation("Pretix date field type"),
 *   description = @Translation("Provides values required by pretix API"),
 *   default_widget = "pretix_date_widget_type",
 *   default_formatter = "pretix_date_formatter_type"
 * )
 *
 * @property string uuid
 * @property string location
 * @property string address
 * @property string|DateTimeComputed time_from
 * @property string|DateTimeComputed time_to
 * @property int spots
 * @property array data
 */
class PretixDateFieldType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['uuid'] = DataDefinition::create('string')
      ->setLabel(t('UUID'))
      ->setRequired(TRUE);
    $properties['location'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Location'))
      ->setRequired(TRUE);
    $properties['address'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Address'))
      ->setRequired(TRUE);
    $properties['time_from'] = DataDefinition::create('any')
      ->setLabel(new TranslatableMarkup('Start time'))
      ->setClass(DateTimeComputed::class)
      ->setSetting('date source', 'value')
      ->setRequired(TRUE);
    $properties['time_to'] = DataDefinition::create('any')
      ->setLabel(new TranslatableMarkup('End time'))
      ->setClass(DateTimeComputed::class)
      ->setSetting('date source', 'value')
      ->setRequired(TRUE);
    $properties['spots'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Spots'))
      ->setRequired(TRUE);
    $properties['data'] = DataDefinition::create('any')
      ->setLabel(new TranslatableMarkup('Data'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'uuid' => [
          'type' => 'varchar',
          'length' => 128,
        ],
        'location' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'address' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'time_from' => [
          'type' => 'varchar',
          'length' => 128,
        ],
        'time_to' => [
          'type' => 'varchar',
          'length' => 128,
        ],
        'spots' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ],
        'data' => [
          'type' => 'blob',
          'size' => 'normal',
          'serialize' => TRUE,
        ],
      ],
      'indexes' => [
        'value' => ['uuid'],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $location = $this->get('location')->getValue();
    $timeFrom = $this->get('time_from')->getValue();
    $timeTo = $this->get('time_to')->getValue();
    return empty($location) || empty($timeFrom) || empty($timeTo);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    if (empty($this->get('uuid')->getValue())) {
      $this->get('uuid')->setValue(\Drupal::service('uuid')->generate());
    }

    $address = $this->get('address')->getValue();
    if (!empty($address)) {
      try {
        $results = (new Dawa())->accessAddressSearch($address);
        if (isset($results[0]->adgangspunkt->koordinater)) {
          $this->addData(['coordinates' => $results[0]->adgangspunkt->koordinater]);
        }
      }
      catch (\Exception $exception) {
      }
    }
  }

  /**
   * Add data to this date.
   *
   * @param array $values
   *   The values to add.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  public function addData(array $values) {
    $field = $this->get('data');
    $value = $field->getValue() ?? [];
    $value = array_merge($value, $values);
    $field->setValue($value);
  }

}
