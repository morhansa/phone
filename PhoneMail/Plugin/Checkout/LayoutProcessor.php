<?php
/**
 * PhoneMail Module
 *
 * @category  PhoneMail
 * @package   PhoneMail\Plugin\Checkout
 * @author    MagoArab
 * @copyright Copyright (c) 2025
 */
declare(strict_types=1);

namespace MagoArab\PhoneMail\Plugin\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor as CheckoutLayoutProcessor;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class LayoutProcessor
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * XML path for module enabled configuration
     */
    const XML_PATH_MODULE_ENABLED = 'phonemail/general/enabled';

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
     * Checkout LayoutProcessor after process plugin
     *
     * @param CheckoutLayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     */
    public function afterProcess(
        CheckoutLayoutProcessor $subject,
        array $jsLayout
    ) {
        if (!$this->isModuleEnabled()) {
            return $jsLayout;
        }

        // Process shipping address telephone field
        if (isset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['telephone'])) {
            
            $telephoneConfig = &$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
                ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['telephone'];
            
            // Ensure telephone is required and visible
            $telephoneConfig['validation'] = [
                'required-entry' => true,
                'validate-phone-number' => true
            ];
            $telephoneConfig['required'] = true;
            $telephoneConfig['visible'] = true;
            
            // Add a custom label to show this is the primary phone field
            $telephoneConfig['label'] = __('Phone Number');
            
            // Make sure it has focus
            $telephoneConfig['autofocus'] = true;
            
            // Move it to the top by setting sortOrder
            $telephoneConfig['sortOrder'] = 10;
            
            // Add a custom CSS class
            if (isset($telephoneConfig['additionalClasses'])) {
                $telephoneConfig['additionalClasses'] .= ' phonemail-primary-phone';
            } else {
                $telephoneConfig['additionalClasses'] = 'phonemail-primary-phone';
            }
            
            // Set custom placeholder
            $telephoneConfig['placeholder'] = __('Enter your phone number');
            
            // Make sure it appears before other fields - adjust sorting of all other fields
            $fieldset = &$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
                ['children']['shippingAddress']['children']['shipping-address-fieldset']['children'];
            
            foreach ($fieldset as $code => &$field) {
                if ($code !== 'telephone' && isset($field['sortOrder'])) {
                    // Ensure all other fields have a higher sort order
                    $field['sortOrder'] = $field['sortOrder'] + 100;
                }
            }
        }
        
        // Hide email field from shipping address
        if (isset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['email'])) {
            
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
                ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['email']['visible'] = false;
        }

        return $jsLayout;
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