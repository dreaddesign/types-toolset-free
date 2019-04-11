<?php


class Types_Information_Table extends Types_Information_Container {

	protected $template;
	protected $archive;
	protected $views;
	protected $forms;

	/**
	 * @param $message
	 *
	 * @return bool
	 */
	public function add_message( Types_Information_Message $message ) {
		if( $message->get_type() )
			switch( $message->get_type() ) {
				case 'template':
					if( $this->template === null && $message->valid() )
						$this->template[] = $message;
					break;
				case 'archive':
					if( $this->archive === null && $message->valid() )
						$this->archive[] = $message;
					break;
				case 'views':
					if( $this->views === null && $message->valid() )
						$this->views[] = $message;
					break;
				case 'forms':
					if( $this->forms === null && $message->valid() )
						$this->forms[] = $message;
					break;
			}
	}

	public function get_template( $force = false ) {
		$post_type = Types_Helper_Condition::get_post_type();

		if( ! $force ) {
			$allowed_columns = apply_filters( 'types_information_table_columns', array_fill_keys( array( 'template', 'archive', 'views', 'forms' ), '' ), $post_type->name );

			if( ! isset( $allowed_columns['template'] ) )
				return false;
		}
		
		return $this->template;
	}

	public function get_archive( $force = false ) {
		$post_type = Types_Helper_Condition::get_post_type();

		if( ! $force ) {
			$allowed_columns = apply_filters( 'types_information_table_columns', array_fill_keys( array( 'template', 'archive', 'views', 'forms' ), '' ), $post_type->name );

			if( ! isset( $allowed_columns['archive'] ) )
				return false;
		}

		return $this->archive;
	}

	public function get_views() {
		return $this->views;
	}

	public function get_forms() {
		return $this->forms;
	}

}