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

namespace MagoArab\PhoneMail\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class PhoneMailConfig implements SectionSourceInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * XML path configurations
     */
    const XML_PATH_MODULE_ENABLED = 'phonemail/general/enabled';
    const XML_PATH_WHATSAPP_ENABLED = 'phonemail/whatsapp/enabled';

    /**
     * Constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get section data
     *
     * @return array
     */
    public function getSectionData(): array
    {
        return [
            'initialized' => true,
            'module_enabled' => $this->isModuleEnabled(),
            'whatsapp_enabled' => $this->isWhatsAppEnabled(),
        ];
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
        if (!$this->isModuleEnabled()) {
            return false;
        }
        
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_WHATSAPP_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }
}