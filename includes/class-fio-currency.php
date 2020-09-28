<?php

class WC_Fio_Currency {

	private static $instance;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/*
	 * @returns
	 * */
	public static function get_fio_amount($amount, $currency = "EUR"){
        $currency = strtoupper($currency);

        $response = wp_remote_get('https://min-api.cryptocompare.com/data/price?fsym=' . $currency . '&tsyms=FIO');
        if ( !$response ) {
            return self::error("No reponse from currency server");
        }
        //standarise the response
        $response = rest_ensure_response($response);
        //Check for valid response
        if ( $response->status !== 200 ) {
            self::error("Not 200 response");
        }
        //Check for body element
        if ( empty($response->data['body']) ) {
            self::error("Response body empty");
        }
        //Decode the json string
        $data = json_decode($response->data['body'], true);
        if(empty($data) && ! is_array($data)){
            self::error("Reponse empty or not array");
        }
        //Do the calculation
        if(!$data["FIO"]){
            self::error("Data not set or not FIO");
        }

        $callback['amount'] = $amount * $data["FIO"];

        //Check if amount got set and round it.
        if (!empty($callback['amount']) && $callback['amount'] > 0)
            return round( $callback['amount'], 6, PHP_ROUND_HALF_UP );
        return self::error("Something wrong with amount");
	}

	private static function error($msg = "Error"){
		return false;
	}

}
WC_Fio_Currency::get_instance();
