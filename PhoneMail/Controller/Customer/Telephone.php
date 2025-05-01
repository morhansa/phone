<?php
/**
 * PhoneMail Module
 *
 * @category  PhoneMail
 * @package   PhoneMail\Controller\Customer
 * @author    MagoArab
 * @copyright Copyright (c) 2025
 */
declare(strict_types=1);

namespace MagoArab\PhoneMail\Controller\Customer;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class Telephone implements HttpGetActionInterface
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;
    
    /**
     * @var Session
     */
    private $customerSession;
    
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
     * @param JsonFactory $resultJsonFactory
     * @param Session $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
    }
    
    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        
        try {
            if (!$this->customerSession->isLoggedIn()) {
                return $result->setData(['success' => false, 'message' => 'Customer not logged in']);
            }
            
            $customerId = $this->customerSession->getCustomerId();
            $customer = $this->customerRepository->getById($customerId);
            
            // الحصول على رقم الهاتف من سمات العميل
            $telephone = '';
            $telephoneAttribute = $customer->getCustomAttribute('telephone');
            
            if ($telephoneAttribute) {
                $telephone = $telephoneAttribute->getValue();
            }
            
            $this->logger->info('PhoneMail: Retrieved customer telephone: ' . $telephone);
            
            return $result->setData([
                'success' => true,
                'telephone' => $telephone
            ]);
        } catch (NoSuchEntityException $e) {
            $this->logger->error('PhoneMail: Customer not found: ' . $e->getMessage());
            return $result->setData(['success' => false, 'message' => 'Customer not found']);
        } catch (LocalizedException $e) {
            $this->logger->error('PhoneMail: Error retrieving customer: ' . $e->getMessage());
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            $this->logger->error('PhoneMail: Unexpected error: ' . $e->getMessage());
            return $result->setData(['success' => false, 'message' => 'An error occurred']);
        }
    }
}