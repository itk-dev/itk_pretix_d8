<?php

namespace Drupal\itk_pretix\Plugin\ConfigFilter;

use Drupal\config_filter\Plugin\ConfigFilterBase;

/**
 * Provides a filter that ignores pretix settings.
 *
 * @ConfigFilter(
 *   id = "itk_pretix_settings",
 *   label = "pretix settings"
 * )
 */
class PretixSettingsFilter extends ConfigFilterBase {

  /**
   * {@inheritdoc}
   */
  public function filterRead($name, $data) {
    return $this->ignoreName($name) ? [] : parent::filterRead($name, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function filterReadMultiple(array $names, array $data) {
    $data = array_filter($data, function ($name) {
      return !$this->ignoreName($name);
    }, ARRAY_FILTER_USE_KEY);
    return parent::filterReadMultiple($names, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function filterWrite($name, array $data) {
    return $this->ignoreName($name) ? [] : parent::filterWrite($name, $data);
  }

  /**
   * Decide if a config name should be ignored.
   *
   * @param string $name
   *   The config name.
   *
   * @return bool
   *   To ignore or not to ignore.
   */
  private function ignoreName(string $name) {
    return 0 === strpos($name, 'itk_pretix.pretixconfig');
  }

}
