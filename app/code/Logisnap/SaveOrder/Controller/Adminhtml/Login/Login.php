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
 * Login Controller

 * @category Controller
 * @package  Logisnap
 * @author   Logisnap <user@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-only
 * @link     https://logisnap.com/
 */
class Login extends Action
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
     * Executed when the user clicks on Login btn
     * 
     * @return \Magento\Framework\Controller\Result\JsonFactory
     */
    public function execute()
    {
        //params passed from login.phtml
        $email = $this->getRequest()->getParam('email');
        $password = $this->getRequest()->getParam('password');

        //get the first token only
        $firstToken = $this->token->getFirstToken($email, $password);

        //get the variables value from the DB with their 'code' attribute
        //loadByCode($variable_code_name, $store_code)
        $existingEmail = $this->variable->loadByCode(
            'logisnap_email', 'base'
        )->getPlainValue();

        $existingPassword = $this->variable->loadByCode(
            'logisnap_password', 'base'
        )->getPlainValue();

        $existingWebshop = $this->variable->loadByCode(
            'logisnap_webshop_token', 'base'
        )->getPlainValue();

        $result = $this->resultJsonFactory->create();

        // 1-Token is OK - always store it
        if ($firstToken != null) {
            $this->storeData($firstToken, 'logisnap_first_token');
        }

        // 2-Token is OK and shop is not selected (very first installation for ex)
        if ($firstToken != null && $existingWebshop == null) {
            $allShops = $this->token->getWebshops($firstToken);
            $result->setData(['logged' => true, 'allShops' => $allShops]);
        }

        // 3-Token is NOT OK
        if ($firstToken == null) {
            $this->storeData(null, 'logisnap_email');
            $this->storeData(null, 'logisnap_password');
            $this->storeData(null, 'logisnap_webshop_token');
            $this->storeData(null, 'logisnap_first_token');
            $result->setData(['logged' => false, 'allShops' => []]);
        }

        // 4-Token is OK and creds are already stored - DO NOTING and return success
        if ($firstToken != null && $existingEmail != null) {
            $result->setData(['logged' => true, 'allShops' => []]);
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