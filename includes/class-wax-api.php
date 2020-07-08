<?php

class WaxApi {

	private static $instance;

	private static $server = 'https://mainnet.wax.dfuse.io/';

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function get_jwt_token($api_key) {
		$body = [
			'api_key' => $api_key,
		];

		$url = "https://auth.dfuse.io/v1/auth/issue";
		$res = wp_remote_post( $url, array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => array(),
			'body' => wp_json_encode($body),
			)
		);
		$res = rest_ensure_response($res);
		if(empty($res) || empty($res->status) || $res->status !== 200){
			return false;
		}
		$body = json_decode($res->data['body']);
		if(is_object($body) && !empty($body->token)) {
			return $body->token;
		}
		return false;
	}

	/*
	 * gets last 100 transactions receiving tokens
	 * */
	public static function get_latest_100_transactions( $account, $api_key ) {
		$server = self::$server;
		
		$jwt_token = WaxApi::get_jwt_token($api_key);

		$path = '/v0/search/transactions?start_block=0&limit=100&sort=desc&q=receiver:eosio.token+action:transfer+data.to:'.$account;
		$args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $jwt_token,
			),
			'timeout'     => 20,
		); 
		$res = wp_remote_get($server.$path, $args);
		$res = rest_ensure_response($res);
		if(empty($res) && empty($res->status) && $res->status !== 200){
			return false;
		}
		$body = json_decode($res->data['body']);
		if(is_object($body) && !empty($body->transactions)){
			return $body->transactions;
		}
		return false;
	}

	// transform from str 'xxx.xxx WAX' to double xxx.xxx
	public static function get_amount($quantitystring) {
		$stringnum = str_replace(" WAX", "", $quantitystring);
		return floatval($stringnum);
	}

	// filter transactions (only wax), and map to only contain execution_trace->action_traces->act->(data)
	// keep the id of the transaction
	public static function transform_transactions($transactions) {
		$mapped = array_map(function ($transaction) {
			return (object) [
				'id' => $transaction->lifecycle->id,
				'action' => $transaction->lifecycle->execution_trace->action_traces[0]->act,
				'amount' => WaxApi::get_amount($transaction->lifecycle->execution_trace->action_traces[0]->act->data->quantity),
			  ];
		}, $transactions);
		$filtered = array_filter($mapped, function ($transaction) {
			return strpos($transaction->action->data->quantity, 'WAX') !== false;
		});
		return $filtered;
	}

	public static function get_latest_transactions($account, $api_key) {
		$transactions = WaxApi::get_latest_100_transactions($account, $api_key);
		return WaxApi::transform_transactions($transactions);
	}

}