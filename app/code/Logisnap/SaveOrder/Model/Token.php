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

namespace Logisnap\SaveOrder\Model;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Variable\Model\Variable;

/**
 * Token Model

 * @category Model
 * @package  Logisnap
 * @author   Logisnap <user@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-only
 * @link     https://logisnap.com/
 */
class Token extends \Magento\Framework\Model\AbstractModel
{
    protected $curl;
    protected $variable;

    /**
     * Contructor
     * 
     * @param Magento\Framework\HTTP\Client\Curl $curl     /
     * @param \Magento\Variable\Model\Variable   $variable /
     */
    public function __construct(
        Curl $curl,
        Variable $variable
    ) {
        $this->curl = $curl;
        $this->variable = $variable;
    }

    /**
     * Get the first token for this Logisnap account
     * 
     * @param string $email    /
     * @param string $password /
     * 
     * @return string
     */
    public function getFirstToken($email, $password)
    {
        $token1 = "";

        $url = 'https://logi-scallback.azurewebsites.net/v1/user/getaccesstoken';
    
        //credidentials entered in the form
        $params = [
            'Email' => $email,
            'Password' => $password,
        ];

        //execute POST request
        $this->curl->post($url, $params);
        $curlresult = json_decode($this->curl->getBody());

        if ($this->curl->getStatus() == 200) {
            $token1 = $curlresult;
        }

        return $token1;
    }

    /**
     * Get all the webshops owned by this Logisnap account
     * 
     * @param string $token1 /
     * 
     * @return array
     */
    public function getWebshops($token1)
    {
        $headers = [
            "Content-Type" => "application/json", 
            "Authorization" => "basic ". $token1
        ];
        $this->curl->setHeaders($headers);

        $url = 'https://logiapiv1.azurewebsites.net/user/getaccounts';
        
        $this->curl->get($url);
        $allShops = json_decode($this->curl->getBody());
    
        //array with all the AccountToken
        return $allShops;
    }

    //full token : token1 and token2 are stored in DB 

    /**
     * Get the full token : token1 and token2 are stored in DB 
     * 
     * @return string
     */
    public function getAccessToken()
    {
        $accesstoken = "";

        $token1 = $this->variable->loadByCode(
            'logisnap_first_token', 'base'
        )->getPlainValue();

        $token2 = $this->variable->loadByCode(
            'logisnap_webshop_token', 'base'
        )->getPlainValue();

        if ($token1 != null && $token2 != null) {
            $accesstoken = $token1 .'.'. $token2;
        }

        return $accesstoken;
    }
}