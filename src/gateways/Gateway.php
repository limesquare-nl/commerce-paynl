<?php
namespace craft\commerce\paynl\gateways;
use Craft;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\models\Transaction;
use craft\commerce\omnipay\base\OffsiteGateway;
use craft\commerce\models\payments\BasePaymentForm;
use Omnipay\Common\AbstractGateway;
use Omnipay\Omnipay;
use Omnipay\Paynl\Gateway as OmnipayGateway;
use yii\base\NotSupportedException;
/**
 * Gateway represents Paynl gateway
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since     1.0
 */
class Gateway extends OffsiteGateway
{
    // Properties
    // =========================================================================
    /**
     * @var string
     */
    public $apiToken;
    public $serviceId;
    public $testMode;
    
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('commerce', 'Pay.nl');
    }
    /**
     * @inheritdoc
     */
    public function getPaymentTypeOptions(): array
    {
        return [
            'purchase' => Craft::t('commerce', 'Purchase (Authorize and Capture Immediately)')
        ];
    }
    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('commerce-paynl/gatewaySettings', ['gateway' => $this]);
    }
    
    public function populateRequest(array &$request, BasePaymentForm $paymentForm = null)
    {
        parent::populateRequest($request, $paymentForm);
        $request['type'] = 'redirect';
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = ['paymentType', 'compare', 'compareValue' => 'purchase'];
        return $rules;
    }
    
    public function completePurchase(Transaction $transaction): RequestResponseInterface
    {
        $request = Craft::$app->getRequest();
        if (!$this->supportsCompletePurchase()) {
            throw new NotSupportedException(Craft::t('commerce', 'Completing purchase is not supported by this gateway'));
        }
        $request = $this->createRequest($transaction);
        $request['transactionReference'] = $transaction->reference;
        $completeRequest = $this->prepareCompletePurchaseRequest($request);
        
        return $this->performRequest($completeRequest, $transaction);
    }
    // Protected Methods
    // =========================================================================
    /**
     * @inheritdoc
     */
    protected function createGateway(): AbstractGateway
    {
        /** @var OmnipayGateway $gateway */
        $gateway = Omnipay::create($this->getGatewayClassName());
        $gateway->setApitoken($this->apiToken);
        $gateway->setServiceId($this->serviceId);
        $gateway->setTestMode($this->testMode);
        return $gateway;
    }
    /**
     * @inheritdoc
     */
    protected function getGatewayClassName()
    {
        return '\\'.OmnipayGateway::class;
    }
}