<?php
/**
 * MagoArab PhoneMail Module
 *
 * @category  MagoArab
 * @package   MagoArab_PhoneMail
 * @author    MagoArab
 * @copyright Copyright (c) 2025
 */
declare(strict_types=1);

namespace MagoArab\PhoneMail\Model;

use MagoArab\PhoneMail\Api\WhatsAppServiceInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class WhatsAppService implements WhatsAppServiceInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * XML path for WhatsApp API configurations
     */
    const XML_PATH_WHATSAPP_ENABLED = 'phonemail/whatsapp/enabled';
    const XML_PATH_WHATSAPP_API_URL = 'phonemail/whatsapp/api_url';
    const XML_PATH_WHATSAPP_API_KEY = 'phonemail/whatsapp/api_key';
    const XML_PATH_WHATSAPP_TEMPLATE_ORDER_CONFIRMATION = 'phonemail/whatsapp/template_order_confirmation';
    const XML_PATH_WHATSAPP_TEMPLATE_SHIPPING_UPDATE = 'phonemail/whatsapp/template_shipping_update';

    /**
     * Constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Curl $curl
     * @param Json $json
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Curl $curl,
        Json $json,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->curl = $curl;
        $this->json = $json;
        $this->logger = $logger;
    }

    /**
     * Send WhatsApp notification for order confirmation
     *
     * @param OrderInterface $order
     * @return bool
     */
    public function sendOrderConfirmation(OrderInterface $order): bool
    {
        if (!$this->isWhatsAppEnabled()) {
            return false;
        }

        $phoneNumber = $this->getPhoneNumber($order);
        if (!$phoneNumber) {
            $this->logger->warning('WhatsApp notification not sent: Phone number not found for order ' . $order->getIncrementId());
            return false;
        }

        $templateName = $this->getOrderConfirmationTemplate();
        $templateParams = $this->prepareOrderConfirmationParams($order);

        return $this->sendWhatsAppMessage($phoneNumber, $templateName, $templateParams);
    }

    /**
     * Send WhatsApp notification for shipping update
     *
     * @param OrderInterface $order
     * @param string $trackingNumber
     * @param string $carrierName
     * @return bool
     */
    public function sendShippingUpdate(OrderInterface $order, string $trackingNumber, string $carrierName): bool
    {
        if (!$this->isWhatsAppEnabled()) {
            return false;
        }

        $phoneNumber = $this->getPhoneNumber($order);
        if (!$phoneNumber) {
            $this->logger->warning('WhatsApp notification not sent: Phone number not found for order ' . $order->getIncrementId());
            return false;
        }

        $templateName = $this->getShippingUpdateTemplate();
        $templateParams = $this->prepareShippingUpdateParams($order, $trackingNumber, $carrierName);

        return $this->sendWhatsAppMessage($phoneNumber, $templateName, $templateParams);
    }

    /**
     * Send WhatsApp message using API
     *
     * @param string $phoneNumber
     * @param string $templateName
     * @param array $templateParams
     * @return bool
     */
    private function sendWhatsAppMessage(string $phoneNumber, string $templateName, array $templateParams): bool
    {
        try {
            $apiUrl = $this->getApiUrl();
            $apiKey = $this->getApiKey();

            if (empty($apiUrl) || empty($apiKey)) {
                $this->logger->error('WhatsApp API configuration incomplete');
                return false;
            }

            // Prepare API request data
            $requestData = [
                'phone' => $phoneNumber,
                'template' => $templateName,
                'params' => $templateParams
            ];

            // Set headers for API request
            $this->curl->addHeader('Content-Type', 'application/json');
            $this->curl->addHeader('Authorization', 'Bearer ' . $apiKey);

            // Post to WhatsApp API
            $this->curl->post($apiUrl, $this->json->serialize($requestData));

            // Get response
            $response = $this->curl->getBody();
            $responseData = $this->json->unserialize($response);

            if (isset($responseData['success']) && $responseData['success'] === true) {
                $this->logger->info('WhatsApp notification sent successfully to ' . $phoneNumber);
                return true;
            } else {
                $errorMessage = $responseData['message'] ?? 'Unknown error';
                $this->logger->error('WhatsApp API error: ' . $errorMessage);
                return false;
            }
        } catch (\Exception $e) {
            $this->logger->error('WhatsApp notification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get phone number from order
     *
     * @param OrderInterface $order
     * @return string|null
     */
    private function getPhoneNumber(OrderInterface $order): ?string
    {
        // Try to get phone from shipping address
        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress && $shippingAddress->getTelephone()) {
            return $this->cleanPhoneNumber($shippingAddress->getTelephone());
        }
        
        // If no shipping address, try billing address
        $billingAddress = $order->getBillingAddress();
        if ($billingAddress && $billingAddress->getTelephone()) {
            return $this->cleanPhoneNumber($billingAddress->getTelephone());
        }
        
        return null;
    }
    
    /**
     * Clean phone number by removing non-numeric characters
     *
     * @param string $phoneNumber
     * @return string
     */
    private function cleanPhoneNumber(string $phoneNumber): string
    {
        // Remove all non-numeric characters
        return preg_replace('/[^0-9]/', '', $phoneNumber);
    }
    
    /**
     * Prepare order confirmation template parameters
     *
     * @param OrderInterface $order
     * @return array
     */
    private function prepareOrderConfirmationParams(OrderInterface $order): array
    {
        // Prepare template parameters for order confirmation
        return [
            'order_id' => $order->getIncrementId(),
            'customer_name' => $order->getCustomerFirstname() ?: ($order->getBillingAddress() ? $order->getBillingAddress()->getFirstname() : 'Customer'),
            'total' => $order->getBaseCurrency()->formatTxt($order->getGrandTotal()),
            'items_count' => (string)$order->getTotalItemCount(),
            'order_date' => $order->getCreatedAt()
        ];
    }
    
    /**
     * Prepare shipping update template parameters
     *
     * @param OrderInterface $order
     * @param string $trackingNumber
     * @param string $carrierName
     * @return array
     */
    private function prepareShippingUpdateParams(OrderInterface $order, string $trackingNumber, string $carrierName): array
    {
        // Prepare template parameters for shipping update
        return [
            'order_id' => $order->getIncrementId(),
            'customer_name' => $order->getCustomerFirstname() ?: ($order->getBillingAddress() ? $order->getBillingAddress()->getFirstname() : 'Customer'),
            'tracking_number' => $trackingNumber,
            'carrier_name' => $carrierName,
            'shipping_date' => date('Y-m-d')
        ];
    }
    
    /**
     * Check if WhatsApp integration is enabled
     *
     * @return bool
     */
    private function isWhatsAppEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_WHATSAPP_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }
    
    /**
     * Get WhatsApp API URL
     *
     * @return string
     */
    private function getApiUrl(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_WHATSAPP_API_URL,
            ScopeInterface::SCOPE_STORE
        ) ?: '';
    }
    
    /**
     * Get WhatsApp API Key
     *
     * @return string
     */
    private function getApiKey(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_WHATSAPP_API_KEY,
            ScopeInterface::SCOPE_STORE
        ) ?: '';
    }
    
    /**
     * Get order confirmation template name
     *
     * @return string
     */
    private function getOrderConfirmationTemplate(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_WHATSAPP_TEMPLATE_ORDER_CONFIRMATION,
            ScopeInterface::SCOPE_STORE
        ) ?: 'order_confirmation';
    }
    
    /**
     * Get shipping update template name
     *
     * @return string
     */
    private function getShippingUpdateTemplate(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_WHATSAPP_TEMPLATE_SHIPPING_UPDATE,
            ScopeInterface::SCOPE_STORE
        ) ?: 'shipping_update';
    }
}