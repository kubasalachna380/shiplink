<?php

namespace App\Helper;

use Symfony\Component\HttpFoundation\JsonResponse;

class OrderHelper
{
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';
    const ORDER_STATUS_PENDING = 1;
    const ORDER_STATUS_PROCESSING = 2;
    const ORDER_STATUS_COMPLETED = 3;
    const ORDER_STATUS_CANCELED = 4;

    /**
     * Call external API
     *
     * @param $url
     * @param $post
     * @param $postData
     * @return bool|string
     */
    public function getApiData($url, $post = false, $postData = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        $options = [
            CURLOPT_POST => $post,
            //CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'accept: application/json',
            ],
        ];
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * Checks numeric type of varilble
     *
     * @param $orderNumber
     * @return bool
     */
    public function validateNumericValue($orderNumber)
    {
        if (!is_numeric($orderNumber)) {
            return false;
        }
        return true;
    }
}