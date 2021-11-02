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
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Variable\Model\Variable;


/**
 * Index Controller

 * @category Controller
 * @package  Logisnap
 * @author   Logisnap <user@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-only
 * @link     https://logisnap.com/
 */
class Index extends Action implements HttpGetActionInterface
{
    const MENU_ID = 'Logisnap_SaveOrder::logisnap_saveorder';

    protected $resultPageFactory;
    protected $variable;

    /**
     * Contructor
     * 
     * @param \Magento\Backend\App\Action\Context        $context           /
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory /
     * @param \Magento\Variable\Model\Variable           $variable          /
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Variable $variable
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->variable = $variable;
    }

    /**
     * Executed when clicking the module
     * 
     * @return \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {
        //get the varibale from the DB with their 'code' attribute
        //loadByCode($variable_code_name, $store_code)
        $existingEmail = $this->variable->loadByCode(
            'logisnap_email', 'base'
        )->getPlainValue();
        $existingPassword = $this->variable->loadByCode(
            'logisnap_password', 'base'
        )->getPlainValue();

        $page = $this->resultPageFactory->create();
        $page->setActiveMenu(static::MENU_ID);
        $page->getConfig()->getTitle()->prepend(__('Logisnap'));
        $block = $page->getLayout()->getBlock('logisnap.login.index.layout');

        if ($existingEmail != null && $existingPassword != null) {
            $block->setData('email', $existingEmail);
            $block->setData('password', $existingPassword);
        } else {
            $block->setData('email', '');
            $block->setData('password', '');
        }

        return $page;
    }
}