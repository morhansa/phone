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

namespace MagoArab\PhoneMail\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class PhoneValidator extends AbstractHelper
{
    /**
     * Config paths
     */
    const XML_PATH_MIN_LENGTH = 'phonemail/phone_validation/min_length';
    const XML_PATH_MAX_LENGTH = 'phonemail/phone_validation/max_length';
    const XML_PATH_ALLOWED_FORMATS = 'phonemail/phone_validation/allowed_formats';

    /**
     * Get minimum phone length
     *
     * @param int|null $storeId
     * @return int
     */
    public function getMinLength($storeId = null): int
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_MIN_LENGTH,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return (int)($value ?: 8); // Default to 8 if not set
    }

    /**
     * Get maximum phone length
     *
     * @param int|null $storeId
     * @return int
     */
    public function getMaxLength($storeId = null): int
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_MAX_LENGTH,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return (int)($value ?: 15); // Default to 15 if not set
    }

    /**
     * Get allowed formats
     *
     * @param int|null $storeId
     * @return array
     */
    public function getAllowedFormats($storeId = null): array
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_ALLOWED_FORMATS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        
        if (empty($value)) {
            return ['digits_only']; // Default to digits only if not set
        }
        
        return is_array($value) ? $value : explode(',', $value);
    }

    /**
     * Validate phone number
     *
     * @param string $phone
     * @param int|null $storeId
     * @return bool
     */
    public function isValid(string $phone, $storeId = null): bool
    {
        // Remove spaces and other formatting characters for length check
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Check length
        $length = strlen($cleanPhone);
        if ($length < $this->getMinLength($storeId) || $length > $this->getMaxLength($storeId)) {
            return false;
        }
        
        // Check format
        $formats = $this->getAllowedFormats($storeId);
        
        // If international format is allowed
        if (in_array('international', $formats) && preg_match('/^\+[0-9]{6,}$/', $cleanPhone)) {
            return true;
        }
        
        // If national format is allowed
        if (in_array('national', $formats) && preg_match('/^0[0-9]{6,}$/', $cleanPhone)) {
            return true;
        }
        
        // If digits only is allowed
        if (in_array('digits_only', $formats) && preg_match('/^[0-9]{6,}$/', $cleanPhone)) {
            return true;
        }
        
        // If spaces are allowed
        if (in_array('with_spaces', $formats) && preg_match('/^[0-9\s]{6,}$/', $phone)) {
            return true;
        }
        
        // If dashes are allowed
        if (in_array('with_dashes', $formats) && preg_match('/^[0-9\-]{6,}$/', $phone)) {
            return true;
        }
        
        return false;
    }
}