<?php

namespace Drupal\itk_pretix\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted time to is not before time from.
 *
 * @Constraint(
 *   id = "PretixDateConstraint",
 *   label = @Translation("Pretix date constraint", context = "Validation"),
 *   type = "pretix_date_field_type"
 * )
 */
class PretixDateConstraint extends Constraint {
  public $timeToBeforeTimeFrom = 'End time must not be before start time';

}
