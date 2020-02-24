<?php

namespace Drupal\itk_pretix;

use Drupal\Core\TypedData\TypedData;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * A computed property for pretix Uuid.
 *
 * Required settings (below the definition's 'settings' key) are:
 *  - uuid source: The uuid property containing the to be computed uuid.
 */
class UuidComputed extends TypedData {

  /**
   * Cached computed value.
   *
   * @var string|null
   */
  protected $uuid = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    if (!$definition->getSetting('uuid source')) {
      throw new \InvalidArgumentException("The definition's 'uuid source' key has to specify the name of the uuid property to be computed.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValue($langcode = NULL) {
    if ($this->uuid !== NULL) {
      return $this->uuid;
    }
    $uuid_service = \Drupal::service('uuid');
    $this->uuid = $uuid_service->generate();

    return $this->uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $this->uuid = $value;
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

}
