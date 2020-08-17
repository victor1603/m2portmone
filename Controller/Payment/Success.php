<?php

namespace CodeCustom\Portmone\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class Success extends Action
{

    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    public function execute()
    {
        $post = $this->getRequest()->getPost();
        $params = $this->getRequest()->getParams();
        echo '<pre>';
        print_r($post);
        echo '<pre>';
        print_r($params);
        exit;
        return '';
    }

}
