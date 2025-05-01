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

namespace MagoArab\PhoneMail\Controller\Account;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\SecurityViolationException;
use Magento\Customer\Controller\AbstractAccount;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use MagoArab\PhoneMail\Helper\Data as PhoneMailHelper;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ObjectManager;

class ForgotPasswordPost extends AbstractAccount implements HttpPostActionInterface
{
    /**
     * @var AccountManagementInterface
     */
    protected $customerAccountManagement;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var Session
     */
    protected $session;
    
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    
    /**
     * @var PhoneMailHelper
     */
    protected $phoneMailHelper;
    
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param AccountManagementInterface $customerAccountManagement
     * @param Escaper $escaper
     * @param StoreManagerInterface $storeManager
     * @param PhoneMailHelper $phoneMailHelper
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        AccountManagementInterface $customerAccountManagement,
        Escaper $escaper,
        StoreManagerInterface $storeManager,
        PhoneMailHelper $phoneMailHelper,
        LoggerInterface $logger = null
    ) {
        parent::__construct($context);
        $this->session = $customerSession;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->escaper = $escaper;
        $this->storeManager = $storeManager;
        $this->phoneMailHelper = $phoneMailHelper;
        $this->logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $email = null;
        $phone = null;

        try {
            // Check if email is provided directly
            $email = $this->getRequest()->getPost('email');
            
            // If no email but telephone is provided, generate the email
            if (empty($email)) {
                $phone = $this->getRequest()->getPost('telephone');
                
                if (empty($phone)) {
                    throw new LocalizedException(__('Please enter your phone number.'));
                }
                
try {
    $email = $this->phoneMailHelper->generateEmailFromPhone($phone, null, true);
} catch (\Exception $e) {
    $this->logger->error('PhoneMail: Error in ForgotPasswordPost: ' . $e->getMessage());
    throw new LocalizedException(__(
        'We couldn\'t find an account with that phone number. ' .
        'Please make sure you have entered the correct phone number.'
    ));
}
            }
 
 // Process the reset with the email
$this->customerAccountManagement->initiatePasswordReset(
    $email,
    \Magento\Customer\Model\AccountManagement::EMAIL_RESET
);

// Log the email used for password reset for debugging
$this->logger->info('PhoneMail: Password reset requested for email: ' . $email);
            
            // Display success message
            $this->messageManager->addSuccessMessage(
                __('If there is an account associated with %1 you will receive an email with a link to reset your password.',
                    $phone ?: $email
                )
            );
            
            return $resultRedirect->setPath('*/*/');
        } catch (NoSuchEntityException $exception) {
            // Do not reveal whether a user is registered or not
            $this->messageManager->addSuccessMessage(
                __('If there is an account associated with %1 you will receive an email with a link to reset your password.',
                    $phone ?: $email
                )
            );
        } catch (SecurityViolationException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Exception $exception) {
            $this->messageManager->addExceptionMessage(
                $exception,
                __('We\'re unable to send the password reset email.')
            );
        }
        
        return $resultRedirect->setPath('*/*/forgotpassword');
    }
}