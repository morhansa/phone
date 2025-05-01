<?php
/**
 * PhoneMail Module
 *
 * @category  PhoneMail
 * @package   PhoneMail\Block\Customer
 * @author    MagoArab
 * @copyright Copyright (c) 2025
 */
declare(strict_types=1);

namespace MagoArab\PhoneMail\Block\Customer;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use MagoArab\PhoneMail\Helper\Data as PhoneMailHelper;
use Magento\Store\Model\StoreManagerInterface;

class PhoneEmail extends Template
{
    /**
     * @var PhoneMailHelper
     */
    protected $phoneMailHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PhoneMailHelper $phoneMailHelper
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        PhoneMailHelper $phoneMailHelper,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->phoneMailHelper = $phoneMailHelper;
        $this->storeManager = $storeManager;
    }

    /**
     * Check if module is enabled
     *
     * @return bool
     */
    public function isModuleEnabled(): bool
    {
        return $this->phoneMailHelper->isModuleEnabled();
    }

    /**
     * Get example email based on a sample phone number
     *
     * @param string $samplePhone
     * @return string
     */
    public function getExampleEmail(string $samplePhone = '1234567890'): string
    {
        try {
            $storeId = $this->storeManager->getStore()->getId();
            return $this->phoneMailHelper->generateEmailFromPhone($samplePhone, $storeId);
        } catch (\Exception $e) {
            // If there's an error, return a generic example
            $domain = $this->storeManager->getStore()->getBaseUrl();
            $domain = str_replace(['http://', 'https://', '/'], '', $domain);
            return $samplePhone . '@' . $domain;
        }
    }
}