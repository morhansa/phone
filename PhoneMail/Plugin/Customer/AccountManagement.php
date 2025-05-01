<?php
/**
 * PhoneMail Module
 *
 * @category  PhoneMail
 * @package   PhoneMail\Plugin\Customer
 * @author    MagoArab
 * @copyright Copyright (c) 2025
 */
declare(strict_types=1);

namespace MagoArab\PhoneMail\Plugin\Customer;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use MagoArab\PhoneMail\Model\PhoneEmailGenerator;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ObjectManager;

class AccountManagement
{
    /**
     * @var PhoneEmailGenerator
     */
    private $phoneEmailGenerator;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * XML path for module enabled configuration
     */
    const XML_PATH_MODULE_ENABLED = 'phonemail/general/enabled';

    /**
     * Constructor
     *
     * @param PhoneEmailGenerator $phoneEmailGenerator
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        PhoneEmailGenerator $phoneEmailGenerator,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger = null
    ) {
        $this->phoneEmailGenerator = $phoneEmailGenerator;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
    }

    /**
     * Before create customer plugin
     *
     * @param AccountManagementInterface $subject
     * @param CustomerInterface $customer
     * @param string|null $password
     * @param string|null $redirectUrl
     * @return array
     * @throws LocalizedException
     */
    public function beforeCreateAccount(
        AccountManagementInterface $subject,
        CustomerInterface $customer,
        $password = null,
        $redirectUrl = null
    ) {
        if (!$this->isModuleEnabled()) {
            return [$customer, $password, $redirectUrl];
        }
        
        try {
            // Check if email is already set and valid
            $email = $customer->getEmail();
            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL) && 
                strpos($email, 'example.com') === false) {
                // If customer already has a valid email, don't override it
                return [$customer, $password, $redirectUrl];
            }
            
            // Get phone number from customer custom attribute
            $phoneNumber = null;
            
            // Try to get from POST data or custom attributes
            if ($customer->getCustomAttribute('telephone')) {
                $phoneNumber = $customer->getCustomAttribute('telephone')->getValue();
            }
                
            // If no phone attribute, try to get from the address
            if (!$phoneNumber && $customer->getAddresses()) {
                foreach ($customer->getAddresses() as $address) {
                    if ($address->getTelephone()) {
                        $phoneNumber = $address->getTelephone();
                        break;
                    }
                }
            }
            
            if (!$phoneNumber) {
                $this->logger->warning('PhoneMail: Phone number not found for customer registration');
                return [$customer, $password, $redirectUrl];
            }
            
            // Generate email based on phone number
            $storeId = (int)$this->storeManager->getStore()->getId();
            $email = $this->phoneEmailGenerator->generateEmailFromPhone($phoneNumber, $storeId);
            
            // Set the generated email to the customer
            $customer->setEmail($email);
            
            $this->logger->info('PhoneMail: Generated email ' . $email . ' for phone number ' . $phoneNumber);
            
        } catch (\Exception $e) {
            $this->logger->error('PhoneMail: Error in beforeCreateAccount: ' . $e->getMessage());
            // Don't stop the registration process if there's an error
        }
        
        return [$customer, $password, $redirectUrl];
    }

    /**
     * Before save customer plugin
     *
     * @param AccountManagementInterface $subject
     * @param CustomerInterface $customer
     * @param string|null $hash
     * @param string|null $redirectUrl
     * @return array
     */
    public function beforeCreateAccountWithPasswordHash(
        AccountManagementInterface $subject,
        CustomerInterface $customer,
        $hash = null,
        $redirectUrl = null
    ) {
        if (!$this->isModuleEnabled()) {
            return [$customer, $hash, $redirectUrl];
        }
        
        try {
            // Check if email is already set and valid
            $email = $customer->getEmail();
            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL) && 
                strpos($email, 'example.com') === false) {
                // If customer already has a valid email, don't override it
                return [$customer, $hash, $redirectUrl];
            }
            
            // Get phone number from customer custom attribute
            $phoneNumber = null;
            
            // Try to get from custom attributes
            if ($customer->getCustomAttribute('telephone')) {
                $phoneNumber = $customer->getCustomAttribute('telephone')->getValue();
            }
                
            // If no phone attribute, try to get from the address
            if (!$phoneNumber && $customer->getAddresses()) {
                foreach ($customer->getAddresses() as $address) {
                    if ($address->getTelephone()) {
                        $phoneNumber = $address->getTelephone();
                        break;
                    }
                }
            }
            
            if (!$phoneNumber) {
                $this->logger->warning('PhoneMail: Phone number not found for customer registration (hash)');
                return [$customer, $hash, $redirectUrl];
            }
            
            // Generate email based on phone number
            $storeId = (int)$this->storeManager->getStore()->getId();
            $email = $this->phoneEmailGenerator->generateEmailFromPhone($phoneNumber, $storeId);
            
            // Set the generated email to the customer
            $customer->setEmail($email);
            
            $this->logger->info('PhoneMail: Generated email ' . $email . ' for phone number ' . $phoneNumber . ' (hash)');
            
        } catch (\Exception $e) {
            $this->logger->error('PhoneMail: Error in beforeCreateAccountWithPasswordHash: ' . $e->getMessage());
            // Don't stop the registration process if there's an error
        }
        
        return [$customer, $hash, $redirectUrl];
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
 * Find customer by phone number
 *
 * @param string $phoneNumber
 * @return \Magento\Customer\Api\Data\CustomerInterface|null
 */
private function findCustomerByPhone(string $phoneNumber): ?\Magento\Customer\Api\Data\CustomerInterface
{
    try {
        // Clean phone number
        $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Generate email based on phone number
        $storeId = (int)$this->storeManager->getStore()->getId();
        $email = $this->phoneEmailGenerator->generateEmailFromPhone($cleanPhone, $storeId);
        
        // Try to load customer by email
        return $this->customerRepository->get($email);
    } catch (LocalizedException $e) {
        $this->logger->error('PhoneMail: Failed to find customer by phone: ' . $e->getMessage());
        return null;
    }
} 
}