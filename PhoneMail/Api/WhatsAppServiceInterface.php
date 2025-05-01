<?php
/**
 * PhoneMail Module
 *
 * @category  PhoneMail
 * @package   PhoneMail\Api
 * @author    MagoArab
 * @copyright Copyright (c) 2025
 */
declare(strict_types=1);

namespace MagoArab\PhoneMail\Api;

use Magento\Sales\Api\Data\OrderInterface;

/**
 * Interface for WhatsApp integration service
 */
interface WhatsAppServiceInterface
{
    /**
     * Send WhatsApp notification for order confirmation
     *
     * @param OrderInterface $order
     * @return bool
     */
    public function sendOrderConfirmation(OrderInterface $order): bool;

    /**
     * Send WhatsApp notification for shipping update
     *
     * @param OrderInterface $order
     * @param string $trackingNumber
     * @param string $carrierName
     * @return bool
     */
    public function sendShippingUpdate(OrderInterface $order, string $trackingNumber, string $carrierName): bool;
}