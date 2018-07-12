<?php
if ( ! defined('BASEPATH')) { exit('No direct script access allowed'); }
Class Lib_rijandelcrypt {
	protected $encrypt_config = array();
	function __construct($encrypt_config) {
		$this->encrypt_config = array(
			'ENCRYPT_KEY'					=> (isset($encrypt_config['ENCRYPT_KEY']) ? $encrypt_config['ENCRYPT_KEY'] : ''),
			'ENCRYPT_IV'					=> (isset($encrypt_config['ENCRYPT_IV']) ? $encrypt_config['ENCRYPT_IV'] : ''),
		);
	}
	public function decrypt($input) {
        $dectext = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->encrypt_config['ENCRYPT_KEY'], base64_decode(strtr($input, '-_', '+/')), MCRYPT_MODE_CBC, $this->encrypt_config['ENCRYPT_IV']);
        $dectext= rtrim($dectext,"\x00..\x1F");
        return $dectext;
    }
	public function encrypt($text) {
		$block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
        $padding = $block - (strlen($text) % $block);
        $text .= str_repeat(chr($padding), $padding);
        $crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->encrypt_config['ENCRYPT_KEY'], $text, MCRYPT_MODE_CBC, $this->encrypt_config['ENCRYPT_IV']);
		return base64_encode(strtr($crypttext, '+/', '-_'));
    }
	
	
	
	
	
	
	
}