<?php

/**
 * Enum class for holding a list of special, well-known post types.
 *
 * @since 2.6.4
 */
abstract class Toolset_Post_Type_List {

	const VIEW_OR_WPA = 'view';
	const CONTENT_TEMPLATE = 'view-template';

	const POST_FIELD_GROUP = 'wp-types-group';
	const USER_FIELD_GROUP = 'wp-types-user-group';
	const TERM_FIELD_GROUP = 'wp-types-term-group';

	const CRED_POST_FORM = 'cred-form';
	const CRED_USER_FORM = 'cred-user-form';
	const CRED_RELATIONSHIP_FORM = 'cred_rel_form';

	const LAYOUT = 'dd_layouts';

	const ATTACHMENT = 'attachment';

}