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
use Magento\Customer\Api\CustomerRepositoryInterface;
use MagoArab\PhoneMail\Model\PhoneEmailGenerator;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;

class AccountManagementLogin
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
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;
    
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

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
     * @param CartRepositoryInterface $cartRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param CartManagementInterface $cartManagement
     * @param CustomerRepositoryInterface $customerRepository
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        PhoneEmailGenerator $phoneEmailGenerator,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        CartRepositoryInterface $cartRepository = null,
        QuoteIdMaskFactory $quoteIdMaskFactory = null,
        CartManagementInterface $cartManagement = null,
        CustomerRepositoryInterface $customerRepository = null,
        LoggerInterface $logger = null
    ) {
        $this->phoneEmailGenerator = $phoneEmailGenerator;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->cartRepository = $cartRepository ?: ObjectManager::getInstance()->get(CartRepositoryInterface::class);
        $this->quoteIdMaskFactory = $quoteIdMaskFactory ?: ObjectManager::getInstance()->get(QuoteIdMaskFactory::class);
        $this->cartManagement = $cartManagement ?: ObjectManager::getInstance()->get(CartManagementInterface::class);
        $this->customerRepository = $customerRepository ?: ObjectManager::getInstance()->get(CustomerRepositoryInterface::class);
        $this->logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
    }

    /**
     * Before authenticate plugin
     *
     * @param AccountManagementInterface $subject
     * @param string $username
     * @param string $password
     * @return array
     */
    public function beforeAuthenticate(
        AccountManagementInterface $subject,
        $username,
        $password
    ) {
        if (!$this->isModuleEnabled()) {
            return [$username, $password];
        }
        
        try {
            // Check if the username looks like a phone number
            if (preg_match('/^[0-9+\-() ]+$/', $username)) {
                $this->logger->info('PhoneMail: Phone number detected in login: ' . $username);
                
                // Clean the phone number
                $cleanPhone = preg_replace('/[^0-9]/', '', $username);
                $this->logger->info('PhoneMail: Cleaned phone number: ' . $cleanPhone);
                
                // Generate email from phone with login flag set to true
                $storeId = (int)$this->storeManager->getStore()->getId();
                $email = $this->phoneEmailGenerator->generateEmailFromPhone($cleanPhone, $storeId, true);
                
                $this->logger->info('PhoneMail: Generated email for login: ' . $email);
                
                // Return the generated email instead of phone number
                return [$email, $password];
            }
        } catch (\Exception $e) {
            $this->logger->error('PhoneMail: Error in login process: ' . $e->getMessage());
        }
        
        return [$username, $password];
    }
    
   /**
 * After authenticate plugin
 *
 * @param AccountManagementInterface $subject
 * @param CustomerInterface $result
 * @param string $username
 * @param string $password
 * @return CustomerInterface
 */
public function afterAuthenticate(
    AccountManagementInterface $subject,
    $result,
    $username,
    $password
) {
    if (!$this->isModuleEnabled()) {
        return $result;
    }
    
    try {
        // Log successful login
        $customerId = $result->getId();
        $this->logger->info('PhoneMail: Customer ID ' . $customerId . ' logged in successfully');
        
        // Store phone number in session for checkout
        try {
            // Find customer addresses
            $customer = $this->customerRepository->getById($customerId);
            $phoneNumber = null;
            
            // Check addresses for phone number
            if ($customer->getAddresses()) {
                foreach ($customer->getAddresses() as $address) {
                    if ($address->getTelephone()) {
                        $phoneNumber = $address->getTelephone();
                        break;
                    }
                }
            }
            
            // If found, store in session
            if ($phoneNumber) {
                $objectManager = ObjectManager::getInstance();
                $customerSession = $objectManager->get(\Magento\Customer\Model\Session::class);
                $customerSession->setCustomerTelephone($phoneNumber);
                $this->logger->info('PhoneMail: Stored phone number ' . $phoneNumber . ' in session');
                
                // Also store in checkout session for convenience
                $checkoutSession = $objectManager->get(\Magento\Checkout\Model\Session::class);
                if (method_exists($checkoutSession, 'setCustomerTelephone')) {
                    $checkoutSession->setCustomerTelephone($phoneNumber);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('PhoneMail: Error storing phone in session: ' . $e->getMessage());
        }
        
        // Cart management is now handled by our QuoteRepository plugin
    } catch (\Exception $e) {
        $this->logger->error('PhoneMail: Error in afterAuthenticate: ' . $e->getMessage());
    }
    
    return $result;
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