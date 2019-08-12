<?php

/**
 *  Zarinpal for the PHP Fat-Free Framework
 *
 *  The contents of this file are subject to the terms of the GNU General
 *  Public License Version 3.0. You may not use this file except in
 *  compliance with the license. Any of the license terms and conditions
 *  can be waived if you get permission from the copyright holder.
 *
 *  Copyright (c) 2019 ~ NimaH79
 *  Nima HeydariNasab <nima.heydari79@yahoo.com>
 *
 *  @version: 1.0
 *  @date: 2019/07/20
 *
 */

class Zarinpal
{

    private $merchant_id;

    protected $amount = null;
    protected $description = null;
    protected $email = null;
    protected $mobile = null;
    protected $callback_url = null;
    protected $additional_data = [];

    private $request_result = null;
    private $is_zaringate = false;
    private $is_sandbox = false;

    const REST_API_URL = 'https://www.zarinpal.com/pg/rest/WebGate/%s.json';
    const BASE_REDIRECT_URL = 'https://www.zarinpal.com/pg/StartPay/';

    const SANDBOX_REST_API_URL = 'https://sandbox.zarinpal.com/pg/rest/WebGate/%s.json';
    const SANDBOX_BASE_REDIRECT_URL = 'https://sandbox.zarinpal.com/pg/StartPay/';

    const METHOD_REQUEST = 'PaymentRequest';
    const METHOD_REQUEST_WITH_EXTRA = 'PaymentRequestWithExtra';
    const METHOD_VERIFICATION = 'PaymentVerification';
    const METHOD_REFRESH_AUTHORITY = 'RefreshAuthority';
    const METHOD_UNVERIFIED_TRANSACTION = 'UnverifiedTransactions';

    public function __construct()
    {
        $this->merchant_id = \Base::instance()->get('ZARINPAL.merchant_id');
    }

    /**
     * Sets amount for payment
     *
     * @param  integer $amount in Iranian toman
     * @return void
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * Sets description for payment
     *
     * @param  string $description
     * @return void
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Sets user email for payment
     *
     * @param  string $email
     * @return void
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Sets user phone number for payment
     *
     * @param string $mobile
     * @return void
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;
    }

    /**
     * Sets payment callback URL (for redirection after payment)
     *
     * @param string $url
     * @return void
     */
    public function setCallbackURL($url)
    {
        $this->callback_url = $url;
    }

    /**
     * Enables Zaringate service
     * Requires necessary services from Zarinpal
     *
     * @return void
     */
    public function enableZaringate()
    {
        $this->is_zaringate = true;
    }

    /**
     * Enables sandbox (test mode)
     *
     * @return void
     */
    public function enableSandbox()
    {
        $this->is_sandbox = true;
    }

    /**
     * Adds a shared pay item (shared pay off)
     * Requires necessary services from Zarinpal
     *
     * @param string $account
     * @param int $amount
     * @param string $description
     * @return void
     */
    public function addSharedPay($account, $amount, $description)
    {
        $this->additional_data['Wages'][$account] = [
            'Amount' => $amount,
            'Description' => $description,
        ];
    }

    /**
     * Sets authority expiration time [1800, 3888000]
     *
     * @param int $seconds
     * @return void
     */
    public function expireIn($seconds)
    {
        $this->additional_data['expireIn'] = $seconds;
    }

    /**
     * Gets redirect URL (payment gateway URL)
     *
     * @return string
     */
    public function getRedirectURL()
    {
        $zarinGate = ($this->is_zaringate && !$this->is_sandbox) ? '/zaringate' : '/';
        if ($this->request_result->ok) {
            // Return sandbox redirect URL (if enabled)
            if ($this->is_sandbox) {
                return self::SANDBOX_BASE_REDIRECT_URL . $this->getAuthority() . $zarinGate;
            }
            // Return production redirect URL
            return self::BASE_REDIRECT_URL . $this->getAuthority() . $zarinGate;
        } else {
            return false;
        }
    }

    /**
     * Gets the authority
     *
     * @return string|int
     */
    public function getAuthority()
    {
        if (!$this->request_result->ok) {
            return false;
        }
        return $this->request_result->body->Authority;
    }

    /**
     * Redirects user to payment URL
     *
     * @return void
     */
    public function redirect()
    {
        header('Location: ' . $this->getRedirectURL());
    }

    /**
     * Sends request to get authority
     *
     * @return Response
     */
    public function request()
    {
        $method = empty($this->additional_data) ? self::METHOD_REQUEST : self::METHOD_REQUEST_WITH_EXTRA;
        $this->request_result = $this->post(
            $method,
            [
                'MerchantID' => $this->merchant_id,
                'Amount' => $this->amount,
                'Description' => $this->description,
                'Email' => $this->email,
                'Mobile' => $this->mobile,
                'CallbackURL' => $this->callback_url,
                'AdditionalData' => $this->additional_data
            ],
            $this->is_sandbox
        );
        return $this->request_result;
    }

    /**
     * Verifies a transaction
     *
     * @param string $authority
     * @return Response
     */
    public function verify()
    {
        $authority = \Base::instance()->exists('GET.Authority') ? \Base::instance()->get('GET.Authority') : 'xxx';
        $this->request_result = $this->post(
            self::METHOD_VERIFICATION,
            [
                'MerchantID' => $this->merchant_id,
                'Amount' => $this->amount,
                'Authority' => $authority,
            ],
            $this->is_sandbox
        );
        return $this->request_result;
    }

    /**
     * Refreshes an authority for custom time
     *
     * @param string $authority
     * @param int $expireIn In seconds
     * @return Response
     */
    public function refreshAuthority($authority, $expireIn)
    {
        $this->request_result = $this->post(
            self::METHOD_REFRESH_AUTHORITY,
            [
                'MerchantID' => $this->merchant_id,
                'Authority' => $authority,
                'ExpireIn' => $expireIn,
            ],
            $this->is_sandbox
        );
        return $this->request_result;
    }

    /**
     * Get a list of unverified (failed) transactions
     *
     * @return Response
     */
    public function getUnverified()
    {
        $this->request_result = $this->post(
            self::METHOD_UNVERIFIED_TRANSACTION,
            ['MerchantID' => $this->merchant_id],
            false
        );
        return $this->request_result;
    }

    /**
     * Sends a POST request via cURL
     *
     * @param string $method
     * @param array $parameters
     * @param bool $is_sandbox (optional)
     * @return Response
     */
    public function post($method, array $parameters = [], $is_sandbox = false)
    {
        $parameters_json = json_encode($parameters);

        return new Response(json_decode(\Web::instance()->request($is_sandbox ? sprintf(self::SANDBOX_REST_API_URL, $method) : sprintf(self::REST_API_URL, $method), [
            'method' => 'POST',
            'content' => $parameters_json,
            'header' => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($parameters_json)
            ]
        ])['body']));
    }

}

class Response
{

    public $ok;
    public $message;
    public $status;
    public $body;

    const ERROR_CODES = [
        '-1' => 'Information submitted is incomplete',
        '-2' => 'Merchant ID or Acceptor IP is not correct',
        '-3' => 'Amount should be above 100 Toman',
        '-4' => 'Approved level of Acceptor is Lower than the silver',
        '-11' => 'Request Not found',
        '-12' => 'Request is not editable',
        '-21' => 'Financial operations for this transaction was not found',
        '-22' => 'Transaction is unsuccessful',
        '-33' => 'Transaction amount does not match the paid amount',
        '-34' => 'Limit the number of transactions or number has crossed the divide',
        '-40' => 'There is no access to the method',
        '-41' => 'Additional Data related to information submitted is invalid',
        '-42' => 'The life span length of the payment ID must be between 30 minutes and 45 days',
        '-54' => 'Request archived',
        '-998' => 'Connection Error: Can\'t connect to API (WebService returns null)',
        '-999' => 'Local Library Error',
        '100' => 'Operation was successful',
        '101' => 'Operation was successful but PaymentVerification operation on this transaction have already been done.',
    ];

    public function __construct($body)
    {
        if ($body == null) {
            $this->ok = false;
            $this->status = '-998';
            $this->message = $this->getMessage($this->status);
            return null;
        }
        $this->body = $body;
        $this->ok = ($this->hasError() ? false : true);
        $this->message = $this->getMessage();
        $this->status = $this->getStatus();
    }

    /**
     * Check is there any error in returned result
     *
     * @return boolean
     */
    private function hasError()
    {
        return (isset($this->body->Status) && $this->body->Status == 100) ? false : true;
    }

    /**
     * Get returned status (API Result Status Code)
     *
     * @return int
     */
    private function getStatus()
    {
        return $this->body->Status;
    }

    /**
     * Get and Translate API Message
     *
     * @param string $status optional status code for local errors
     *
     * @return string
     */
    private function getMessage($status = null)
    {
        if ($status != null) {
            return self::ERROR_CODES[$status];
        }

        // Return error message that sent from Zarinpal api as $message
        if (isset($this->body->errors)) {
            $error = (array)$this->body->errors;
            return reset($error)[0];
        }

        // If there was't any message from Zarinpal, use local status translator
        return self::ERROR_CODES[$this->body->Status];
    }
}
