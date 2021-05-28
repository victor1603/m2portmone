<?php

namespace CodeCustom\Portmone\Model\Response;

use CodeCustom\Portmone\Api\Response\FailureInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use CodeCustom\Portmone\Helper\Config\PortmoneConfig;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;

class Failure implements FailureInterface
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var PortmoneConfig
     */
    protected $configHelper;

    /**
     * @var Order
     */
    protected $orderModel;

    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var OrderResource
     */
    protected $orderResource;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var null
     */
    public $history = null;

    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        PortmoneConfig $configHelper,
        OrderRepositoryInterface $_orderRepository,
        Order $orderModel,
        OrderResource $orderResource,
        StoreManagerInterface $storeManager
    )
    {
        $this->response = $response;
        $this->request = $request;
        $this->configHelper = $configHelper;
        $this->_orderRepository = $_orderRepository;
        $this->orderModel = $orderModel;
        $this->orderResource = $orderResource;
        $this->storeManager = $storeManager;
    }

    /**
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function failure()
    {
        $params = $this->request->getParams();
        $orderId = isset($params['SHOPORDERNUMBER']) ? $params['SHOPORDERNUMBER'] : null;

        if ($orderId) {
            $order = $this->orderModel->loadByIncrementId($orderId);
            $this->history[] = __("Payment from Portmane, order: %1 status: %2", $order->getIncrementId(), 'failure');
            $this->changeOrder($this->configHelper->getOrderFailureStatus(), $order);
        }

        if ($this->configHelper->getFrontRedirectUrl()) {
            $this->response->setRedirect($this->configHelper->getFrontRedirectUrl())->sendResponse();
        } else {
            $this->response->setRedirect($this->storeManager->getStore()->getBaseUrl() . 'checkout/onepage/success/')->sendResponse();
        }
        return true;
    }

    /**
     * @param $state
     * @param Order $order
     * @param array $history
     * @return bool
     * @throws \Exception
     */
    private function changeOrder($state, Order $order, $history = [])
    {
        if ($this->history) {
            $history += $this->history;
        }

        if ($state) {
            $order->setStatus($state);

        }

        if (count($history)) {
            $order->addStatusHistoryComment(implode(' ', $history))
                ->setIsCustomerNotified(true);
        }

        $this->orderResource->save($order);
        return true;
    }

}
