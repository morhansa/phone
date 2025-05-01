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

namespace MagoArab\PhoneMail\Model\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use MagoArab\PhoneMail\Api\WhatsAppServiceInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class SendWhatsAppShippingNotification implements ObserverInterface
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
    const XML_PATH_WHATSAPP_ENABLED = 'phonemail/whatsapp/enabled';

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
        if (!$this->isModuleEnabled() || !$this->isWhatsAppEnabled()) {
            return;
        }

        try {
            $shipment = $observer->getEvent()->getShipment();
            if ($shipment) {
                $order = $shipment->getOrder();
                $tracks = $shipment->getAllTracks();
                
                foreach ($tracks as $track) {
                    $trackingNumber = $track->getTrackNumber();
                    $carrierName = $track->getTitle() ?: $track->getCarrierCode();
                    
                    if ($trackingNumber && $carrierName) {
                        $this->whatsAppService->sendShippingUpdate($order, $trackingNumber, $carrierName);
                        // Send notification for the first tracking number only
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error sending WhatsApp shipping notification: ' . $e->getMessage());
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

    /**
     * Check if WhatsApp is enabled
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
}