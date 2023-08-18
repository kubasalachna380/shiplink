<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderDetail;
use App\Helper\OrderHelper;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrdersController extends AbstractController
{
    /**
     * Service verification
     *
     * @Route("/work-check", name="check()")
     * @return JsonResponse
     */
    public function check(): JsonResponse
    {
        return new JsonResponse(
            'I\'m alive and well :)',
            JsonResponse::HTTP_OK
        );
    }

    /**
     * Returns list of product based on Fake Store API
     *
     * @Route ("/products", name="products()")
     * @return JsonResponse
     */
    public function products(): JsonResponse
    {
        $orderHelper = new OrderHelper();
        $response = $orderHelper->getApiData('https://fakestoreapi.com/products');

        return new JsonResponse(
            $response, JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * Creates a new order
     *
     * @param ManagerRegistry $doctrine
     * @param Request $request
     * @Route ("order/new", name="addOrder()", methods={"POST"})
     * @return JsonResponse
     */
    public function addOrder(ManagerRegistry $doctrine, Request $request): JsonResponse
    {
        $request = json_decode($request->getContent(), true);

        $em = $doctrine->getManager();
        $order = new Order();

        try {
            $order->setFirstName($request['firstName']);
            $order->setLastName($request['lastName']);
            $order->setStatus(OrderHelper::ORDER_STATUS_PENDING);
            $order->setCreatedAt(new \DateTime());
            $order->setEmailAddress($request['emailAddress']);
            $em->persist($order);
            $em->flush();

            foreach ($request['products'] as $product) {
                $orderDetail = new OrderDetail();
                $orderDetail->setOrderId($order->getId());
                $orderDetail->setProductId((int) $product['id']);
                $orderDetail->setQuantity((int) $product['quantity']);
                $em->persist($orderDetail);
                $em->flush();
            }

            $response = [
                'status' => OrderHelper::STATUS_SUCCESS,
                'message' => 'Order has been placed',
                'orderId' => $order->getId(),
            ];
            $responseCode = JsonResponse::HTTP_OK;
        } catch (\Exception $e) {
            $response = [
                'status' => OrderHelper::STATUS_ERROR,
                'message' => 'There was an error during placing order',
                'errorStatus' => $e->getMessage(),
            ];
            $responseCode = JsonResponse::HTTP_FORBIDDEN;
        }

        return new JsonResponse(
            $response,
            $responseCode
        );
    }

    /**
     * Updates the order status
     *
     * @param ManagerRegistry $doctrine
     * @param Request $request
     * @Route ("orders/status-update", name="changeOrderStatus()", methods={"PUT"})
     * @return JsonResponse
     */
    public function changeOrderStatus(ManagerRegistry $doctrine, Request $request): JsonResponse
    {
        $request = json_decode($request->getContent(), true);
        $em = $doctrine->getManager();

        $response = [];

        foreach ($request['orders'] as $order) {
            //Verification on status value
            if (!in_array($order['status'], [
                OrderHelper::ORDER_STATUS_PENDING,
                OrderHelper::ORDER_STATUS_PROCESSING,
                OrderHelper::ORDER_STATUS_COMPLETED,
                OrderHelper::ORDER_STATUS_CANCELED
            ])) {
                $response[] = [
                    'status' => OrderHelper::STATUS_ERROR,
                    'id' => $order['id'],
                    'message' => 'Wrong status value',
                ];
            } else {
                $orderData = $em->getRepository(Order::class)->find($order['id']);
                if (!$orderData) {
                    $response[] = [
                        'status' => OrderHelper::STATUS_ERROR,
                        'id' => $order['id'],
                        'message' => 'Order doesn\'t exist',
                    ];
                } else {
                    $orderData->setStatus($order['status']);
                    $em->flush();

                    $response[] = [
                        'status' => OrderHelper::STATUS_SUCCESS,
                        'id' => $order['id'],
                        'message' => 'Order status changed',
                    ];
                }
            }
        }

        return new JsonResponse(
            $response,
            JsonResponse::HTTP_OK
        );
    }

    /**
     * Removes the order
     *
     * @param ManagerRegistry $doctrine
     * @param $orderNumber
     * @Route ("/order/delete/{orderNumber}", name="deleteOrder", methods={"DELETE"})
     * @return JsonResponse
     */
    public function deleteOrder(ManagerRegistry $doctrine, $orderNumber):JsonResponse
    {
        $orderHelper = new OrderHelper();
        if (!$orderHelper->validateNumericValue($orderNumber)) {
            return new JsonResponse([
                'status' => OrderHelper::STATUS_ERROR,
                'message' => 'Wrong order number',
            ],
                JsonResponse::HTTP_FORBIDDEN
            );
        }

        $em = $doctrine->getManager();

        //Find order
        $orderData = $em->getRepository(Order::class)->find($orderNumber);

        if(!$orderData) {
            return new JsonResponse([
                'status' => OrderHelper::STATUS_ERROR,
                'message' => 'Order doesn\'t exist',
            ],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        //Find order details
        $orderDetails = $em->getRepository(OrderDetail::class)->findBy(
            ['order_id' => $orderNumber]
        );

        //Remove order details
        foreach ($orderDetails as $detail) {
           $em->remove($detail);
        }
        $em->flush();

        //Remove order
        $em->remove($orderData);
        $em->flush();

        return new JsonResponse([
                'status' => OrderHelper::STATUS_SUCCESS,
                'message' => 'Order deleted successfully',
            ],
            JsonResponse::HTTP_OK
        );
    }

    /**
     * Creates a new order based on existing order
     *
     * @param ManagerRegistry $doctrine
     * @param $orderNumber
     * @Route ("order/recreate/{orderNumber}", name="recreateOrder()", methods={"POST"})
     * @return JsonResponse
     */
    public function recreateOrder(ManagerRegistry $doctrine, $orderNumber): JsonResponse
    {
        $orderHelper = new OrderHelper();
        if (!$orderHelper->validateNumericValue($orderNumber)) {
            return new JsonResponse([
                'status' => OrderHelper::STATUS_ERROR,
                'message' => 'Wrong order number',
            ],
                JsonResponse::HTTP_FORBIDDEN
            );
        }

        $em = $doctrine->getManager();
        $orderData = $em->getRepository(Order::class)->find($orderNumber);
        if(!$orderData) {
            return new JsonResponse([
                'status' => OrderHelper::STATUS_ERROR,
                'message' => 'Order doesn\'t exists',
            ],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        //Create new order
        $order = new Order();
        $order->setFirstName($orderData->getFirstName());
        $order->setLastName($orderData->getLastName());
        $order->setEmailAddress($orderData->getEmailAddress());
        $order->setStatus(OrderHelper::ORDER_STATUS_PENDING);
        $order->setCreatedAt(new \DateTime());
        $em->persist($order);
        $em->flush();

        //Find order details
        $orderDetailsData = $em->getRepository(OrderDetail::class)->findBy(
            ['order_id' => $orderNumber]
        );

        //Recreate order details
        foreach ($orderDetailsData as $detail) {
            $orderDetail = new OrderDetail();
            $orderDetail->setOrderId($order->getId());
            $orderDetail->setProductId($detail->getProductId());
            $orderDetail->setQuantity($detail->getQuantity());
            $em->persist($orderDetail);
        }
        $em->flush();

        return new JsonResponse([
            'status' => OrderHelper::STATUS_SUCCESS,
            'message' => 'Order recreated successfully',
            'orderId' => $order->getId()
            ],
            JsonResponse::HTTP_OK
        );
    }

    /**
     * Returns the order data
     *
     * @param ManagerRegistry $doctrine
     * @param $orderNumber
     * @Route ("/order/{orderNumber}", name="orderData()")
     * @return void
     */
    public function orderData(ManagerRegistry $doctrine, $orderNumber): JsonResponse
    {
        $orderHelper = new OrderHelper();
        if (!$orderHelper->validateNumericValue($orderNumber)) {
            return new JsonResponse([
                'status' => OrderHelper::STATUS_ERROR,
                'message' => 'Wrong order number',
            ],
                JsonResponse::HTTP_FORBIDDEN
            );
        }

        $em = $doctrine->getManager();
        $orderData = $em->getRepository(Order::class)->find($orderNumber);

        if(!$orderData) {
            return new JsonResponse([
                'status' => OrderHelper::STATUS_ERROR,
                'message' => 'Order doesn\'t exists',
            ],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        $orderDetails = $em->getRepository(OrderDetail::class)->findBy(
            ['order_id' => $orderNumber]
        );

        $response = [];
        foreach ($orderDetails as $detail) {
            $response[] = $detail->getQuantity();
        }

        return new JsonResponse($response, JsonResponse::HTTP_OK);
    }
}