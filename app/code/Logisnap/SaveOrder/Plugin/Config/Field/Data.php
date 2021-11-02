<?php
/**
 * Copyright © LogiSnap, Inc. All rights reserved.
 * php version 7.3.29

 * @category Plugin
 * @package  Logisnap
 * @author   Logisnap <user@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-only
 * @link     https://logisnap.com/
 */

namespace Logisnap\SaveOrder\Plugin\Config\Field;

use Magento\Config\Model\Config\Structure\Data as StructureData;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\HTTP\Client\Curl;
use Logisnap\SaveOrder\Model\Token;
use Logisnap\SaveOrder\Model\Carrier\LogisnapCarriers;

/**
 * SaveOrder Plugin

 * @category Plugin
 * @package  Logisnap
 * @author   Logisnap <user@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-only
 * @link     https://logisnap.com/
 */
class Data
{
    protected $token;
    protected $curl;

    /**
     * Contructor
     * 
     * @param Magento\Framework\Module\ModuleListInterface $moduleList /
     * @param Logisnap\SaveOrder\Model\Token               $token      /
     * @param Magento\Framework\HTTP\Client\Curl           $curl       /
     */
    public function __construct(ModuleListInterface $moduleList, 
        Token $token,
        Curl $curl
    ) {
        $this->_moduleList = $moduleList;
        $this->token = $token;
        $this->curl = $curl;
    }

    /**
     * Executed before Magento merge function
     * 
     * @param \Magento\Config\Model\Config\Structure\Data $object /
     * @param array                                       $config /
     * 
     * @return array
     */
    public function beforeMerge(StructureData $object, array $config)
    {
        $accesstoken = $this->token->getAccessToken();

        if ($accesstoken != null) {

            $sections = $config['config']['system']['sections'];
            foreach ($sections as $sectionId => $section) {
                if ($section['id'] !== 'carriers') {
                    continue;
                }
    
                //array of carriers based on API response
                $dynamicGroups = $this->getGroups('Logisnap_SaveOrder', $section['id'], $accesstoken);
    
                //append groups to section with ID 'carriers'
                if (!empty($dynamicGroups)) {
                    $config['config']['system']['sections'][$sectionId]['children'] = $dynamicGroups + $section['children'];
                }
                break;
            }

        }

        return [$config];
    }

    /**
     * Get all the carriers in form of a group
     * 
     * @param string $moduleName  /
     * @param string $sectionName /
     * @param string $accesstoken /
     * 
     * @return array
     */
    protected function getGroups($moduleName, $sectionName, $accesstoken)
    {
        $headers = [
            "Content-Type" => "application/json", 
            "Authorization" => "basic ". $accesstoken
        ];
        $this->curl->setHeaders($headers);

        $url = 'https://logiapiv1uat.azurewebsites.net/logistics/order/shipmenttypes';
        
        $this->curl->get($url);
        $curlresult = json_decode($this->curl->getBody());

        $groups = [];

        if ($curlresult != null) {

            foreach ($curlresult as $carrier) {
                $groups[] = [
                    'id'            => $carrier->UID,
                    'label'         => __($carrier->ClientName.' - '.$carrier->Name),
                    'showInDefault' => '1',
                    'showInWebsite' => '1',
                    'showInStore'   => '1',
                    '_elementType'  => 'group',
                    'path'          => $sectionName,
                    'children'      => $this->getAllFields($carrier->UID, $moduleName, $sectionName) //the fields under each carrier
                ];
            }

        }

        return $groups;
    }

    /**
     * Get all the fields (enable dropdown, price input) for each carrier
     * 
     * @param string $carrier     /
     * @param string $moduleName  /
     * @param string $sectionName /
     * 
     * @return array
     */
    protected function getAllFields($carrier, $moduleName, $sectionName)
    {
        //fields needed for each carrier : enable/disable - price
        return [
            $carrier.'-active' => [
                'id' => 'active',
                'type'          => 'select',
                'showInDefault' => '1',
                'showInWebsite' => '1',
                'showInStore'   => '1',
                'module_name'   => $moduleName,
                '_elementType'  => 'field',
                'path'          => $sectionName . '/' . $carrier,
                'label'          => __('Enabled'),
                'source_model' => 'Magento\Config\Model\Config\Source\Yesno',
                'show'           => 1
            ],
            $carrier.'-price' => [
                'id' => 'price',
                'type'          => 'text',
                'showInDefault' => '1',
                'showInWebsite' => '1',
                'showInStore'   => '1',
                'module_name'   => $moduleName,
                '_elementType'  => 'field',
                'path'          => $sectionName . '/' . $carrier,
                'label'          => __('Price'),
                'show'           => 1
            ]
        ];
    }
}