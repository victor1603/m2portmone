<?php

namespace CodeCustom\Portmone\Plugin\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Resolver\PlaceOrder as PlaseOrderResolve;
use Magento\Sales\Model\Order;
use CodeCustom\Portmone\Model\Portmone;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\View\LayoutFactory;
use CodeCustom\Portmone\Helper\Config\PortmoneConfig;
use CodeCustom\Portmone\Model\PortmonePaymentLink;

class PlaceOrder
{
    /**
     * @var PlaseOrderResolve
     */
    protected $placeOrderResolve;

    /**
     * @var Order
     */
    protected $orderModel;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @var PortmoneConfig
     */
    protected $portmoneHelper;

    protected $portmoneLink;

    public function __construct(
        PlaseOrderResolve $placeOrderResolve,
        Order $orderModel,
        CustomerRepositoryInterface $customerRepository,
        LayoutFactory $layoutFactory,
        PortmoneConfig $portmoneHelper,
        PortmonePaymentLink $paymentLink
    )
    {
        $this->placeOrderResolve = $placeOrderResolve;
        $this->orderModel = $orderModel;
        $this->customerRepository = $customerRepository;
        $this->layoutFactory = $layoutFactory;
        $this->portmoneHelper = $portmoneHelper;
        $this->portmoneLink = $paymentLink;
    }

    /**
     * @param ResolverInterface $subject
     * @param $resolvedValue
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return mixed
     * @throws \Exception
     */
    public function afterResolve(
        ResolverInterface $subject,
        $resolvedValue,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    )
    {
        if (!$resolvedValue['order']) {
            throw new GraphQlInputException(__("Order not created"));
        }

        try {
            $orderId = $resolvedValue['order']['order_number'];
            $order = $this->orderModel->loadByIncrementId($orderId);

            if ($order && $order->getPayment()->getMethod() == Portmone::METHOD_CODE) {
                $resolvedValue['order']['payment_extension_data']['redirect_url'] = $this->portmoneLink->getPaymentLink($order);
                $resolvedValue['order']['payment_extension_data']['html_data'] = '';
                $resolvedValue['order']['payment_extension_data']['payment_method'] = $order->getPayment()->getMethod();
            }
        } catch (\Exception $e) {
            throw new \Exception(__($e->getMessage()));
        }

        return $resolvedValue;
    }

    /**
     * Deprecated from 2021-07-01
     * use cURL to portmone access
     * @param \Magento\Sales\Model\Order $order
     */
    protected function getPaymentLogic($order)
    {
        $customer = null;
        if ($order->getCustomerId()) {
            $customer = $this->customerRepository->getById($order->getCustomerId());
        }

        $formBlock = $this->layoutFactory->create()->createBlock('\CodeCustom\Portmone\Block\Portmone\RedirectForm');
        $formBlock->setOrder($order);
        $formBlock->setCustomer($customer);
        $html = $formBlock->getHtml();

        return $html;
    }

}
