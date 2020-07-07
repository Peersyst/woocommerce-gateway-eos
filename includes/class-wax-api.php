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
		$body = [
			'api_key' => $apikey,
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
	public static function get_latest_100_transactions( $account ) {
		$server = self::$server;
		
		// TODO: redo it with get_token
		// $jwt_token = WaxApi::get_jwt_token();
		$jwt_token = "eyJhbGciOiJLTVNFUzI1NiIsInR5cCI6IkpXVCJ9.eyJleHAiOjE1OTQyMDQ3NDMsImp0aSI6ImNmZjljNDYyLWZkMmQtNDcwYy1iNzc4LWEwZDhmM2YyYTE1YSIsImlhdCI6MTU5NDExODM0MywiaXNzIjoiZGZ1c2UuaW8iLCJzdWIiOiJ1aWQ6MGNvbHkyMDY5ODk3ZjJhMjYyOTdmIiwidGllciI6ImZyZWUtdjEiLCJ2IjoxLCJ1c2ciOiJzZXJ2ZXIiLCJha2kiOiJhN2QyYmQxZjJlNTU5MzI3YWI4ZmE4M2NmZmVlMjFkMmRlYTBjYjM5NTRjOGVjZjU5ZGJhNjZhODMyOTRiZWFjIiwib3JpZ2luIjoiMGNvbHkyMDY5ODk3ZjJhMjYyOTdmIiwic3RibGsiOi0zNjAwLCJwbGFuIjowfQ.TFJD7D6i1k0Rcx2m-25dSzajKzqacULUeHV78FP9yCSdYPzyCI7WZisqfKfM2ZEp5-wJrd4uvo5t_3lGLfVUxw";
		// error_log(print_r($jwt_token, true));

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
		// error_log(print_r($transactions, true));
		$mapped = array_map(function ($transaction) {
			return (object) [
				'id' => $transaction->lifecycle->id,
				'action' => $transaction->lifecycle->execution_trace->action_traces[0]->act,
				'amount' => WaxApi::get_amount($transaction->lifecycle->execution_trace->action_traces[0]->act->data->quantity),
			  ];
		}, $transactions);
		// error_log(print_r($mapped, true));
		$filtered = array_filter($mapped, function ($transaction) {
			return strpos($transaction->action->data->quantity, 'WAX') !== false;
		});
		// error_log(print_r($filtered, true));
		error_log(count($filtered));
		return $filtered;
	}

	public static function get_latest_transactions($account) {
		$transactions = WaxApi::get_latest_100_transactions($account);
		return WaxApi::transform_transactions($transactions);
	}

}