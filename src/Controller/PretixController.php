<?php

namespace Drupal\itk_pretix\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use ItkDev\Pretix\Api\Entity\SubEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Encoder\CsvEncoder;

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

  /**
   * Show orders for a node with a pretix dates field.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   */
  public function orders(Request $request, NodeInterface $node) {
    throw new BadRequestHttpException();
  }

  /**
   * Show orders for a single date on a node with a pretix dates field.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   * @param string $uuid
   *   The item uuid.
   *
   * @return array|\Symfony\Component\HttpFoundation\Response
   *   The response.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function ordersDate(Request $request, NodeInterface $node, string $uuid) {
    $item = $this->nodeHelper->getDateItem($node, $uuid);
    if (NULL === $item) {
      throw new BadRequestHttpException();
    }
    $eventInfo = $this->orderHelper->loadPretixEventInfo($node);
    $subEventInfo = $this->orderHelper->loadPretixSubEventInfo($item);

    $client = $this->eventHelper->getPretixClient($node);
    $event = $client->getEvent($eventInfo['pretix_event_slug']);
    $subEventId = $subEventInfo['pretix_subevent_id'];
    $subEvent = $client->getSubEvents($event)->filter(
      static function (SubEvent $subEvent) use ($subEventId) {
        return $subEvent->getId() === $subEventId;
      })->first();

    if (!$subEvent) {
      throw new BadRequestHttpException();
    }

    $orders = $client->getOrders($event, ['subevent' => $subEvent]);

    $format = $request->getRequestFormat();
    $filename = sprintf('event.%s', $format);
    switch ($format) {
      case 'csv':
        $encoder = new CsvEncoder();
        return new Response(
          $encoder->encode($orderPositions->toArray(), 'csv'),
          200,
          [
            'content-type' => 'text/csv',
            'content-disposition' => sprintf('attachment; filename="%s"', $filename),
          ]
        );

      case 'json':
        return new JsonResponse($orderPositions->toArray());
    }

    return [
      '#theme' => 'itk_pretix_orders_date',
      '#event' => $event,
      '#sub_event' => $subEvent,
      '#orders' => $orders,
      '#node' => $node,
      '#exports' => [
        'csv' => [
          'url' => Url::fromRoute(
            $request->attributes->get('_route'),
            [
              'node' => $node->id(),
              'uuid' => $uuid,
              '_format' => 'csv',
            ])->toString(),
        ],
      ],
    ];
  }

}