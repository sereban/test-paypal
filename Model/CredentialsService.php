<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Model;

use Magento\Config\Model\Config\Factory as ConfigFactory;
use Magento\PaypalOnBoarding\Api\CredentialsServiceInterface;
use Magento\PaypalOnBoarding\Api\Data\CredentialsInterface;

/**
 * PayPal credentials service
 */
class CredentialsService implements CredentialsServiceInterface
{
    /**
     * @var ConfigFactory
     */
    private $configFactory;

    /**
     * @param ConfigFactory $configFactory
     */
    public function __construct(ConfigFactory $configFactory)
    {
        $this->configFactory = $configFactory;
    }

    /**
     * @inheritdoc
     *
     * @param CredentialsInterface $credentials
     * @param int|null $websiteId
     * @return bool
     * @throws \Exception
     */
    public function save(CredentialsInterface $credentials, $websiteId)
    {
        $configData = [
            'section' => 'payment',
            'website' => $websiteId,
            'store' => null,
            'groups' => $this->getGroupsForSave($credentials),
        ];
        /** @var \Magento\Config\Model\Config $configModel  */
        $configModel = $this->configFactory->create(['data' => $configData]);
        $configModel->save();

        return true;
    }

    /**
     * Prepare groups data for save
     *
     * @param CredentialsInterface $credentials
     * @return array
     */
    private function getGroupsForSave(CredentialsInterface $credentials)
    {
        return [
            'paypal_alternative_payment_methods' => [
                'groups' => [
                    'express_checkout_us' => [
                        'groups' => [
                            'express_checkout_required' => [
                                'groups' => [
                                    'express_checkout_required_express_checkout' => [
                                        'fields' => [
                                            'api_username' => ['value' => $credentials->getUsername()],
                                            'api_password' => ['value' => $credentials->getPassword()],
                                            'api_signature' => ['value' => $credentials->getSignature()],
                                        ]
                                    ]
                                ],
                                'fields' => [
                                    'merchant_id' => ['value' => $credentials->getMerchantId()],
                                ],
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
