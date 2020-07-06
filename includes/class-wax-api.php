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

	// TODO: remove hardcoded api key and put it in plugin configs
	private static $apikey = "server_9316a69872adf068d6a20beb8f75b405";

	public static function get_jwt_token($apikey = "server_9316a69872adf068d6a20beb8f75b405") {
		$res = wp_remote_get('http://'.$server.$path);
		$res = rest_ensure_response($res);
		if(empty($res) || empty($res->status) || $res->status !== 200){
			return false;
		}
		$body = json_decode($res->data['body']);
		if(is_object($body) && !empty($body->token)) {
			return $body->token
		}
		return false;
	}

	/*
	 * todo: Much todo here, only get from last got hash. But lets keep it stupid meanwhile.
	 * gets last 100 transactions receiving tokens
	 * */
	public static function get_latest_100_transactions( $account ) {
        $server = self::$server;

		$path = '/v0/search/transactions?start_block=0&block_count=10000&limit=100&sort=desc&q=receiver:eosio.token+action:transfer+data.to:'.$account;
		$res = wp_remote_get($server.$path);
		$res = rest_ensure_response($res);
		if(empty($res) && empty($res->status) && $res->status !== 200){
			return false;
		}
		$body = json_decode($res->data['body']);
		if(is_object($body) && !empty($body->transactions)){
			//Need api to support transactions forward
			//WC()->session->set('last_nem_transaction_hash', $transactions->data[0]->meta->hash->data);
			return $transactions->data;
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
				'amount' => get_amount($transaction->lifecycle->execution_trace->action_traces[0]->act->data->quantity),
			  ];
		}, $transactions);
		return array_filter(function ($transaction) {
			return strpos($transaction->action->data->quantity, 'WAX') !== false;
		}, $mapped);
	}

	public static function get_latest_transactions($account) {
		$transactions = get_latest_100_transactions($account);
		return transform_transactions($transactions);
	}

}