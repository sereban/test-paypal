<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Controller\Adminhtml\Redirect;

use Magento\Framework\Message\MessageInterface;
use Magento\PaypalOnBoarding\Model\CredentialsService;
use Magento\PaypalOnBoarding\Model\MagentoMerchantId;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Magento\Paypal\Model\Config as PaypalConfig;

/**
 * Contains tests for Success controller with different variations
 */
class SuccessTest extends AbstractBackendController
{
    /**
     * @var string
     */
    private static $entryPoint = 'backend/paypal_onboarding/redirect/success';

    /**
     * @var string
     */
    private static $configPath = 'backend/admin/system_config/edit/section/payment';

    /**
     * @var string
     */
    private static $userName = 'username';

    /**
     * @var string
     */
    private static $userPassword = 'password';

    /**
     * @var string
     */
    private static $signature = 'signature';

    /**
     * @var string
     */
    private static $paypalMerchantId = '43V9GN4SHXNX4';

    /**
     * @var PaypalConfig
     */
    private $config;

    /**
     * @covers \Magento\PaypalOnBoarding\Controller\Adminhtml\Redirect\Success::execute
     * @magentoAppArea adminhtml
     */
    public function testExecuteWithCredentialsSaveFailing()
    {
        $errorMessage = 'DB error';
        $credentialsServiceMock = $this->getMockBuilder(CredentialsService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $credentialsServiceMock->expects(static::once())
            ->method('save')
            ->willThrowException(new \Exception($errorMessage));
        $this->_objectManager->addSharedInstance($credentialsServiceMock, CredentialsService::class);
        /** @var MagentoMerchantId $merchantService */
        $merchantService = $this->_objectManager->get(MagentoMerchantId::class);
        $magentoMerchantId = $merchantService->generate();
        $this->getRequest()->setPostValue('magentoMerchantId', $magentoMerchantId);
        $this->getRequest()->setPostValue('username', self::$userName);
        $this->getRequest()->setPostValue('password', self::$userPassword);
        $this->getRequest()->setPostValue('signature', self::$signature);
        $this->getRequest()->setPostValue('paypalMerchantId', self::$paypalMerchantId);

        $this->dispatch(self::$entryPoint);

        static::assertRedirect(static::stringContains(self::$configPath));
        static::assertSessionMessages(
            static::equalTo(['Something went wrong while saving credentials: ' . $errorMessage]),
            MessageInterface::TYPE_ERROR
        );
        $this->_objectManager->removeSharedInstance(CredentialsService::class);
    }

    /**
     * @covers \Magento\PaypalOnBoarding\Controller\Adminhtml\Redirect\Success::execute
     * @magentoAppArea adminhtml
     */
    public function testExecuteWithFakeWebsiteId()
    {
        $originWebsiteId = 1;
        $fakeWebsiteId = 2;

        /** @var MagentoMerchantId $merchantService */
        $merchantService = $this->_objectManager->get(MagentoMerchantId::class);
        $magentoMerchantId = $merchantService->generate($originWebsiteId);
        $this->getRequest()->setPostValue('magentoMerchantId', $magentoMerchantId);
        $this->getRequest()->setPostValue('username', self::$userName);
        $this->getRequest()->setPostValue('password', self::$userPassword);
        $this->getRequest()->setPostValue('signature', self::$signature);
        $this->getRequest()->setPostValue('paypalMerchantId', self::$paypalMerchantId);
        $this->getRequest()->setPostValue('website', $fakeWebsiteId);

        $this->dispatch(self::$entryPoint);

        static::assertRedirect(static::stringContains(self::$configPath));
        static::assertSessionMessages(
            static::equalTo(['Wrong merchant signature']),
            MessageInterface::TYPE_ERROR
        );
    }

    /**
     * @covers \Magento\PaypalOnBoarding\Controller\Adminhtml\Redirect\Success::execute
     * @param int $website
     * @magentoAppArea adminhtml
     * @dataProvider getWebsiteDataProvider
     */
    public function testExecuteSuccess($website)
    {
        $merchantService = $this->_objectManager->get(MagentoMerchantId::class);
        $magentoMerchantId = $merchantService->generate($website);

        $this->getRequest()->setPostValue('magentoMerchantId', $magentoMerchantId);
        $this->getRequest()->setPostValue('username', self::$userName);
        $this->getRequest()->setPostValue('password', self::$userPassword);
        $this->getRequest()->setPostValue('signature', self::$signature);
        $this->getRequest()->setPostValue('paypalMerchantId', self::$paypalMerchantId);

        $this->dispatch(self::$entryPoint . '/website/' . $website);

        // Assert for saved data
        $this->config = $this->_objectManager->get(PaypalConfig::class);
        $this->config->setMethodCode(PaypalConfig::METHOD_EXPRESS);

        static::assertEquals($this->config->getValue('api_username', $website), self::$userName);
        static::assertEquals($this->config->getValue('api_password', $website), self::$userPassword);
        static::assertEquals($this->config->getValue('api_signature', $website), self::$signature);
        static::assertEquals($this->config->getValue('merchant_id', $website), self::$paypalMerchantId);

        static::assertRedirect(static::stringContains(self::$configPath));

        // Assert for success session message
        static::assertSessionMessages(
            static::equalTo(['You saved PayPal credentials. Please enable PayPal Express Checkout.']),
            MessageInterface::TYPE_SUCCESS
        );
    }

    /**
     * Get variations for controller test
     * @return array
     */
    public function getWebsiteDataProvider()
    {
        return [
            ['website' => 0],
            ['website' => 1]
        ];
    }
}
