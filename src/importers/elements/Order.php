<?php

namespace barrelstrength\sproutimport\importers\elements;

use barrelstrength\sproutbase\app\import\base\ElementImporter;
use barrelstrength\sproutimport\SproutImport;
use craft\commerce\base\Gateway;
use craft\commerce\elements\Order as OrderElement;
use Craft;
use craft\commerce\errors\PaymentException;
use craft\commerce\models\Address;
use craft\commerce\models\Customer;
use craft\commerce\Plugin;
use craft\commerce\records\Purchasable;
use craft\commerce\records\Transaction;

class Order extends ElementImporter
{
    /** @var string Session key for storing the cart number */
    protected $cookieCartId = 'commerce_cookie';

    /**
     * @return string
     */
    public function getName()
    {
        return Craft::t('sprout-import', 'Commerce Orders');
    }

    /**
     * @return mixed
     */
    public function getModelName()
    {
        return OrderElement::class;
    }

    /**
     * @param       $model
     * @param array $settings
     *
     * @return bool|mixed|void
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     */
    public function setModel($model, array $settings = [])
    {
        $number = $settings['attributes']['number'] ?? null;
        if ($number) {
            $orderCart = Plugin::getInstance()->getOrders()->getOrderByNumber($number);
            if ($orderCart) {
                $this->model = $orderCart;
            }
        }

        if ($this->model->id == null) {
            // Forget cart to create new order
            Plugin::getInstance()->getCarts()->forgetCart();
            $this->model = Plugin::getInstance()->getCarts()->getCart();
        }

        $this->model->setAttributes($settings['attributes'], false);

        $customerEmail = $settings['attributes']['customerId'] ?? null;

        if (is_string($customerEmail)) {
            $user = Craft::$app->users->getUserByUsernameOrEmail($customerEmail);

            if ($user) {
                $customer = Plugin::getInstance()->getCustomers()->getCustomerByUserId((int)$user->id);

                if ($customer) {
                    $this->model->customerId = $customer->id;
                } else {
                    $customer = new Customer();

                    if ($user) {
                        $customer->userId = $user->id;
                    }
                }
            }
        } else {
            $customer = Plugin::getInstance()->getCustomers()->getCustomerById($customerEmail);
        }

        Plugin::getInstance()->getCustomers()->saveCustomer($customer);
        $address = $settings['addresses']['billingAddress'] ?? null;
        if ($address) {
            $billingAddress = new Address();

            $billingAddress->firstName = $address['firstName'];
            $billingAddress->lastName = $address['lastName'];
            $countryCode = $address['countryCode'];

            $countryObj = Plugin::getInstance()->getCountries()->getCountryByIso($countryCode);
            $billingAddress->countryId = $countryObj ? $countryObj->id : null;

            if ($billingAddress->countryId) {
                $stateObj = Plugin::getInstance()
                    ->getStates()
                    ->getStateByAbbreviation($billingAddress->countryId, $address['state']);

                $stateId = $stateObj ? $stateObj->id : null;

                if ($stateId) {
                    $billingAddress->stateId = $stateId;
                } else {
                    $billingAddress->stateName = $address['state'];
                }

                $billingAddress->address1 = $address['address1'] ?? null;
                $billingAddress->city = $address['city'] ?? null;
            }

            $billingAddress->zipCode = $address['zipCode'] ?? null;

            Plugin::getInstance()->getCustomers()->saveAddress($billingAddress, $customer);

            $customer->primaryBillingAddressId = $billingAddress->id;
            $customer->primaryShippingAddressId = $billingAddress->id;

            Plugin::getInstance()->getCustomers()->saveCustomer($customer);
        }

        if ($customer->id) {
            $this->model->customerId = $customer->id;
        }

        if ($this->model->customer) {
            $billingAddress = $this->model->customer->getPrimaryBillingAddress();

            $shippingAddress = $this->model->customer->getPrimaryShippingAddress();
            $this->model->setBillingAddress($billingAddress);
            $this->model->setShippingAddress($shippingAddress);
        }

        if ($settings['lineItems']) {

            // Remove line item if it exist to avoid appending of line item values
            Plugin::getInstance()->getLineItems()->deleteAllLineItemsByOrderId($this->model->id);

            foreach ($settings['lineItems'] as $item) {

                $purchasableId = $item['purchasableId'] ?? null;

                $sku = $item['sku'] ?? null;

                if ($sku) {
                    $purchasable = Purchasable::find()->where(['sku' => $sku])->one();

                    if ($purchasable) {
                        $purchasableId = $purchasable->id;
                    }
                }

                $lineItem = Plugin::getInstance()->getLineItems()
                    ->resolveLineItem($this->model->id, $purchasableId, $item['options'], $item['qty'], '');

                $this->model->addLineItem($lineItem);
            }
        }

        $this->model->isCompleted = $settings['attributes']['isCompleted'] ?? 1;

        $orderStatusId = $settings['attributes']['orderStatusId'] ?? null;

        if (!$orderStatusId) {
            $orderStatus = Plugin::getInstance()->getOrderStatuses()->getDefaultOrderStatusForOrder($this->model);
            $orderStatusId = $orderStatus->id;
        } elseif (is_string($orderStatusId)) {
            $orderStatus = Plugin::getInstance()->getOrderStatuses()->getOrderStatusByHandle($orderStatusId);

            $orderStatusId = $orderStatus->id ?? null;
        }

        $this->model->orderStatusId = $orderStatusId;

        $this->model->paymentCurrency = Plugin::getInstance()->getPaymentCurrencies()->getPrimaryPaymentCurrencyIso();
    }

    /**
     * @return bool|void
     * @throws \Throwable
     */
    public function save()
    {
        parent::save();

        $utilities = SproutImport::$app->utilities;

        $settings = $this->rows;
        $order = $this->model;

        if ($settings['payments']) {
            $gateway = $order->getGateway();

            if (!$gateway) {
                $error = Craft::t('sprout-import', 'There is no gateway selected for this order.');
                $utilities->addError('invalid-gateway', $error);
            }
            /**
             * @var $gateway Gateway
             */
            // Get the gateway's payment form
            $paymentForm = $gateway->getPaymentFormModel();

            $paymentForm->setAttributes($settings['payments'], false);

            $redirect = '';
            $transaction = null;
            if (!$paymentForm->hasErrors() && !$order->hasErrors()) {
                try {
                    Plugin::getInstance()->getPayments()->processPayment($order, $paymentForm, $redirect, $transaction);

                    if (!empty($settings['transactions'])) {
                        if ($transaction) {
                            $transactionRecord = Transaction::findOne($transaction->id);

                            if ($status = $settings['transactions']['status']) {
                                $transactionRecord->status = $status;
                            }

                            if ($reference = $settings['transactions']['reference']) {
                                $transactionRecord->reference = $reference;
                            }

                            if ($response = $settings['transactions']['response']) {
                                $transactionRecord->response = $response;
                            }

                            $transactionRecord->save();
                        }
                    }
                } catch (PaymentException $exception) {
                    $utilities->addError('invalid-payment', $exception->getMessage());
                }
            } else {
                $customError = Craft::t('sprout-import', 'Invalid payment or order. Please review.');
                $utilities->addError('invalid-payment', $customError);
            }
        }
    }

    /**
     * @param $id
     *
     * @return bool
     * @throws \Throwable
     */
    public function deleteById($id)
    {
        return Craft::$app->elements->deleteElementById($id);
    }

    public function getImporterDataKeys()
    {
        return ['lineItems', 'payments', 'transactions', 'addresses'];
    }

    /**
     * This is code is from Commerce_CartService copied it because it is private
     *
     * @return mixed|string
     */
    private function getRandomCartNumber()
    {
        $number = md5(uniqid(mt_rand(), true));

        $order = Plugin::getInstance()->getOrders()->getOrderByNumber($number);
        // Make sure not duplicate number
        if ($order) {
            return $this->getRandomCartNumber();
        }

        return $number;
    }

    public function getFieldLayoutId($model)
    {
        // TODO: Implement getFieldLayoutId() method.
    }
}