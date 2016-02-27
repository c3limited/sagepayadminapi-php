<?php
/**
 * @author Colin Burn-Murdoch <colin@burn-murdoch.com>
 *
 **/
namespace colinmb;

class SagePayAdminApi {

	private $vendor;
	private $username;
	private $password;
	private $xml;
	private $url;
	private $sslverify;

	public $curlTimeout = 90;

	public function __construct($vendor, $username, $password, $live = true, $sslverify = true) {
		$this->vendor   = $vendor;
		$this->username = $username;
		$this->password = $password;
		$this->url = 'https://' . ($live ? 'live' : 'test') . '.sagepay.com/access/access.htm';
		$this->sslverify = $sslverify;
	}

	public function __call($name, $args) {
		$this->xml = $this->xmlise($name, $args[0]);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,'XML='.$this->xml);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->curlTimeout);
		if (!$this->sslverify) curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($ch);
		curl_close($ch);
		return new SimpleXMLElement($response);
	}

	private function xmlise($command, $elements) {
		$xml = "<command>{$command}</command><vendor>{$this->vendor}</vendor><user>{$this->username}</user>";
		foreach($elements as $key => $value) {
			$xml .= $this->recursiveKeyValue($key, $value);
		}
		$signature = md5($xml . "<password>{$this->password}</password>");
		$xml .= "<signature>{$signature}</signature>";
		return "<vspaccess>{$xml}</vspaccess>";
	}

	// This is just for debugging.
	public function getXml() {
		return $this->xml;
	}

	public function recursiveKeyValue($key, $value) {
		$return = "<{$key}>";
		if (is_array($value)){
			if ($this->is_assoc($value)) {
				foreach ($value as $sub_key => $sub_value) {
					$return .= $this->recursiveKeyValue($sub_key, $sub_value);
				}
			} else {
				$return = '';
				foreach ($value as $sub_value) {
					$return .= $this->recursiveKeyValue($key, $sub_value);
				}
				return $return;
			}
		} else {
			$return .= $value;
		}
		$return .= "</{$key}>";
		return $return;
	}

	// Checks if array is associative
	private function is_assoc($a) {
		$a = array_keys($a);
		return ($a !== array_keys($a));
	}
}
