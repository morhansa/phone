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

namespace MagoArab\PhoneMail\Block\Adminhtml\System\Config\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Config\Block\System\Config\Form\Field;
use MagoArab\PhoneMail\Helper\Data as PhoneMailHelper;
use Magento\Store\Model\StoreManagerInterface;

class DomainInfo extends Field
{
    /**
     * @var string
     */
    protected $_template = 'MagoArab_PhoneMail::system/config/field/domain_info.phtml';

    /**
     * @var PhoneMailHelper
     */
    private $phoneMailHelper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

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
     * Render element
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $html = $this->_renderValue($element);
        
        return $html;
    }

    /**
     * Get the element HTML
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _renderValue(AbstractElement $element)
    {
        return $this->_toHtml();
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