<?php

namespace Drupal\itk_pretix\Exporter;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Exporter manager.
 */
class Manager {
  use StringTranslationTrait;

  /**
   * The event exporters.
   *
   * @var array|AbstractExporter[]
   */
  private $eventExporters;

  /**
   * The event exporter forms (indexed by form id).
   *
   * @var array|AbstractExporter[]
   */
  private $eventExporterForms;

  /**
   * Add an event exporter.
   */
  public function addEventExporter(AbstractExporter $exporter, $priority = 0) {
    $this->eventExporters[$exporter->getId()] = $exporter;
    $this->eventExporterForms[$exporter->getFormId()] = $exporter;

    return $this;
  }

  /**
   * Get event exporters.
   */
  public function getEventExporters() {
    return $this->eventExporters;
  }

  /**
   * Get event exporter.
   */
  public function getEventExporter(string $id) {
    return $this->eventExporters[$id] ?? NULL;
  }

  /**
   * Implementation of itk_pretix_file_download.
   *
   * @param string $uri
   *   The file uri.
   */
  public function fileDownload(string $uri) {
    if (0 === strpos($uri, 'private://itk_pretix/exporters/')) {
      $user = \Drupal::currentUser();
      if ($user->isAuthenticated()) {
        $filename = basename($uri);

        return [
          'content-disposition' => 'attachment; filename="' . $filename . '"',
        ];
      }

      return -1;
    }

    return NULL;
  }

}
