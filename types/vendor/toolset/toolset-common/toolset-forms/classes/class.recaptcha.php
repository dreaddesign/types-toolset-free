<?php

require_once 'class.textfield.php';

/**
 * Class responsible to create Google Recaptcha V2
 */
class WPToolset_Field_Recaptcha extends WPToolset_Field_Textfield {

	private $pubkey = '';
	private $privkey = '';

	/**
	 * Recaptcha init getting public and private keys, the source lang of the component by wp or wpml if actived
	 */
	public function init() {
		$attr = $this->getAttr();

		//Site Key
		$this->pubkey = isset( $attr['public_key'] ) ? $attr['public_key'] : '';
		//Secret Key
		$this->privkey = isset( $attr['private_key'] ) ? $attr['private_key'] : '';

		// get_user_locale() was introduced in WordPress 4.7
		$locale = ( function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale() );
		$user_locale_lang = substr( $locale, 0, 2 );

		$wpml_source_lang = isset( $_REQUEST['source_lang'] ) ? sanitize_text_field( $_REQUEST['source_lang'] ) : apply_filters( 'wpml_current_language', null );
		$wpml_lang = isset( $_REQUEST['lang'] ) ? sanitize_text_field( $_REQUEST['lang'] ) : $wpml_source_lang;

		$lang = isset( $wpml_lang ) ? $wpml_lang : $user_locale_lang;

		wp_enqueue_script( 'wpt-cred-recaptcha', '//www.google.com/recaptcha/api.js?onload=onLoadRecaptcha&render=explicit&hl=' . $lang );
	}

	/**
	 * Create recaptcha metaform as requested by superclass
	 *
	 * @return array
	 */
	public function metaform() {
		$form = array();
		$data = $this->getData();

		$capture = '';
		if (
			$this->pubkey
			|| ! Toolset_Utils::is_real_admin()
		) {
			$capture = '<div id="recaptcha_' . esc_attr( $data['id'] ) . '" class="g-recaptcha" data-sitekey="' . esc_attr( $this->pubkey ) . '"></div><div class="recaptcha_error" style="color:#aa0000;display:none;">' . __( 'Please validate reCAPTCHA', 'wpv-views' ) . '</div>';
		}

		$form[] = array(
			'#type' => 'textfield',
			'#title' => '',
			'#name' => '_recaptcha',
			'#value' => '',
			'#attributes' => array( 'style' => 'display:none;' ),
			'#before' => $capture,
		);

		return $form;
	}

}
