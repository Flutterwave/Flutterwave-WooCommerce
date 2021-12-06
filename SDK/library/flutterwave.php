<?php

namespace Sdk\Library;

class FlutterwaveSdk {
    protected $publicKey;
    protected $secretKey;
    protected $amount;
    protected $paymentOptions = Null;
    protected $customDescription;
    protected $customLogo;
    protected $customTitle;
    protected $country;
    protected $currency;
    protected $customerEmail;
    protected $customerFirstname;
    protected $customerLastname;
    protected $customerPhone;
    protected $txref;
    protected $integrityHash;
    protected $payButtonText = 'Make Payment';
    protected $redirectUrl;
    protected $meta = array();
    protected $transactionPrefix;
    public $transaction_actual_id;
    public $logger;
    protected $handler;
    protected $liveUrl = 'https://api.flutterwave.com/v3';
    protected $baseUrl;
    protected $tracking_url = 'https://kgelfdz7mf.execute-api.us-east-1.amazonaws.com/staging/sendevent';
    protected $transactionData;
    protected $overrideTransactionReference;
    protected $requeryCount = 0;
    protected $disableBarter;
    protected $context = array( 'source' => 'flutterwave_for_woocommerce' );
    
    function __construct($publicKey, $secretKey, $prefix, $overrideRefWithPrefix = false){
        $this->publicKey = $publicKey;
        $this->secretKey = $secretKey;
        $this->transactionPrefix = $overrideRefWithPrefix ? $prefix : $prefix.'_';
        $this->overrideTransactionReference = $overrideRefWithPrefix;
        $this->createReferenceNumber();
        $this->baseUrl = $this->liveUrl;
        // logs - use wc logs
        $this->logger = wc_get_logger();
        $this->logger->notice('Flutterwave Class Initializes....', $this->context);
        return $this;
    }
    
    /**
     * Generates a checksum value for the information to be sent to the payment gateway
     * @return object
     * */
    function createCheckSum(){
        $this->logger->notice('Generating Checksum....', $this->context);
        $options = array( 
            "PBFPubKey" => $this->publicKey, 
            "amount" => $this->amount, 
            "customer_email" => $this->customerEmail, 
            "customer_firstname" => $this->customerFirstname, 
            "customer_lastname" => $this->customerLastname, 
            "txref" => $this->txref, 
            "payment_options" => $this->paymentOptions, 
            "country" => $this->country, 
            "currency" => $this->currency, 
            "custom_description" => $this->customDescription, 
            "custom_logo" => $this->customLogo, 
            "custom_title" => $this->customTitle, 
            "customer_phone" => $this->customerPhone,
            "pay_button_text" => $this->payButtonText,
            "redirect_url" => $this->redirectUrl,
            "hosted_payment" => 1
        );

        // check if the user disabled barter
        if ($this->getDisableBarter() == 'yes'){
            $options['disable_pwb'] = true;
        }
        
        ksort($options);
        
        $this->transactionData = $options;
        
        $hashedPayload = '';
        
        foreach($options as $key => $value){
            $hashedPayload .= $value;
        }

        $completeHash = $hashedPayload.$this->secretKey;
        $hash = hash('sha256', $completeHash);
        
        $this->integrityHash = $hash;
        return $this;
    }
    
    /**
     * Generates a transaction reference number for the transactions
     * @return object
     * */
    function createReferenceNumber(){
        $logger = wc_get_logger();
        $logger->notice('Generating Reference Number....', $this->context);
        if($this->overrideTransactionReference){
            $this->txref = $this->transactionPrefix;
        }else{
            $this->txref = uniqid($this->transactionPrefix);
        }
        $logger->notice('Generated Reference Number....'.$this->txref, $this->context);
        return $this;
    }
    
    /**
     * gets the current transaction reference number for the transaction
     * @return string
     * */
    function getReferenceNumber(){
        return $this->txref;
    }

    /**
     * Disable barter from the form
     * @param string yes/no
     * @return object
     */
    function setDisableBarter($barter){
        $this->disableBarter = $barter;
        return $this;
    }

    /**
     * gets the disable barter decision
     * @return string
     */
    function getDisableBarter(){
        return $this->disableBarter;
    }
    
    /**
     * Sets the transaction amount
     * @param integer $amount Transaction amount
     * @return object
     * */
    function setAmount($amount){
        $this->amount = $amount;
        return $this;
    }
    
    /**
     * gets the transaction amount
     * @return string
     * */
    function getAmount(){
        return $this;
    }
    
    /**
     * Sets the allowed payment methods
     * @param string $paymentOptions The allowed payment methods. Can be card, account or both 
     * @return object
     * */
    function setPaymentOptions($paymentOptions){
        $this->paymentOptions = $paymentOptions;
        return $this;
    }
    
    /**
     * gets the allowed payment methods
     * @return string
     * */
    function getPaymentOptions(){
        return $this;
    }
    
    /**
     * Sets the transaction description
     * @param string $customDescription The description of the transaction
     * @return object
     * */
    function setDescription($customDescription){
        $this->customDescription = $customDescription;
        return $this;
    }
    
    /**
     * gets the transaction description
     * @return string
     * */
    function getDescription(){
        return $this->customDescription;
    }
    
    /**
     * Sets the payment page logo
     * @param string $customLogo Your Logo
     * @return object
     * */
    function setLogo($customLogo){
        $this->customLogo = $customLogo;
        return $this;
    }
    
    /**
     * gets the payment page logo
     * @return string
     * */
    function getLogo(){
        return $this->customLogo;
    }
    
    /**
     * Sets the payment page title
     * @param string $customTitle A title for the payment. It can be the product name, your business name or anything short and descriptive 
     * @return object
     * */
    function setTitle($customTitle){
        $this->customTitle = $customTitle;
        return $this;
    }
    
    /**
     * gets the payment page title
     * @return string
     * */
    function getTitle(){
        return $this->customTitle;
    }
    
    /**
     * Sets transaction country
     * @param string $country The transaction country. Can be NG, US, KE, GH and ZA
     * @return object
     * */
    function setCountry($country){
        $this->country = $country;
        return $this;
    }
    
    /**
     * gets the transaction country
     * @return string
     * */
    function getCountry(){
        return $this->country;
    }
    
    /**
     * Sets the transaction currency
     * @param string $currency The transaction currency. Can be NGN, GHS, KES, ZAR, USD, EUR and GBP
     * @return object
     * */
    function setCurrency($currency){
        $this->currency = $currency;
        return $this;
    }
    
    /**
     * gets the transaction currency
     * @return string
     * */
    function getCurrency(){
        return $this->currency;
    }
    
    /**
     * Sets the customer email
     * @param string $customerEmail This is the paying customer's email
     * @return object
     * */
    function setEmail($customerEmail){
        $this->customerEmail = $customerEmail;
        return $this;
    }
    
    /**
     * gets the customer email
     * @return string
     * */
    function getEmail(){
        return $this->customerEmail;
    }
    
    /**
     * Sets the customer firstname
     * @param string $customerFirstname This is the paying customer's firstname
     * @return object
     * */
    function setFirstname($customerFirstname){
        $this->customerFirstname = $customerFirstname;
        return $this;
    }
    
    /**
     * gets the customer firstname
     * @return string
     * */
    function getFirstname(){
        return $this->customerFirstname;
    }
    
    /**
     * Sets the customer lastname
     * @param string $customerLastname This is the paying customer's lastname
     * @return object
     * */
    function setLastname($customerLastname){
        $this->customerLastname = $customerLastname;
        return $this;
    }
    
    /**
     * gets the customer lastname
     * @return string
     * */
    function getLastname(){
        return $this->customerLastname;
    }
    
    /**
     * Sets the customer phonenumber
     * @param string $customerPhone This is the paying customer's phonenumber
     * @return object
     * */
    function setPhoneNumber($customerPhone){
        $this->customerPhone = $customerPhone;
        return $this;
    }
    
    /**
     * gets the customer phonenumber
     * @return string
     * */
    function getPhoneNumber(){
        return $this->customerPhone;
    }
    
    /**
     * Sets the payment page button text
     * @param string $payButtonText This is the text that should appear on the payment button on the Rave payment gateway.
     * @return object
     * */
    function setPayButtonText($payButtonText){
        $this->payButtonText = $payButtonText;
        return $this;
    }
    
    /**
     * gets payment page button text
     * @return string
     * */
    function getPayButtonText(){
        return $this->payButtonText;
    }
    
    /**
     * Sets the transaction redirect url
     * @param string $redirectUrl This is where the Rave payment gateway will redirect to after completing a payment
     * @return object
     * */
    function setRedirectUrl($redirectUrl){
        $this->redirectUrl = $redirectUrl;
        return $this;
    }
    
    /**
     * gets the transaction redirect url
     * @return string
     * */
    function getRedirectUrl(){
        return $this->redirectUrl;
    }
    
    /**
     * Sets the transaction meta data. Can be called multiple time to set multiple meta data
     * @param array $meta This are the other information you will like to store with the transaction. It is a key => value array. eg. PNR for airlines, product colour or attributes. Example. array('name' => 'femi')
     * @return object
     * */
    function setMetaData($meta){
        array_push($this->meta, $meta);
        return $this;
    }
    
    /**
     * gets the transaction meta data
     * @return string
     * */
    function getMetaData(){
        return $this->meta;
    }
    
    /**
     * Sets the event hooks for all available triggers
     * @param object $handler This is a class that implements the Event Handler Interface
     * @return object
     * */
    function eventHandler($handler){
        $this->handler = $handler;
        return $this;
    }
    
    /**
     * Requerys a previous transaction from the Rave payment gateway
     * @param string $referenceNumber This should be the reference number of the transaction you want to requery
     * @return object
     * */
    function requeryTransaction($referenceNumber, $referenceId){
        $logger = wc_get_logger();
        $this->txref = $referenceNumber;
        $this->transaction_actual_id = $referenceId;

        $this->requeryCount++;
        $logger->notice('Requerying Transaction....'.$this->txref, $this->context);
        if(isset($this->handler)){
            $this->handler->onRequery($this->txref);
        }

        $data = array(
            'txref' => $this->txref,
            'SECKEY' => $this->secretKey,
        );

        $url = $this->baseUrl."/transactions/$this->transaction_actual_id/verify";

        //$url = "https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/verify";

        $headers = array('Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$this->secretKey);

        // $response = wp_safe_remote_post( $url, array(
        //     'headers' => $headers,
        //     'timeout' => 30,
        //     'body' => wp_json_encode($data)
        // ) );

        $response = wp_safe_remote_get( $url, array(
            'headers' => $headers,
            'timeout' => 30,
        ) );

        if ( is_wp_error( $response ) ) {
            $error_code = array_key_first( $response->errors );
            $error_message = $response->errors[$error_code][0];
            $logger->error( $error_message );
        }else{
            $response  = json_decode(wp_remote_retrieve_body($response), true);
        }  

        // echo "<pre>";
        // print_r($response);
        // echo "</pre>";
        // exit;
        // check the status is success
        if ( $response["status"] === "success") {
 
            if( $response["data"]["status"] && $response["data"]["status"] === "successful"){
                $logger->notice('Requeryed a successful transaction....'.json_encode($response["data"]), $this->context);
                // Handle successful
                if(isset($this->handler)){
                    $this->handler->onSuccessful($response["data"]);
                }
            }elseif($response["data"]["status"] && $response["data"]["status"] === "failed"){
                // Handle Failure
                $logger->warning('Requeryed a failed transaction....'.json_encode($response->body->data), $this->context);
                if(isset($this->handler)){
                    $this->handler->onFailure($response["data"]["status"]);
                }
            }else{
                // Handled an undecisive transaction. Probably timed out.
                $logger->warning('Requeryed an undecisive transaction....'.json_encode($response["data"]["status"]), $this->context);
                // I will requery again here. Just incase we have some devs that cannot setup a queue for requery. I don't like this.
                if($this->requeryCount > 4){
                    // Now you have to setup a queue by force. We couldn't get a status in 5 requeries.
                    if(isset($this->handler)){
                        $this->handler->onTimeout($this->txref, $response["data"]);
                    }
                }else{

                    $logger->notice('delaying next requery for 3 seconds', $this->context);
                    sleep(3);
                    $logger->notice('Now retrying requery...', $this->context);
                    $this->requeryTransaction($this->txref, $this->transaction_actual_id);
                    
                }
            }
        }else{
            $logger->warning('Requery call returned error for transaction reference.....'.json_encode($response["data"]).'Transaction Reference: '. $this->txref, $this->context);
            // Handle Requery Error
            if(isset($this->handler)){
                $this->handler->onRequeryError($response["data"]);
            }
        }
        return $this;
    }
    
    /**
     * Generates the final json to be used in configuring the payment call to the rave payment gateway
     * @return string
     * */
    function initialize(){
        $this->createCheckSum();
        $this->transactionData = array_merge($this->transactionData, array('integrity_hash' => $this->integrityHash), array('meta' => $this->meta));
        
        if(isset($this->handler)){
            $this->handler->onInit($this->transactionData);
        }
        
        $json = json_encode($this->transactionData);
        echo '<html>';
        echo '<body>';
        echo '<center>Proccessing...<br /><img src="'.plugins_url('SDK/ajax-loader.gif', WC_FLUTTERWAVE_PLUGIN_FILE).'" /></center>';
        echo '<script type="text/javascript" src="https://checkout.flutterwave.com/v3.js"></script>';
        echo '<script>';
        echo 'document.addEventListener("DOMContentLoaded", function(event) {';
        echo 'FlutterwaveCheckout({
            public_key: "' . $this->publicKey . '",
            tx_ref: "' . $this->txref . '",
            amount: ' . $this->amount . ',
            currency: "' . $this->currency . '",
            country: "' . $this->country . '",
            payment_options: "card,ussd,mpesa,barter,mobilemoneyghana,mobilemoneyrwanda,mobilemoneyzambia,mobilemoneyuganda,mobilemoneyfranco,banktransfer,account",
            redirect_url:"' . $this->redirectUrl . '",
            customer: {
              email: "' . $this->customerEmail . '",
              phone_number: "' . $this->customerPhone . '",
              name: "' . $this->customerFirstname . ' ' . $this->customerLastname . '",
            },
            callback: function (data) {
              console.log(data);
            },
            onclose: function() {
                window.location = "?cancelled=cancelled";
              },
            customizations: {
              title: "' . $this->customTitle . '",
              description: "' . $this->customDescription . '",
              logo: "' . $this->customLogo . '",
            }
        });';
        echo '});';

        echo '</script>';
        echo '</body>';
        echo '</html>';

        return $json;
    }
    
    /**
     * Handle canceled payments with this method
     * @param string $referenceNumber This should be the reference number of the transaction that was canceled
     * @return object
     * */
    function paymentCanceled($referenceNumber){
        $logger = wc_get_logger();
        $this->txref = $referenceNumber;
        $logger->notice('Payment was canceled by user..'.$this->txref, $this->context);
        if(isset($this->handler)){
            $this->handler->onCancel($this->txref);
        }
        return $this;
    }
    
}
