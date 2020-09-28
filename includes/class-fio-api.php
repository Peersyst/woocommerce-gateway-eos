<?php

class WC_FioApi {

	private static $instance;

	private static $server = 'https://fio.cryptolions.io';

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/*
	 * gets last 100 transactions receiving tokens
	 * */
	public static function get_latest_100_transactions( $account ) {
		$server = self::$server;

		$res = wp_remote_get($server.'/v2/history/get_actions?limit=100&account='.$account.'&sort=desc&simple=false&noBinary=true&checkLib=false&act.name=trnsfiopubky');
		$res = rest_ensure_response($res);
		if(empty($res) || empty($res->status) || $res->status !== 200){
			return false;
		}
		$body = json_decode($res->data['body']);
		if(is_object($body) && !empty($body->actions)) {
			return $body->actions;
		}
	}

	public static function transform_amount($amount_string) {
		return floatval($amount_string)/1000000000;
	}

	// filter transactions (only fio), and map to only contain act->(data)
	// keep the id of the transaction
	public static function transform_transactions($transactions) {
		$mapped = array_map(function ($transaction) {
			return (object) [
				'id' => $transaction->trx_id,
				'action' => $transaction->act->data,
				'amount' => WC_FioApi::transform_amount($transaction->act->data->amount)
			  ];
		}, $transactions);
		return $mapped;
	}

	public static function get_latest_transactions($account) {
		$transactions = WC_FioApi::get_latest_100_transactions($account);
		$t = WC_FIOApi::transform_transactions($transactions);
		return $t;
	}

}