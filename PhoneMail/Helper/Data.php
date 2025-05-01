<?php
/**
 * PhoneMail Module
 *
 * @category  PhoneMail
 * @package   PhoneMail\Helper
 * @author    MagoArab
 * @copyright Copyright (c) 2025
 */
declare(strict_types=1);

namespace MagoArab\PhoneMail\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use MagoArab\PhoneMail\Model\PhoneEmailGenerator;

class Data extends AbstractHelper
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var PhoneEmailGenerator
     */
    protected $phoneEmailGenerator;

    /**
     * XML path for module enabled configuration
     */
    const XML_PATH_MODULE_ENABLED = 'phonemail/general/enabled';

    /**
     * Constructor
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param PhoneEmailGenerator $phoneEmailGenerator
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        PhoneEmailGenerator $phoneEmailGenerator
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->phoneEmailGenerator = $phoneEmailGenerator;
    }

    /**
     * Check if module is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isModuleEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_MODULE_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

/**
 * Generate email from phone number
 *
 * @param string $phoneNumber
 * @param int|null $storeId
 * @param bool $isLogin
 * @return string
 * @throws \Magento\Framework\Exception\LocalizedException
 */
public function generateEmailFromPhone(string $phoneNumber, $storeId = null, bool $isLogin = false): string
{
    if ($storeId === null) {
        $storeId = (int)$this->storeManager->getStore()->getId();
    } elseif (is_string($storeId) && is_numeric($storeId)) {
        $storeId = (int)$storeId;
    }
    
    return $this->phoneEmailGenerator->generateEmailFromPhone($phoneNumber, $storeId, $isLogin);
}

    /**
     * Clean phone number by removing non-numeric characters
     *
     * @param string $phoneNumber
     * @return string
     */
    public function cleanPhoneNumber(string $phoneNumber): string
    {
        return preg_replace('/[^0-9]/', '', $phoneNumber);
    }
}