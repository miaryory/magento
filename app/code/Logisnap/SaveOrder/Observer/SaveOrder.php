<?php
/**
 * Copyright © LogiSnap, Inc. All rights reserved.
 * php version 7.3.29

 * @category Observer
 * @package  Logisnap
 * @author   Logisnap <user@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-only
 * @link     https://logisnap.com/
 */

namespace Logisnap\SaveOrder\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Logisnap\SaveOrder\Model\Token;
use Magento\Framework\HTTP\Client\Curl;

/**
 * SaveOrder Observer

 * @category Observer
 * @package  Logisnap
 * @author   Logisnap <user@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-only
 * @link     https://logisnap.com/
 */
class SaveOrder implements ObserverInterface
{
    protected $token;
    protected $curl;

    /**
     * Contructor
     * 
     * @param Logisnap\SaveOrder\Model\Token     $token /
     * @param Magento\Framework\HTTP\Client\Curl $curl  /
     */
    public function __construct(
        Token $token,
        Curl $curl
    ) {
        $this->token = $token;
        $this->curl = $curl;
    }

    /**
     * Executed when a new order is placed
     * 
     * @param \Magento\Framework\Event\Observer $observer /
     * 
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        //get order info
        $order = $observer->getEvent()->getOrder();
        $orderNumber = $order->getIncrementId();

        //get customer info
        $customerId = $order->getCustomerId();
        $customerFirstname = $order->getCustomerFirstname();
        $customerLastname = $order->getCustomerLastname();
        $customerEmail = $order->getCustomerEmail();

        //get shipping info
        $shipping = $order->getShippingAddress();
        $shippingMethod = $order->getShippingMethod(); //LogisnapCarriers_shipmentUID
        $shippingCity = $shipping->getCity();
        $shippingStreet = $shipping->getStreet();
        $shippingPostcode = $shipping->getPostcode(); 
        $shippingTelephone = $shipping->getTelephone();

        //send order only if the user chooses a Logisnap carrier
        if (strpos($shippingMethod, 'LogisnapCarriers_') !== false) {

            //get Logisnap custom carrier UID
            $shippingUID = str_replace('LogisnapCarriers_', '', $shippingMethod);

            //get token and check if valid
            $accesstoken = $this->token->getAccessToken();
    
            if ($accesstoken != null) {
    
                //get shipment info from the API
                $shipmentTypes = $this->getShipmentTypes($accesstoken, $shippingUID);
        
                //get sender info (the logisnap user)
                $sender = $this->getSenderDetails($accesstoken);

                $orderData = [
                    'ClientShipmentTypeUID' => $shipmentTypes->UID,
                    'PickDate' => 20310801,
                    'DeliveryDate' => 20310801,
                    'Number' => $orderNumber, //orderNumber
                    'Ref1' => '',
                    'TypeID' => $shipmentTypes->TypeID,
                    'StatusID' => $shipmentTypes->StatusID,
                    'ContactInformation' => [
                        'Receiver' => [
                            "StatusID" => 10,
                            "Name" => $customerFirstname." ".$customerLastname,
                            "ContactType" => 100, //should stay 100
                            "TypeID" =>10,            
                            "Adr1" => $shippingStreet[0],
                            "PostalCode" => $shippingPostcode,
                            "City" => $shippingCity,
                            "Phone" => $shippingTelephone,
                            "Email" => $customerEmail
                        ],
                        'Sender' => [
                            "StatusID" => $sender->StatusID,
                            "Name" => $sender->Name,
                            "ContactType" => 10,
                            "TypeID" => $sender->TypeID,            
                            "Adr1" => $sender->Adr1,
                            "PostalCode" => $sender->PostalCode,
                            "City" => $sender->City,
                            "Phone" => $sender->Phone,
                            "Email" => $sender->PrimaryEmail
                        ]
                    ]

                ];

                //get info for each item in the order
                $orderItems = $order->getAllVisibleItems();
                $orderlineData = [];
                
                foreach ($orderItems as $item) {
                    $orderlineData[] = [
                        "ProdNumber" => $item->getProductId(),
                        "ProdName" => $item->getName() .' '.$item->getSku(),
                        "ProdAmount" => intval($item->getQtyOrdered())
                    ];
                }
    
                $logisnapOrder = json_encode($orderData);
                
                //send to postOrder()
                $this->postOrder($accesstoken, $logisnapOrder, $orderlineData);
            }

        }

    }

    /**
     * Get all shipment types for this Logisnap account
     * 
     * @param string $token       /
     * @param string $shippingUID /
     * 
     * @return string
     */
    protected function getShipmentTypes($token, $shippingUID)
    {
        $headers = [
            "Content-Type" => "application/json", 
            "Authorization" => "basic ". $token
        ];
        $this->curl->setHeaders($headers);

        $url = 'https://logiapiv1.azurewebsites.net/logistics/order/shipmenttypes';
        
        $this->curl->get($url);
        $curlresult = json_decode($this->curl->getBody());

        if ($curlresult != null) {
            
            //return shipment info selected
            foreach ($curlresult as $carrier) {
                if ($carrier->UID === $shippingUID) {
                    return $carrier;
                }
            }

        }

    }

    /**
     * Get sender details
     * 
     * @param string $token /
     * 
     * @return string
     */
    protected function getSenderDetails($token)
    {
        $headers = [
            "Content-Type" => "application/json", 
            "Authorization" => "basic ". $token
        ];
        $this->curl->setHeaders($headers);

        $url = 'https://logiapiv1.azurewebsites.net/client/get/current';
        
        $this->curl->get($url);
        $curlresult = json_decode($this->curl->getBody());
    
        return $curlresult;
    }

    /**
     * Save order in Logisnap system
     * 
     * @param string $token     /
     * @param array  $order     /
     * @param array  $orderLine /
     * 
     * @return void
     */
    protected function postOrder($token, $order, $orderLine)
    {
        $headers = [
            "Content-Type" => "application/json", 
            "Authorization" => "basic ". $token
        ];
        $this->curl->setHeaders($headers);

        $url = 'https://logiapiv1.azurewebsites.net/logistics/order/create/bulk';

        $this->curl->post($url, $order);
        
        if ($this->curl->getStatus() == 200) {
            //if the order was created > create the orderline
            $curlresult = json_decode($this->curl->getBody());
            $orderlineData = [];

            foreach ($orderLine as $item) {
                $orderlineData = [
                    "OrderUID" => $curlresult->UID,
                    "Number" => $curlresult->Number,
                    "ProdNumber" => $item['ProdNumber'],
                    "ProdName" => $item['ProdName'],
                    "ProdLocation" => "",
                    "ProdAmount" => $item['ProdAmount'],
                    "ColliPrProd" => 1,
                    "TypeID" => 10,
                    "StatusID" =>10
                ];
                
                //create and orderline for each item in the order
                $this->postOrderline($token, json_encode($orderlineData));
            }

        }
    }

    /**
     * Save order items in Logisnap system
     * 
     * @param string $token     /
     * @param array  $orderLine /
     * 
     * @return void
     */
    protected function postOrderline($token, $orderLine)
    {
        $headers = [
            "Content-Type" => "application/json", 
            "Authorization" => "basic ". $token
        ];
        $this->curl->setHeaders($headers);

        $url = 'https://logiapiv1.azurewebsites.net/logistics/order/line/create';

        $this->curl->post($url, $orderLine);
    }


}