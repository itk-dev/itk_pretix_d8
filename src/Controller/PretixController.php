<?php

namespace Drupal\itk_pretix\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Pretix controller.
 */
class PretixController extends ControllerBase {
  /**
   * The event helper.
   *
   * @var \Drupal\itk_pretix\Pretix\EventHelper
   */
  private $eventHelper;

  /**
   * The node helper.
   *
   * @var \Drupal\itk_pretix\NodeHelper
   */
  private $nodeHelper;

  /**
   * The order helper.
   *
   * @var \Drupal\itk_pretix\Pretix\OrderHelper
   */
  private $orderHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->eventHelper = $container->get('itk_pretix.event_helper');
    $instance->nodeHelper = $container->get('itk_pretix.node_helper');
    $instance->orderHelper = $container->get('itk_pretix.order_helper');

    return $instance;
  }

  public function orders(Request $request, NodeInterface $node) {
    $client = $this->orderHelper->getPretixClient($node);
    $info = $this->orderHelper->loadPretixEventInfo($node);
    header('content-type: text/plain'); echo var_export($info, true); die(__FILE__.':'.__LINE__.':'.__METHOD__);
  }

  public function ordersDate(Request $request, NodeInterface $node, string $uuid) {
$item = $this->getDateItem($uuid);
    $info = $this->orderHelper->loadPretixSubEventInfo($item);

    header('content-type: text/plain'); echo var_export($uuid, true); die(__FILE__.':'.__LINE__.':'.__METHOD__);
  }
}
