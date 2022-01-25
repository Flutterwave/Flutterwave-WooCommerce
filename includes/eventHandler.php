<?php
require_once (WC_FLUTTERWAVE_DIR_PATH. "SDK/library/flutterwaveEventTracker.php");

interface EventHandlerInterface{
    /**
     * This is called when the a transaction is initialized
     * @param object $initializationData This is the initial transaction data as passed
     * */
    function onInit($initializationData);

    /**
     * This is called only when a transaction is successful
     * @param object $transactionData This is the transaction data as returned from the Flutterwave payment gateway
     * */
    function onSuccessful($transactionData);

    /**
     * This is called only when a transaction failed
     * @param object $transactionData This is the transaction data as returned from the Flutterwave payment gateway
     * */
    function onFailure($transactionData);

    /**
     * This is called when a transaction is requeryed from the payment gateway
     * @param string $transactionReference This is the transaction reference as returned from the Flutterwave payment gateway
     * */
    function onRequery($transactionReference);

    /**
     * This is called a transaction requery returns with an error
     * @param string $requeryResponse This is the error response gotten from the Flutterwave payment gateway requery call
     * */
    function onRequeryError($requeryResponse);

    /**
     * This is called when a transaction is canceled by the user
     * @param string $transactionReference This is the transaction reference as returned from the Flutterwave payment gateway
     * */
    function onCancel($transactionReference);

    /**
     * This is called when a transaction doesn't return with a success or a failure response.
     * @param string $transactionReference This is the transaction reference as returned from the Flutterwave payment gateway
     * @data object $data This is the data returned from the requery call.
     * */
    function onTimeout($transactionReference,$data);
}

      // This is where you set how you want to handle the transaction at different stages
      class myEventHandler implements EventHandlerInterface{
          use FLW_SDK\FlutterwaveEventTracker;

          private $order;

          function __construct($order){
            $this->order = $order;
            $payment_method = 'flutterwave';
            $this->settings = get_option('woocommerce_' . $payment_method . '_settings');
          }
          /**
           * This is called when the Flutterwave class is initialized
           * */
          function onInit($initializationData){
            // Save the transaction to your DB.
            self::setPublicKey($this->settings['public_key']);
            $this->order->add_order_note('Payment initialized via Flutterwave');
            update_post_meta( $this->order->get_id(), '_flw_payment_txn_ref', $initializationData['tx_ref'] );
            $this->order->add_order_note('Your transaction reference: '.$initializationData['tx_ref']);
          }

          /**
           * This is called only when a transaction is successful
           * */
          function onSuccessful($transactionData){

            // Get the transaction from your DB using the transaction reference (txref)
            // Check if you have previously given value for the transaction. If you have, redirect to your successpage else, continue
              // echo "<pre>";
              // print_r($transactionData);
              // echo "</pre>";
              // exit;
              if($transactionData['status'] === 'successful'){

                  if($transactionData['currency'] == $this->order->get_currency() && $transactionData['amount'] == $this->order->get_total()){

                      switch ($transactionData['authmodel']) {
                        case 'mobilemoney':
                            self::sendAnalytics("Initiate-Momo-transfer");
                            break;
                        case 'USSD':
                            self::sendAnalytics("Initiate-USSD-transfer");//ussd
                            break;
                        default:
                            self::sendAnalytics("Initiate-Card-Payments");//card
                            break;
                      }
                      $this->order->payment_complete( $this->order->get_id() );
                      $payment_method = $this->order->get_payment_method();

		              $flw_settings = get_option('woocommerce_' . $payment_method . '_settings');

		              if ( isset( $flw_settings['autocomplete_order'] ) && 'yes' === $flw_settings['autocomplete_order'] ) {
                            $this->order->update_status( 'completed' );
		              }
                      $this->order->add_order_note('Payment was successful on Flutterwave');
                      $this->order->add_order_note('Flutterwave transaction reference: '.$transactionData['flw_ref']);

                        $customer_note  = 'Thank you for your order.<br>';
                        $customer_note .= 'Your payment was successful, we are now <strong>processing</strong> your order.';

                        $this->order->add_order_note( $customer_note, 1 );

                        wc_add_notice( $customer_note, 'notice' );
                  }else{
                      $this->order->update_status( 'on-hold' );
                        $customer_note  = 'Thank you for your order.<br>';
                        $customer_note .= 'Your payment successfully went through, but we have to put your order <strong>on-hold</strong> ';
                        $customer_note .= 'because the we couldn\t verify your order. Please, contact us for information regarding this order.';
                        $admin_note     = 'Attention: New order has been placed on hold because of incorrect payment amount or currency. Please, look into it. <br>';
                        $admin_note    .= 'Amount paid: '. $transactionData['currency'].' '. $transactionData['amount'].' <br> Order amount: '.$this->order->get_currency().' '. $this->order->get_total().' <br> Reference: '.$transactionData['tx_ref'];

                        $this->order->add_order_note( $customer_note, 1 );
                        $this->order->add_order_note( $admin_note );

                        wc_add_notice( $customer_note, 'notice' );
                  }

                  //get order_id from the txref
                  $getOrderId = explode('_', $transactionData['tx_ref']);
                  $order_id = $getOrderId[1];

                  WC()->cart->empty_cart();

              }else{

                  $this->onFailure($transactionData);

              }

          }

          /**
           * This is called only when a transaction failed
           * */
          function onFailure($transactionData){
              // Get the transaction from your DB using the transaction reference (txref)
              // Update the db transaction record (includeing parameters that didn't exist before the transaction is completed. for audit purpose)
              // You can also redirect to your failure page from here
              switch ($transactionData['authmodel']) {
                case 'mobilemoney':
                    self::sendAnalytics("Initiate-Momo-Payment-error");;
                    break;
                case 'USSD':
                    self::sendAnalytics("Initiate-USSD-Payment-error");;//ussd
                    break;
                default:
                    self::sendAnalytics("Initiate-Card-Payment-error");
                    break;
              }
              self::sendAnalytics("Initiate-Ach-Payment-error");
              $this->order->update_status( 'Failed' );
              $this->order->add_order_note('The payment failed on Flutterwave');
              $customer_note  = 'Your payment <strong>failed</strong>. ';
                $customer_note .= 'Please, try funding your account.';

                $this->order->add_order_note( $customer_note, 1 );

                wc_add_notice( $customer_note, 'notice' );
          }

          /**
           * This is called when a transaction is requeryed from the payment gateway
           * */
          function onRequery($transactionReference){
              // Do something, anything!
              $this->order->add_order_note('Confirming payment on Flutterwave');
          }

          /**
           * This is called a transaction requery returns with an error
           * */
          function onRequeryError($requeryResponse){
              // Do something, anything!
              $this->order->add_order_note('An error occured while confirming payment on Flutterwave');
              $this->order->update_status( 'on-hold' );
                $customer_note  = 'Thank you for your order.<br>';
                $customer_note .= 'We had an issue confirming your payment, but we have put your order <strong>on-hold</strong>. ';
                $customer_note .= 'Please, contact us for information regarding this order.';
                $admin_note     = 'Attention: New order has been placed on hold because we could not confirm the payment. Please, look into it. <br>';
                $admin_note    .= 'Payment Responce: '.json_encode($requeryResponse);

                $this->order->add_order_note( $customer_note, 1 );
                $this->order->add_order_note( $admin_note );

                wc_add_notice( $customer_note, 'notice' );
          }

          /**
           * This is called when a transaction is canceled by the user
           * */
          function onCancel($transactionReference){
              // Do something, anything!
              // Note: Somethings a payment can be successful, before a user clicks the cancel button so proceed with caution
              $this->order->add_order_note('The customer clicked on the cancel button on Flutterwave');
              $this->order->update_status( 'Cancelled' );
              $admin_note     = 'Attention: Customer clicked on the cancel button on the payment gateway. We have updated the order to canceled. <br>';
                $admin_note    .= 'Please, confirm from the order notes that there is no note of a successful transaction. If there is, this means that the user was debited and you either have to give value for the transaction or refund the customer.';
                $this->order->add_order_note( $admin_note );
          }

          /**
           * This is called when a transaction doesn't return with a success or a failure response. This can be a timedout transaction on the Flutterwave server or an abandoned transaction by the customer.
           * */
          function onTimeout($transactionReference, $data){
              // Get the transaction from your DB using the transaction reference (txref)
              // Queue it for requery. Preferably using a queue system. The requery should be about 15 minutes after.
              // Ask the customer to contact your support and you should escalate this issue to the flutterwave support team. Send this as an email and as a notification on the page. just incase the page timesout or disconnects
              $this->order->add_order_note('The payment didn\'t return a valid response. It could have timed out or abandoned by the customer on Flutterwave');
              $this->order->update_status( 'on-hold' );
                $customer_note  = 'Thank you for your order.<br>';
                $customer_note .= 'We had an issue confirming your payment, but we have put your order <strong>on-hold</strong>. ';
                $customer_note .= 'Please, contact us for information regarding this order.';
                $admin_note     = 'Attention: New order has been placed on hold because we could not get a definite response from the payment gateway. Kindly contact the Flutterwave support team at hi@flutterwave.com to confirm the payment. <br>';
                $admin_note    .= 'Payment Reference: '.$transactionReference;

                $this->order->add_order_note( $customer_note, 1 );
                $this->order->add_order_note( $admin_note );

                wc_add_notice( $customer_note, 'notice' );
          }
      }

?>