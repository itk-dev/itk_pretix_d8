<?php

namespace Drupal\itk_pretix\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\itk_pretix\Exception\ExporterException;
use Drupal\itk_pretix\Exporter\AbstractExporter;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Pretix controller.
 */
class PretixEventExportersController extends ControllerBase {
  /**
   * The event helper.
   *
   * @var \Drupal\itk_pretix\Pretix\EventHelper
   */
  private $eventHelper;

  /**
   * The exporter manager.
   *
   * @var \Drupal\itk_pretix\Exporter\Manager
   */
  private $exporterManager;

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
    $instance->exporterManager = $container->get('itk_pretix.exporter_manager');
    $instance->setMessenger($container->get('messenger'));
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

    if ('POST' === $request->getMethod()) {
      header('content-type: text/plain'); echo var_export($request->request->all(), TRUE); die(__FILE__ . ':' . __LINE__ . ':' . __METHOD__);
    }

    $exporters = $this->exporterManager->getEventExporters();
    $exporterForms = array_map(function (AbstractExporter $exporter) use ($node) {
      return [
        'name' => $exporter->getName(),
        'form' => $this->buildForm($node, $exporter),
      ];
    }, $exporters);

    return [
      '#theme' => 'itk_pretix_event_exporters',
      '#node' => $node,
    // '#exporters' => $this->eventHelper->getExporters($node),
      '#exporter_forms' => $exporterForms,
    ];
  }

  /**
   * Build exporter form.
   */
  private function buildForm(NodeInterface $node, AbstractExporter $exporter) {
    $exporter
      ->setPretixClient($this->eventHelper->getPretixClient($node))
      ->setEventInfo($this->eventHelper->loadPretixEventInfo($node));

    $form = $this->formBuilder()->getForm($exporter);
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run exporter @exporter', ['@exporter' => $exporter->getName()]),
    ];

    $form['#action'] = Url::fromRoute('itk_pretix.pretix_exporter_event_run', [
      'node' => $node->id(),
      'identifier' => $exporter->getId(),
    ])->toString();

    return $form;
  }

  /**
   * Show exporter.
   */
  public function showExporter(Request $request, NodeInterface $node, string $identifier) {
    $parameters = $request->request->all();
    $run = $this->eventHelper->runExporter($node, $identifier, $parameters);

    $key = sha1(json_encode($run));
    $this->session->set($key, $run);

    return $this->redirect('itk_pretix.pretix_exporter_event_run_show', [
      'node' => $node->id(),
      'identifier' => $identifier,
      'key' => $key,
    ]);
  }

  /**
   * Run exporter.
   */
  public function runExporter(Request $request, NodeInterface $node, string $identifier) {
    $exporter = $this->exporterManager->getEventExporter($identifier);
    $parameters = $exporter->processInputParameters($request->request->all());
    $run = $this->eventHelper->runExporter($node, $identifier, $parameters);

    $key = sha1(json_encode($run));
    $this->session->set($key, $run);

    return $this->redirect('itk_pretix.pretix_exporter_event_run_show', [
      'node' => $node->id(),
      'identifier' => $identifier,
      'key' => $key,
    ]);
  }

  /**
   * Show export result.
   */
  public function showRun(Request $request, NodeInterface $node, string $identifier, string $key) {
    $run = $this->session->get($key);
    if (NULL === $run) {
      throw new ExporterException(sprintf('Invalid export: %s', $key));
    }

    $response = $this->eventHelper->getExport($node, $run);

    // @see https://docs.pretix.eu/en/latest/api/resources/exporters.html#downloading-the-result
    switch ($response->getStatusCode()) {
      case Response::HTTP_OK:
        $this->session->remove($key);

        return $response;

      case Response::HTTP_CONFLICT:
        sleep(10);
        return $this->redirect('itk_pretix.pretix_exporter_event_run_show', [
          'node' => $node->id(),
          'identifier' => $identifier,
          'key' => $key,
        ]);

      case Response::HTTP_GONE:
      case Response::HTTP_NOT_FOUND:
      default:
        $this->session->remove($key);
        $this->messenger()->addError(sprintf('%d: %s', $response->getStatusCode(), json_encode((string) $response->getBody())));
        return $this->redirect('itk_pretix.pretix_exporter_event', [
          'node' => $node->id(),
        ]);
    }

  }

}
