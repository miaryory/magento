<?php
/**
 * Copyright © LogiSnap, Inc. All rights reserved.
 * php version 7.3.29

 * @category Model
 * @package  Logisnap
 * @author   Logisnap <user@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-only
 * @link     https://logisnap.com/
 */

namespace Logisnap\SaveOrder\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Framework\HTTP\Client\Curl;
use Logisnap\SaveOrder\Model\Token;


/**
 * LogisnapCarriers Model

 * @category Model
 * @package  Logisnap
 * @author   Logisnap <user@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-only
 * @link     https://logisnap.com/
 */
class LogisnapCarriers extends AbstractCarrier implements CarrierInterface
{
    protected $token;
    protected $curl;
    protected $scopeConfig;
    protected $_code = 'LogisnapCarriers';
    protected $_isFixed = true;
    private $rateResultFactory;
    private $rateMethodFactory;

    /**
     * Contructor
     * 
     * @param \Magento\Framework\App\Config\ScopeConfigInterface          $scopeConfig       /
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory  $rateErrorFactory  /
     * @param \Psr\Log\LoggerInterface                                    $logger            /
     * @param \Magento\Shipping\Model\Rate\ResultFactory                  $rateResultFactory /
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory / 
     * @param Logisnap\SaveOrder\Model\Token                              $token             /
     * @param Magento\Framework\HTTP\Client\Curl                          $curl              /
     * @param array                                                       $data              /
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        Token $token,
        Curl $curl,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->scopeConfig = $scopeConfig;
        $this->token = $token;
        $this->curl = $curl;
    }

    /**
     * LogisnapCarriers Rates Collector
     * 
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request /
     * 
     * @return \Magento\Framework\Controller\Result\JsonFactory
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $result = $this->rateResultFactory->create();

        $allowedMethods = $this->getAllowedMethods();

        if ($allowedMethods != null) {
            foreach ($allowedMethods as $key) {
    
                $method = $this->rateMethodFactory->create();
    
                $method->setCarrier($this->_code);
                $method->setCarrierTitle($key["title"]);
                $method->setMethod($key["code"]);
                $method->setMethodTitle($key["method"]);
    
                $shippingCost = (float)$key["cost"];
                $method->setPrice($shippingCost);
                $method->setCost($shippingCost);
    
                $result->append($method);
            }
    
            return $result;
        }
    }

    /**
     * LogisnapCarriers All allowed methods
     * 
     * @return array
     */
    public function getAllowedMethods()
    {
        $accesstoken = $this->token->getAccessToken();

        if ($accesstoken != null) {

            $headers = [
                "Content-Type" => "application/json", 
                "Authorization" => "basic ". $accesstoken
            ];
            $this->curl->setHeaders($headers);
    
            $url = 'https://logiapiv1.azurewebsites.net/logistics/order/shipmenttypes';
            
            $this->curl->get($url);
            $curlresult = json_decode($this->curl->getBody());
    
            $allMethods = [];
    
            if ($curlresult != null) {
                
                foreach ($curlresult as $method) {
        
                    //check in DB if the carrier is active by using the path sectionID/groupID/active
                    $activeStatus = $this->scopeConfig->getValue(
                        "carriers/".$method->UID."/active",
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    );
                    
                    $methodPrice = $this->scopeConfig->getValue(
                        "carriers/".$method->UID."/price",
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    );
        
                    //display method only if active = 1 in DB
                    if ($activeStatus == 1) {
                        $allMethods[] = [
                            'code' => $method->UID,
                            'title' => $method->ClientName,
                            'method' => $method->Name,
                            'cost' => $methodPrice
                        ];
                    }
                }
        
            }
            
            return $allMethods;
        }

    }
}
