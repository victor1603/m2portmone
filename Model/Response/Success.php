<?php

namespace CodeCustom\Portmone\Model\Response;

use CodeCustom\Portmone\Api\Response\SuccessInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use CodeCustom\Portmone\Helper\Config\PortmoneConfig;
use Magento\Sales\Model\Order;
use Magento\Setup\Exception;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;

class Success implements SuccessInterface
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
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var InvoiceService
     */
    protected $_invoiceService;

    /**
     * @var Transaction
     */
    protected $_transaction;

    /**
     * @var Invoice
     */
    protected $_invoice;

    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var BuilderInterface
     */
    protected $_transactionBuilder;

    /**
     * @var Order
     */
    protected $orderModel;

    /**
     * @var OrderResource
     */
    protected $orderResource;

    /**
     * @var null
     */
    public $history = null;

    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        PortmoneConfig $configHelper,
        StoreManagerInterface $storeManager,
        InvoiceService $_invoiceService,
        Transaction $_transaction,
        Invoice $_invoice,
        OrderRepositoryInterface $_orderRepository,
        BuilderInterface $_transactionBuilder,
        Order $order,
        OrderResource $orderResource
    )
    {
        $this->request = $request;
        $this->response = $response;
        $this->configHelper = $configHelper;
        $this->storeManager = $storeManager;
        $this->_invoiceService = $_invoiceService;
        $this->_transaction = $_transaction;
        $this->_invoice = $_invoice;
        $this->_orderRepository = $_orderRepository;
        $this->_transactionBuilder = $_transactionBuilder;
        $this->orderModel = $order;
        $this->orderResource = $orderResource;
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function success()
    {
        $params = $this->request->getParams();
        $orderId = isset($params['SHOPORDERNUMBER']) ? $this->configHelper->parseOrderId($params['SHOPORDERNUMBER']) : null;
        $result = isset($params['RESULT']) ? $params['RESULT'] : null;

        /**
         * if we have != 0 result on sucess page
         * we send redirect to failure page
         */
        if ($result != 0) {
            $this->response->setRedirect($this->configHelper->getFailureUrl())->sendResponse();
        }

        /**
         * We work with order status
         * Order transaction
         * and order invoice
         */
        if ($orderId) {
            $order = $this->orderModel->loadByIncrementId($orderId);
            $transactionId = isset($params['SHOPBILLID']) ? $params['SHOPBILLID'] : null;
            $state = $order->getState();
            $this->history[] = __("Payment from Portmane, order: %1 status: %2", $order->getIncrementId(), 'success');
            $invoice = $this->createInvoice($order, $transactionId, 2);
            if ($invoice) {
                $this->createTransaction(
                    $order,
                    [
                        'id' => $transactionId,
                        'order_id' => $order->getIncrementId()
                    ]
                );
            }
            $this->changeOrder($this->configHelper->getOrderSuccessStatus(), $order);
        }

        /**
         * Make redirect to front page
         */
        $redirectUrl = $this->configHelper->getFrontRedirectUrl();
        if (isset($params['ATTRIBUTE1']) && strpos($params['ATTRIBUTE1'], 'http') !== false) {
            $redirectUrl = $this->configHelper->buildPureFrontUrl($params['ATTRIBUTE1']);
        }
        if ($redirectUrl) {
            $this->response->setRedirect($redirectUrl)->sendResponse();
        } else {
            $this->response->setRedirect($this->storeManager->getStore()->getBaseUrl() . 'checkout/onepage/success/')->sendResponse();
        }
        return $params;
    }

    /**
     * @param Order|null $order
     * @param array $paymentData
     * @return bool
     * @throws \Exception
     */
    private function createTransaction(Order $order = null, $paymentData = array())
    {
        try {
            $this->history[] = __("Creating transaction with ID: %1", $paymentData['id']);
            $payment = $order->getPayment();
            $payment->setLastTransId($paymentData['id']);
            $payment->setTransactionId($paymentData['id']);
            $payment->setMethod($order->getPayment()->getMethod());
            $payment->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $paymentData]
            );
            $formatedPrice = $order->getBaseCurrency()->formatTxt(
                $order->getGrandTotal()
            );

            $message = __('The authorized amount is %1.', $formatedPrice);
            $trans = $this->_transactionBuilder;
            $transaction = $trans->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($paymentData['id'])
                ->setAdditionalInformation(
                    [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $paymentData]
                )
                ->setFailSafe(true)
                ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);

            $payment->addTransactionCommentsToOrder(
                $transaction,
                $message
            );
            $payment->setParentTransactionId(null);
            $payment->save();
            $order->save();

            return  $transaction->save()->getTransactionId();
        } catch (Exception $e) {
            $this->history[] = __("Error transaction with ID: %1 not created", $paymentData['id']);
        }

        return true;
    }

    /**
     * @param Order $order
     * @param $transactionId
     * @param $invoiceState
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function createInvoice(Order $order, $transactionId, $invoiceState)
    {
        $this->history[] = __("Creating invoice with transaction ID: %1 and state: %2", $transactionId, $invoiceState);

        try {
            if ($order->canInvoice()) {
                $invoice = $this->_invoiceService->prepareInvoice($order);
                $invoice->register()->pay();
                $invoice->setState($invoiceState);
                if ($transactionId && !is_null($transactionId)) {
                    $invoice->setTransactionId($transactionId);
                }
                $transactionSave = $this->_transaction
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder()
                    );
                $transactionSave->save();
            } else {
                $invoiceCollection = $order->getInvoiceCollection();
                if (isset($invoiceCollection->getData()[0])) {
                    $invoice = $invoiceCollection->getData()[0];
                }
                if (isset($invoice['increment_id'])) {
                    $invoiceData = $this->_invoice->loadByIncrementId($invoice['increment_id']);
                    $invoiceData->setState($invoiceState);
                    if ($transactionId && !is_null($transactionId)) {
                        $invoiceData->setTransactionId($transactionId);
                    }
                    $invoiceData->save();
                }
            }
        } catch (\Exception $exception) {
            $this->history[] = __("Error creating incoice with order ID: %1 ", $order->getIncrementId());
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

    private function changeCustomer()
    {
        // change some customer info
    }

}
