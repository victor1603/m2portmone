<?php

namespace CodeCustom\Portmone\Controller\Checkout;

use CodeCustom\Portmone\Helper\Config\PortmoneConfig;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\Order;
use Magento\Checkout\Model\Session as CheckoutSession;
use CodeCustom\Portmone\Model\Portmone;
use CodeCustom\Portmone\Model\PortmonePaymentLink;

class Payment implements ActionInterface
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected $redirect;

    protected $url;

    protected $checkoutSession;

    protected $configHelper;

    protected $portmoneLink;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    public function __construct(
        Context $context,
        PortmoneConfig $configHelper,
        PortmonePaymentLink $portmoneLink,
        CheckoutSession $checkoutSession
    )
    {
        $this->request = $context->getRequest();
        $this->redirect = $context->getRedirect();
        $this->resultFactory = $context->getResultFactory();
        $this->url = $context->getUrl();
        $this->checkoutSession = $checkoutSession;
        $this->configHelper = $configHelper;
        $this->portmoneLink = $portmoneLink;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Exception
     */
    public function execute()
    {
        if (!$this->configHelper->isEnabled()) {
            throw new \Exception(__('Payment method is not allow.'));
        }
        /**
         * @var $order Order
         */
        $order = $this->checkoutSession->getLastRealOrder();
        if (!($order && $order->getId())) {
            throw new \Exception(__('Order not found'));
        }

        if ($order->getPayment()->getMethod() != Portmone::METHOD_CODE) {
            throw new \Exception('Order payment method is not a Portmone payment method');
        }
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $paymentUrl = $this->portmoneLink->getPaymentLink($order);
        if ($paymentUrl) {
            $result->setData(['status' => 'success', 'redirect' => $paymentUrl]);
        } else {
            $result->setData(['status' => 'error', 'redirect' => $this->url->getUrl('checkout/cart')]);
        }

        return $result;
    }
}
