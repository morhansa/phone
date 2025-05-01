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

use Magento\Customer\Controller\Account\CreatePost as MagentoCreatePost;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Helper\Address;
use Magento\Framework\UrlFactory;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Customer\Model\Registration;
use Magento\Framework\Escaper;
use Magento\Customer\Model\CustomerExtractor;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Data\Form\FormKey\Validator;
use MagoArab\PhoneMail\Helper\Data as PhoneMailHelper;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class CreatePost extends MagentoCreatePost
{
    /**
     * @var PhoneMailHelper
     */
    protected $phoneMailHelper;
    
    /**
     * @var Validator
     */
    protected $formKeyValidator;
    
    /**
     * @var LoggerInterface
     */
    protected $customLogger;
    
    /**
     * @var AccountRedirect
     */
    protected $accountRedirect;

    /**
     * XML path for module enabled configuration
     */
    const XML_PATH_MODULE_ENABLED = 'phonemail/general/enabled';

    /**
     * CreatePost constructor.
     *
     * @param Context $context
     * @param Session $customerSession
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param AccountManagementInterface $accountManagement
     * @param Address $addressHelper
     * @param UrlFactory $urlFactory
     * @param FormFactory $formFactory
     * @param SubscriberFactory $subscriberFactory
     * @param RegionInterfaceFactory $regionDataFactory
     * @param AddressInterfaceFactory $addressDataFactory
     * @param CustomerInterfaceFactory $customerDataFactory
     * @param CustomerUrl $customerUrl
     * @param Registration $registration
     * @param Escaper $escaper
     * @param CustomerExtractor $customerExtractor
     * @param DataObjectHelper $dataObjectHelper
     * @param AccountRedirect $accountRedirect
     * @param CustomerRepositoryInterface $customerRepository
     * @param Validator $formKeyValidator
     * @param PhoneMailHelper $phoneMailHelper
     * @param ForwardFactory|null $resultForwardFactory
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        AccountManagementInterface $accountManagement,
        Address $addressHelper,
        UrlFactory $urlFactory,
        FormFactory $formFactory,
        SubscriberFactory $subscriberFactory,
        RegionInterfaceFactory $regionDataFactory,
        AddressInterfaceFactory $addressDataFactory,
        CustomerInterfaceFactory $customerDataFactory,
        CustomerUrl $customerUrl,
        Registration $registration,
        Escaper $escaper,
        CustomerExtractor $customerExtractor,
        DataObjectHelper $dataObjectHelper,
        AccountRedirect $accountRedirect,
        CustomerRepositoryInterface $customerRepository,
        Validator $formKeyValidator,
        PhoneMailHelper $phoneMailHelper,
        ForwardFactory $resultForwardFactory = null,
        LoggerInterface $logger = null
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $scopeConfig,
            $storeManager,
            $accountManagement,
            $addressHelper,
            $urlFactory,
            $formFactory,
            $subscriberFactory,
            $regionDataFactory,
            $addressDataFactory,
            $customerDataFactory,
            $customerUrl,
            $registration,
            $escaper,
            $customerExtractor,
            $dataObjectHelper,
            $accountRedirect,
            $customerRepository,
            $formKeyValidator,
            $resultForwardFactory
        );
        
        $this->phoneMailHelper = $phoneMailHelper;
        $this->formKeyValidator = $formKeyValidator;
        $this->customLogger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
        $this->accountRedirect = $accountRedirect;
    }

    /**
     * Get account redirect object
     *
     * @return Redirect
     */
    protected function getAccountRedirect()
    {
        return $this->accountRedirect->getRedirect();
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
     * Create customer account action
     *
     * @return Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        // Check if module is enabled
        if (!$this->isModuleEnabled()) {
            // If not enabled, use parent implementation
            return parent::execute();
        }

        if ($this->session->isLoggedIn() || !$this->registration->isAllowed()) {
            $resultRedirect->setPath('*/*/');
            return $resultRedirect;
        }

        if (!$this->getRequest()->isPost() || !$this->formKeyValidator->validate($this->getRequest())) {
            $resultRedirect->setPath('*/*/create');
            return $resultRedirect;
        }

        try {
            // Get phone number from request
            $telephone = $this->getRequest()->getParam('telephone');
            
            // Log the request parameters for debugging
            $this->customLogger->debug('PhoneMail: Registration request parameters: ' . json_encode($this->getRequest()->getParams()));
            
            if (empty($telephone)) {
                $this->customLogger->error('PhoneMail: Phone number is empty in registration request');
                throw new LocalizedException(__('Phone number is required. Please enter your phone number.'));
            }

            // Check if email is already set in the request
            $email = $this->getRequest()->getParam('email');
            if (empty($email) || strpos($email, 'example.com') !== false) {
                // Generate email and set it in the request
                try {
                    $email = $this->phoneMailHelper->generateEmailFromPhone($telephone);
                    $this->getRequest()->setParam('email', $email);
                    $this->customLogger->info('PhoneMail: Generated email ' . $email . ' for phone ' . $telephone);
                } catch (LocalizedException $e) {
                    // Special handling for existing account
                    if (strpos($e->getMessage(), 'already exists') !== false) {
                        $this->customLogger->info('PhoneMail: Customer with phone ' . $telephone . ' already exists');
                        $this->messageManager->addComplexErrorMessage(
                            'existingAccountMessage',
                            [
                                'url' => $this->urlModel->getUrl('customer/account/login'),
                                'phone' => $telephone
                            ]
                        );
                        
                        $resultRedirect->setPath('customer/account/login');
                        return $resultRedirect;
                    } else {
                        // Handle other exceptions
                        throw $e;
                    }
                } catch (\Exception $e) {
                    $this->customLogger->error('PhoneMail: Error generating email from phone: ' . $e->getMessage());
                    throw new LocalizedException(__('Error generating email address. Please try again later.'));
                }
            }

            // Extract customer data
            $address = $this->extractAddress();
            $addresses = $address === null ? [] : [$address];

            $customer = $this->customerExtractor->extract('customer_account_create', $this->_request);
            $customer->setAddresses($addresses);

            // Create account
            $customer = $this->accountManagement->createAccount($customer, $this->getRequest()->getParam('password'));
            $this->_eventManager->dispatch(
                'customer_register_success',
                ['account_controller' => $this, 'customer' => $customer]
            );

            // Set confirmation/success message based on confirmation status
            $confirmationStatus = $this->accountManagement->getConfirmationStatus($customer->getId());
            if ($confirmationStatus === AccountManagementInterface::ACCOUNT_CONFIRMATION_REQUIRED) {
                $this->messageManager->addComplexSuccessMessage(
                    'confirmAccountSuccessMessage',
                    [
                        'url' => $this->customerUrl->getEmailConfirmationUrl($customer->getEmail()),
                    ]
                );
                $url = $this->urlModel->getUrl('*/*/index', ['_secure' => true]);
                $resultRedirect->setUrl($this->_redirect->success($url));
            } else {
                $this->session->setCustomerDataAsLoggedIn($customer);
                $this->messageManager->addSuccessMessage(
                    __('Thank you for registering with %1.', $this->storeManager->getStore()->getFrontendName())
                );
                $resultRedirect = $this->getAccountRedirect();
            }

            return $resultRedirect;
        } catch (StateException $e) {
            $this->customLogger->error('PhoneMail: StateException: ' . $e->getMessage());
            $this->messageManager->addComplexErrorMessage(
                'customerAlreadyExistsErrorMessage',
                [
                    'url' => $this->urlModel->getUrl('customer/account/forgotpassword'),
                ]
            );
        } catch (InputException $e) {
            $this->customLogger->error('PhoneMail: InputException: ' . $e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
            foreach ($e->getErrors() as $error) {
                $this->messageManager->addErrorMessage($error->getMessage());
            }
        } catch (LocalizedException $e) {
            $this->customLogger->error('PhoneMail: LocalizedException: ' . $e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->customLogger->error('PhoneMail: Exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            $this->messageManager->addExceptionMessage($e, __('We can\'t save the customer.'));
        }

        $this->session->setCustomerFormData($this->getRequest()->getPostValue());
        $defaultUrl = $this->urlModel->getUrl('*/*/create', ['_secure' => true]);
        $resultRedirect->setUrl($this->_redirect->error($defaultUrl));
        return $resultRedirect;
    }
}