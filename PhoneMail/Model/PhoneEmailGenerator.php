<?php
/**
 * PhoneMail Module
 *
 * @category  PhoneMail
 * @package   PhoneMail\Model
 * @author    MagoArab
 * @copyright Copyright (c) 2025
 */
declare(strict_types=1);

namespace MagoArab\PhoneMail\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class PhoneEmailGenerator
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * XML path for custom domain configuration
     */
    const XML_PATH_CUSTOM_DOMAIN = 'phonemail/general/custom_domain';
    
    /**
     * XML path for using custom domain or store domain
     */
    const XML_PATH_USE_CUSTOM_DOMAIN = 'phonemail/general/use_custom_domain';

    /**
     * Constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
    }

/**
 * Generate email address from phone number
 *
 * @param string $phoneNumber
 * @param int|null $storeId
 * @param bool $isLogin Flag to indicate if this is a login process
 * @return string
 * @throws LocalizedException
 */
public function generateEmailFromPhone(string $phoneNumber, ?int $storeId = null, bool $isLogin = false): string
{
    // Clean phone number (remove non-numeric characters)
    $cleanPhone = $this->cleanPhoneNumber($phoneNumber);
    
    if (empty($cleanPhone)) {
        throw new LocalizedException(__('Phone number is required to generate email address'));
    }
    
    // Get email domain
    $domain = $this->getEmailDomain($storeId);
    
    // Generate email
    $email = $cleanPhone . '@' . $domain;
    
    // Skip email uniqueness validation for login
    if (!$isLogin) {
        $this->validateEmailUniqueness($email);
    }
    
    return $email;
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
     * Get the email domain to use (either store domain or custom domain)
     *
     * @param int|null $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    private function getEmailDomain(?int $storeId = null): string
    {
        $useCustomDomain = $this->scopeConfig->isSetFlag(
            self::XML_PATH_USE_CUSTOM_DOMAIN,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        
        if ($useCustomDomain) {
            // Use custom domain from configuration
            $domain = $this->scopeConfig->getValue(
                self::XML_PATH_CUSTOM_DOMAIN,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
            
            if (!empty($domain)) {
                return $domain;
            }
        }
        
        // Fallback to store domain
        $store = $this->storeManager->getStore($storeId);
        $baseUrl = $store->getBaseUrl();
        
        // Extract domain from base URL
        $parsedUrl = parse_url($baseUrl);
        $domain = $parsedUrl['host'] ?? '';
        
        // Remove www. if present
        $domain = preg_replace('/^www\./', '', $domain);
        
        return $domain;
    }

/**
 * Validate that email doesn't already exist in the system
 *
 * @param string $email
 * @return bool
 * @throws LocalizedException
 */
private function validateEmailUniqueness(string $email): bool
{
    try {
        $this->customerRepository->get($email);
        // If we get here, email exists so throw an exception for registration process
        throw new LocalizedException(__('An account with this phone number already exists. Please login instead.'));
    } catch (NoSuchEntityException $e) {
        // Email doesn't exist, which is what we want for registration
        return true;
    }
}
}