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
use MagoArab\PhoneMail\Model\PhoneEmailGenerator;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class GenerateEmail implements ObserverInterface
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
     * @param PhoneEmailGenerator $phoneEmailGenerator
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        PhoneEmailGenerator $phoneEmailGenerator,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->phoneEmailGenerator = $phoneEmailGenerator;
        $this->storeManager = $storeManager;
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
            $customer = $observer->getEvent()->getCustomer();
            if ($customer) {
                // Check if email is already set and not temporary
                if ($customer->getEmail() && !$this->isTemporaryEmail($customer->getEmail())) {
                    return;
                }
                
                // Get phone number from customer
                $phoneNumber = $this->getPhoneNumber($customer);
                if (!$phoneNumber) {
                    return;
                }
                
                // Generate email based on phone number
                $storeId = $this->storeManager->getStore()->getId();
                $email = $this->phoneEmailGenerator->generateEmailFromPhone($phoneNumber, $storeId);
                
                // Set generated email
                $customer->setEmail($email);
                
                $this->logger->info('Generated email for customer: ' . $email);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error generating email from phone: ' . $e->getMessage());
        }
    }

    /**
     * Get phone number from customer
     *
     * @param \Magento\Customer\Model\Data\Customer $customer
     * @return string|null
     */
    private function getPhoneNumber($customer): ?string
    {
        // Check custom attribute
        if ($customer->getCustomAttribute('telephone')) {
            return $customer->getCustomAttribute('telephone')->getValue();
        }
        
        // Check addresses
        if ($customer->getAddresses()) {
            foreach ($customer->getAddresses() as $address) {
                if ($address->getTelephone()) {
                    return $address->getTelephone();
                }
            }
        }
        
        return null;
    }

    /**
     * Check if email is temporary
     *
     * @param string $email
     * @return bool
     */
    private function isTemporaryEmail(string $email): bool
    {
        // Check common patterns for temporary emails
        $tempPatterns = [
            'example.com',
            'temp',
            'fake',
            'temporary'
        ];
        
        foreach ($tempPatterns as $pattern) {
            if (stripos($email, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
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