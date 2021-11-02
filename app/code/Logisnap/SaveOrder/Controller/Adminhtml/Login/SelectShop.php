<?php
/**
 * Copyright © LogiSnap, Inc. All rights reserved.
 * php version 7.3.29

 * @category Controller
 * @package  Logisnap
 * @author   Logisnap <user@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-only
 * @link     https://logisnap.com/
 */

namespace Logisnap\SaveOrder\Controller\Adminhtml\Login;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Logisnap\SaveOrder\Model\Token;
use Magento\Variable\Model\Variable;
use Magento\Variable\Model\VariableFactory;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * SelectShop Controller

 * @category Controller
 * @package  Logisnap
 * @author   Logisnap <user@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-only
 * @link     https://logisnap.com/
 */
class SelectShop extends Action
{
    protected $token;
    protected $variable;
    protected $variableFactory;
    protected $resultJsonFactory;

    /**
     * Contructor
     * 
     * @param \Magento\Backend\App\Action\Context              $context           /
     * @param \Logisnap\SaveOrder\Model\Token                  $token             /
     * @param \Magento\Variable\Model\Variable                 $variable          /
     * @param \Magento\Variable\Model\VariableFactory          $variableFactory   /
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory / 
     */
    public function __construct(
        Context $context,
        Token $token,
        Variable $variable,
        VariableFactory $variableFactory,
        JsonFactory $resultJsonFactory
    ) {
        $this->token = $token;
        $this->variable = $variable;
        $this->variableFactory = $variableFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        return parent::__construct($context);
    }

    /**
     * Executed when the user select a web shop and clicks on Save btn
     * 
     * @return \Magento\Framework\Controller\Result\JsonFactory
     */
    public function execute()
    {
        //params passed from login.phtml
        $token2 = $this->getRequest()->getParam('shopToken');
        $email = $this->getRequest()->getParam('email');
        $password = $this->getRequest()->getParam('password');
        $result = $this->resultJsonFactory->create();

        if ($token2 != null && $email != null && $password != null) {
            $this->storeData($email, 'logisnap_email');
            $this->storeData($password, 'logisnap_password');
            $this->storeData($token2, 'logisnap_webshop_token');
            $result->setData(['logged' => true]);
        }

        return $result;
    }


    /**
     * Save the data in variable table
     *
     * @param string $plainValue /
     * @param string $code       /
     * 
     * @return void
     */
    protected function storeData($plainValue, $code) 
    {
        $variable = $this->variableFactory->create();
        $specificVar = $variable->loadByCode($code);

        $data = [
            'variable_id' => $specificVar->getId(),
            'code' => $code,
            'name' => $code,
            'html_value' => $plainValue,
            'plain_value' => $plainValue,

        ];
        $variable->setData($data);
        $variable->save();  
    }
}