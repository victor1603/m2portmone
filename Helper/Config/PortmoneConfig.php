<?php

namespace CodeCustom\Portmone\Helper\Config;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class PortmoneConfig extends AbstractHelper
{
    const XML_PATH_IS_ENABLED                       = 'payment/portmone_payment/active';
    const XML_PATH_TITLE                            = 'payment/portmone_payment/title';
    const XML_PATH_PAYEE_ID                         = 'payment/portmone_payment/payee_id';
    const XML_PATH_PAYMENT_NEW_STATUS               = 'payment/portmone_payment/order_status';
    const XML_PATH_PAYMENT_SUCCESS_STATUS           = 'payment/portmone_payment/payment_success_order_status';
    const XML_PATH_PAYMENT_ERROR_STATUS             = 'payment/portmone_payment/payment_error_order_status';
    const XML_PATH_PAYMENT_SHIPPENT                 = 'payment/portmone_payment/allowed_carrier';
    const XML_PATH_DESCRIPTION                      = 'payment/portmone_payment/description';
    const XML_PATH_SUBMIT_URL                       = 'payment/portmone_payment/submit_url';
    const XML_PATH_LANGUAGE                         = 'payment/portmone_payment/language';
    const XML_PATH_SUCCESS_URL                      = 'payment/portmone_payment/success_url';
    const XML_PATH_FAILURE_URL                      = 'payment/portmone_payment/failure_url';
    const XML_PATH_FRONT_URL                        = 'payment/portmone_payment/front_url';
    const XML_PATH_ALLOWED_CARRIERS                 = 'payment/portmone_payment/allowed_carrier';

    const SUCCESS_REST_URL                          = 'rest/V1/portmone/success';
    const FAILURE_REST_URL                          = 'rest/V1/portmone/failure';

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager
    )
    {
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    /** Getting system configuration by field path
     * @param $field
     * @param null $storeId
     * @return mixed
     */
    public function getConfigValue($field, $storeId = null)
    {
        $storeId = $storeId ? $storeId : $this->getSiteStoreId();

        return $this->scopeConfig->getValue(
            $field, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    /** Current store id
     * @return int|null
     */
    public function getSiteStoreId()
    {
        try {
            $storeId = $this->_storeManager->getStore()->getId();
            return $storeId;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        if ($this->scopeConfig->getValue(
            static::XML_PATH_IS_ENABLED,
            ScopeInterface::SCOPE_STORE
        )
        ) {
            return true;
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function getSubmitUrl()
    {
        return $this->getConfigValue(self::XML_PATH_SUBMIT_URL);
    }

    /**
     * @return mixed
     */
    public function getSuccessUrl()
    {
        return $this->getBaseurl() . self::SUCCESS_REST_URL;
    }

    /**
     * @return mixed
     */
    public function getFailureUrl()
    {
        return $this->getBaseurl() . self::FAILURE_REST_URL;
    }

    /**
     * @return mixed
     */
    public function getPayeeId()
    {
        return $this->getConfigValue(self::XML_PATH_PAYEE_ID);
    }

    /**
     * @return mixed
     */
    public function getDescription($orderIncrementId = null)
    {
        return $this->getConfigValue(self::XML_PATH_DESCRIPTION);
    }

    /**
     * @return mixed
     */
    public function getLanguage()
    {
        return $this->getConfigValue(self::XML_PATH_LANGUAGE);
    }

    public function getFrontRedirectUrl()
    {
        return $this->getConfigValue(self::XML_PATH_FRONT_URL);
    }

    public function getOrderNewStatus()
    {
        return $this->getConfigValue(self::XML_PATH_PAYMENT_NEW_STATUS);
    }

    public function getOrderSuccessStatus()
    {
        return $this->getConfigValue(self::XML_PATH_PAYMENT_SUCCESS_STATUS);
    }

    public function getOrderFailureStatus()
    {
        return $this->getConfigValue(self::XML_PATH_PAYMENT_ERROR_STATUS);
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getBaseurl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    /**
     * @return mixed
     */
    public function getAllowedCarriers()
    {
        return $this->getConfigValue(self::XML_PATH_ALLOWED_CARRIERS);
    }

}
