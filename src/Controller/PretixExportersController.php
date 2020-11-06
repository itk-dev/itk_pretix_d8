<?php

namespace Drupal\itk_pretix\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\itk_pretix\Exception\ExporterException;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Pretix controller.
 */
class PretixExportersController extends ControllerBase {
  /**
   * The event helper.
   *
   * @var \Drupal\itk_pretix\Pretix\EventHelper
   */
  private $eventHelper;

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  private $session;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->eventHelper = $container->get('itk_pretix.event_helper');
    $instance->session = $container->get('session');

    return $instance;
  }

  /**
   * Show orders for a node with a pretix dates field.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   */
  public function index(Request $request, NodeInterface $node) {
    $user = User::load(\Drupal::currentUser()->id());
    if (!$node->access('update', $user)) {
      throw new AccessDeniedHttpException();
    }

    $exporters = $this->eventHelper->getExporters($node);

    return [
      '#theme' => 'itk_pretix_event_exporters',
      '#node' => $node,
      '#exporters' => $exporters,
    ];
  }

  /**
   * Run exporter.
   */
  public function runExporter(Request $request, NodeInterface $node, string $identifier) {
    $parameters = $request->request->all();
    $run = $this->eventHelper->runExporter($node, $identifier, $parameters);

    $key = sha1(json_encode($run));
    $this->session->set($key, $run);

    return $this->redirect('itk_pretix.pretix_exporter_show', [
      'node' => $node->id(),
      'identifier' => $identifier,
      'key' => $key,
    ]);
  }

  /**
   * Show export result.
   */
  public function showExport(Request $request, NodeInterface $node, string $identifier, string $key) {
    $run = $this->session->get($key);
    if (NULL === $run) {
      throw new ExporterException(sprintf('Invalid export: %s', $key));
    }

    $response = $this->eventHelper->getExport($node, $run);

    if (Response::HTTP_OK === $response->getStatusCode()) {
      $this->session->remove($key);

      return $response;
    }

    sleep(10);
    return $this->redirect('itk_pretix.pretix_exporter_show', [
      'node' => $node->id(),
      'identifier' => $identifier,
      'key' => $key,
    ]);
  }

}
