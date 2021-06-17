<?php

namespace wpautoterms\frontend\notice;

use wpautoterms\api\License;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cookies_Notice extends Base_Notice {
	const CLASS_COOKIES_NOTICE = 'wpautoterms-cookies-notice';
	const CLASS_CLOSE_BUTTON = 'wpautoterms-notice-close';
	/**
	 * @var License
	 */
	protected $_license;

	public static function create( $license ) {
		$a = new Cookies_Notice( 'cookies_notice', 'wpautoterms-cookies-notice-container', self::CLASS_COOKIES_NOTICE );
		$a->set_license( $license );

		return $a;
	}

	public function set_license( $license ) {
		$this->_license = $license;
	}

	protected function _is_enabled() {
		return $this->_license->is_paid() && parent::_is_enabled();
	}

	protected function _print_box() {
		$cookie = 'wpautoterms-cookies-notice';
		$class_escaped = esc_attr( Cookies_Notice::CLASS_COOKIES_NOTICE );
		\wpautoterms\print_template( 'cookies-notice', array(
			'cookie_name' => $cookie,
			'cookie_value' => 1,
			'class_escaped' => $class_escaped,
			'message' => do_shortcode( $this->_message ),
			'close' => $this->_close_message,
		) );
	}
}
