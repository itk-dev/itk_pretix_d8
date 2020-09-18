<?php

namespace Drupal\itk_pretix\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;
use ItkDev\Pretix\Api\Collections\EntityCollectionInterface;
use ItkDev\Pretix\Api\Entity\Order;
use ItkDev\Pretix\Api\Entity\Order\Position;
use ItkDev\Pretix\Api\Entity\SubEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
    $user = User::load(\Drupal::currentUser()->id());
    if (!$node->access('update', $user)) {
      throw new AccessDeniedHttpException();
    }

    $item = $this->nodeHelper->getDateItem($node, $uuid);
    if (NULL === $item) {
      throw new BadRequestHttpException();
    }
    $eventInfo = $this->orderHelper->loadPretixEventInfo($node);
    if (NULL === $eventInfo) {
      throw new BadRequestHttpException('Cannot get event info');
    }
    $subEventInfo = $this->orderHelper->loadPretixSubEventInfo($item);
    if (NULL === $subEventInfo) {
      throw new BadRequestHttpException('Cannot get sub-event info');
    }

    $client = $this->eventHelper->getPretixClient($node);
    $event = $client->getEvent($eventInfo['pretix_event_slug']);
    $subEventId = $subEventInfo['pretix_subevent_id'];
    $subEvent = $client->getSubEvents($event)->filter(
      static function (SubEvent $subEvent) use ($subEventId) {
        return $subEvent->getId() === $subEventId;
      })->first();

    if (!$subEvent) {
      throw new BadRequestHttpException('Cannot get sub-event');
    }

    $orders = $client->getOrders($event, ['subevent' => $subEvent]);

    $format = $request->getRequestFormat();
    $filename = sprintf('event.%s', $format);
    switch ($format) {
      case 'csv':
        $encoder = new CsvEncoder();
        return new Response(
          $encoder->encode($this->getExportData($orders), 'csv'),
          200,
          [
            'content-type' => 'text/csv',
            'content-disposition' => sprintf('attachment; filename="%s"', $filename),
          ]
        );

      case 'json':
        return new JsonResponse($this->getExportData($orders));
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

  /**
   * Get data for export.
   */
  private function getExportData(EntityCollectionInterface $orders): array {
    // Create a list with an entry for each order position.
    return array_merge(...$orders->map(static function (Order $order) {
      return $order->getPositions()->map(static function (Position $position) use ($order) {
        return [
          'Order code' => $order->getCode(),
          'Name' => $position->getAttendeeName(),
          'Email' => $position->getAttendeeEmail() ?? $order->getEmail(),
          'Price' => $position->getPrice(),
          'Url' => $order->getUrl(),
        ];
      });
    })->toArray());
  }

}
