<?php

namespace CodeCustom\Portmone\Model;

use Magento\Sales\Model\Order;
use Magento\Customer\Api\CustomerRepositoryInterface;
use CodeCustom\Portmone\Model\Curl\Transport;
use CodeCustom\Portmone\Model\Data as TransportData;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;

class PortmonePaymentLink
{
    const URL_PATH          = 'portmone/pay/order';

    const PWA_URL_PATH      = '/p';

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var Transport
     */
    protected $curlTransport;

    /**
     * @var Data
     */
    protected $transportData;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    protected $customerFactory;

    /**
     * PortmonePaymentLink constructor.
     * @param CustomerRepositoryInterface $customerRepository
     * @param Transport $curlTransport
     * @param Data $transportData
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        Transport $curlTransport,
        TransportData $transportData,
        StoreManagerInterface $storeManager,
        CustomerInterfaceFactory $customerFactory
    )
    {
        $this->customerRepository = $customerRepository;
        $this->curlTransport = $curlTransport;
        $this->transportData = $transportData;
        $this->storeManager = $storeManager;
        $this->customerFactory = $customerFactory;
    }

    /**
     * @param Order $order
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getInternalPaymentUrl(Order $order)
    {
        return $this->storeManager->getStore()->getBaseUrl()
            . self::URL_PATH
            . '?o=' . base64_encode($order->getIncrementId())
            . '&c=' . base64_encode($order->getCustomerId());
    }

    /**
     * @param Order $order
     * @return string
     */
    public function getPWAPaymentUrl(Order $order)
    {
        return self::PWA_URL_PATH
            . '?o=' . base64_encode($order->getIncrementId())
            . '&c=' . base64_encode($order->getCustomerId());
    }

    /**
     * This method ganerate unique encripted link from PORTMONE
     * @param Order $order
     * @return mixed|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getPaymentLink(Order $order)
    {
        try {
            if ($order->getCustomerId()) {
                $customer = $this->customerRepository->getById($order->getCustomerId());
            } else {
                $customer = $this->customerFactory->create();
            }

            $result = $this->curlTransport->getRequestUrl(
                $this->transportData->getData($order, $customer)
            );
        } catch (\Exception $exception) {
            $result = null;
        }

        return $result;
    }

}
