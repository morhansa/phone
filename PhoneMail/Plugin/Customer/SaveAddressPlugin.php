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

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Psr\Log\LoggerInterface;

class SaveAddressPlugin
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * Constructor
     *
     * @param CustomerRepositoryInterface $customerRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
    }
    
    /**
     * Before save address
     *
     * @param AddressRepositoryInterface $subject
     * @param AddressInterface $address
     * @return array
     */
    public function beforeSave(
        AddressRepositoryInterface $subject,
        AddressInterface $address
    ) {
        try {
            // If address doesn't have telephone but customer does
            if ((!$address->getTelephone() || empty($address->getTelephone())) && $address->getCustomerId()) {
                $customer = $this->customerRepository->getById($address->getCustomerId());
                
                // Get customer telephone attribute
                $telephone = $customer->getCustomAttribute('telephone');
                
                if ($telephone && $telephone->getValue()) {
                    // Set telephone in address
                    $address->setTelephone($telephone->getValue());
                    $this->logger->info('PhoneMail: Added customer phone to address: ' . $telephone->getValue());
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('PhoneMail: Error copying phone to address: ' . $e->getMessage());
        }
        
        return [$address];
    }
}