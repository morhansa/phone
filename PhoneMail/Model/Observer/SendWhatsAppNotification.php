<?php
/**
 * PhoneMail Module
 *
 * @category  PhoneMail
 * @package   PhoneMail\Model\Observer
 * @author    MagoArab
 * @copyright Copyright (c) 2025
 */
declare(strict_types=1);

namespace MagoArab\PhoneMail\Model\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use MagoArab\PhoneMail\Api\WhatsAppServiceInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class SendWhatsAppNotification implements ObserverInterface
{
    /**
     * @var WhatsAppServiceInterface
     */
    private $whatsAppService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * XML path for module enabled configuration
     */
    const XML_PATH_MODULE_ENABLED = 'phonemail/general/enabled';

    /**
     * Constructor
     *
     * @param WhatsAppServiceInterface $whatsAppService
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        WhatsAppServiceInterface $whatsAppService,
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->whatsAppService = $whatsAppService;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$this->isModuleEnabled()) {
            return;
        }

        try {
            $order = $observer->getEvent()->getOrder();
            if ($order) {
                $this->whatsAppService->sendOrderConfirmation($order);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error sending WhatsApp notification: ' . $e->getMessage());
        }
    }

    /**
     * Check if module is enabled
     *
     * @return bool
     */
    private function isModuleEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_MODULE_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }
}