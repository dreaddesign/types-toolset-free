<?php

/**
 * Enum class for names of post type labels as defined in get_post_type_labels()
 *
 * @link https://developer.wordpress.org/reference/functions/get_post_type_labels/
 * @since m2m
 */
class Toolset_Post_Type_Labels {

	const NAME = 'name';
	const SINGULAR_NAME = 'singular_name';
	const ADD_NEW = 'add_new';
	const ADD_NEW_ITEM = 'add_new_item';
	const EDIT_ITEM = 'edit_item';
	const NEW_ITEM = 'new_item';
	const VIEW_ITEM = 'view_item';
	const VIEW_ITEMS = 'view_items';
	const SEARCH_ITEMS = 'search_items';
	const NOT_FOUND = 'not_found';
	const NOT_FOUND_IN_TRASH = 'not_found_in_trash';
	const PARENT_ITEM_COLON = 'parent_item_colon';
	const ALL_ITEMS = 'all_items';
	const ARCHIVES = 'archives';
	const ATTRIBUTES = 'attributes';
	const INSERT_INTO_ITEM = 'insert_into_item';
	const UPLOADED_TO_THIS_ITEM = 'uploaded_to_this_item';
	const FEATURED_IMAGE = 'featured_image';
	const SET_FEATURED_IMAGE = 'set_featured_image';
	const REMOVE_FEATURED_IMAGE = 'remove_featured_image';
	const USE_FEATURED_IMAGE = 'use_featured_image';
	const MENU_NAME = 'menu_name';
	const FILTER_ITEMS_LIST = 'filter_items_list';
	const ITEMS_LIST_NAVIGATION = 'items_list_navigation';
	const ITEMS_LIST = 'items_list';


	/**
	 * @return string[] All label names.
	 */
	public static function all() {
		return array(
			self::NAME, self::SINGULAR_NAME, self::ADD_NEW, self::ADD_NEW_ITEM, self::EDIT_ITEM, self::NEW_ITEM,
			self::VIEW_ITEM, self::VIEW_ITEMS, self::SEARCH_ITEMS, self::NOT_FOUND, self::NOT_FOUND_IN_TRASH,
			self::PARENT_ITEM_COLON, self::ALL_ITEMS, self::ARCHIVES, self::ATTRIBUTES, self::INSERT_INTO_ITEM,
			self::UPLOADED_TO_THIS_ITEM, self::FEATURED_IMAGE, self::SET_FEATURED_IMAGE, self::REMOVE_FEATURED_IMAGE,
			self::USE_FEATURED_IMAGE, self::MENU_NAME, self::FILTER_ITEMS_LIST, self::ITEMS_LIST_NAVIGATION,
			self::ITEMS_LIST
		);
	}


	public static function mandatory() {
		return array( self::NAME, self::SINGULAR_NAME );
	}

}