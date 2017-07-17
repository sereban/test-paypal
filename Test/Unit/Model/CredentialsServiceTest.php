<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Test\Unit\Model;

use Magento\Config\Model\Config;
use Magento\Config\Model\Config\Factory;
use Magento\PaypalOnBoarding\Model\Credentials;
use Magento\PaypalOnBoarding\Model\CredentialsService;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Contains tests for Credentials service
 */
class CredentialsServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Factory|MockObject
     */
    private $configFactory;

    /**
     * @var Config|MockObject
     */
    private $config;

    /**
     * @var CredentialsService
     */
    private $credentialsService;

    protected function setUp()
    {
        $this->configFactory = $this->getMockBuilder(Factory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->setMethods(['save'])
            ->getMock();

        $this->credentialsService = new CredentialsService($this->configFactory);
    }

    /**
     * @covers \Magento\PaypalOnBoarding\Model\CredentialsService::save
     */
    public function testSave()
    {
        $username = 'merchant';
        $password = 'querty123';
        $signature = 'e1yu0djs4j2ls';
        $merchantId = '43V9GN4SHXNX4';
        $websiteId = 1;

        $data = [
            'section' => 'payment',
            'website' => $websiteId,
            'store' => null,
            'groups' => [
                'paypal_alternative_payment_methods' => [
                    'groups' => [
                        'express_checkout_us' => [
                            'groups' => [
                                'express_checkout_required' => [
                                    'groups' => [
                                        'express_checkout_required_express_checkout' => [
                                            'fields' => [
                                                'api_username' => ['value' => $username],
                                                'api_password' => ['value' => $password],
                                                'api_signature' => ['value' => $signature],
                                            ]
                                        ]
                                    ],
                                    'fields' => [
                                        'merchant_id' => ['value' => $merchantId],
                                    ],
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $this->configFactory->expects(static::once())
            ->method('create')
            ->with(['data' => $data])
            ->willReturn($this->config);

        $this->config->expects(static::once())
            ->method('save')
            ->willReturnSelf();

        $credentials = new Credentials([
            'username' => $username,
            'password' => $password,
            'signature' => $signature,
            'merchant_id' => $merchantId
        ]);

        $result = $this->credentialsService->save($credentials, $websiteId);

        static::assertTrue($result);
    }

    /**
     * @covers \Magento\PaypalOnBoarding\Model\CredentialsService::save
     * @expectedException \Exception
     * @expectedExceptionMessage Save config exception
     */
    public function testSaveWithException()
    {
        $username = 'merchant';
        $password = 'querty123';
        $signature = 'e1yu0djs4j2ls';
        $merchantId = '43V9GN4SHXNX4';
        $websiteId = 1;

        $this->configFactory->expects(static::once())
            ->method('create')
            ->willReturn($this->config);

        $this->config->expects(static::once())
            ->method('save')
            ->willThrowException(new \Exception('Save config exception'));

        $credentials = new Credentials([
            'username' => $username,
            'password' => $password,
            'signature' => $signature,
            'merchant_id' => $merchantId
        ]);

        $this->credentialsService->save($credentials, $websiteId);
    }
}
