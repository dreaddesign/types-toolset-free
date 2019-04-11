<?php


interface Types_Wpml_Interface {
	public function translate();
	public function register( $slug_update = false );
}