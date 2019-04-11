<?php
/**
 *
 *
 */
require_once 'class.textfield.php';

class WPToolset_Field_Taxonomy extends WPToolset_Field_Textfield {

    public $output;
    public $values = "";
    public $objValues;

    /**
     * @var static string $script_localization  Should contain array of script localization for each taxonomy on form.
     */
    public function init() {
        static $script_localization;
        $static_localization_script = &$script_localization;
        unset( $script_localization );
        $this->objValues = array();

        // Compatibility with CRED 1.8.4 and above
        if ( class_exists( 'CRED_Form_Rendering' ) ) {
            $current_post_id = CRED_Form_Rendering::$current_postid;
        } else {
            // CRED <= 1.8.3
            $current_post_id = CredForm::$current_postid;
        }

        /**
         * toolset_filter_taxonomy_terms
         *
         * Terms with all fields as they are returned from wp_get_post_terms()
         * @since 1.8.8
         *
         * @param array terms
         * @param string name field
         */
        $wp_get_post_terms = wp_get_post_terms( $current_post_id, $this->getName(), array("fields" => "all") );
        $terms = apply_filters( 'toolset_filter_taxonomy_terms', $wp_get_post_terms, $this->getName() );

	    if ( $terms
		    && ! is_wp_error( $terms )
	    ) {
	        $i = 0;
	        foreach ( $terms as $n => $term ) {
		        $this->values .= ( $i == 0 ) ? $term->name : "," . $term->name;
		        $this->objValues[ $term->slug ] = $term;
		        $i++;
	        }
        }

	    if ( $static_localization_script === null ) {
		    $static_localization_script = array( 'ajaxurl' => admin_url( 'admin-ajax.php', null ), 'instances' => array() );
	    }


        $static_localization_script['instances'][] = array(
            'values' => $this->values,
            'name' => $this->getName(),
            'form' => WPTOOLSET_FORMS_RELPATH,
            'field' => $this->_nameField,
        );

        $script_localization = &$static_localization_script;

        wp_register_script( 'wptoolset-taxonomy-field', WPTOOLSET_FORMS_RELPATH . '/js/taxonomy.js', array('wptoolset-forms'), WPTOOLSET_FORMS_VERSION, true );

        wp_localize_script( 'wptoolset-taxonomy-field', 'wptoolset_taxonomy_settings', $script_localization );

        wp_enqueue_script( 'wptoolset-taxonomy-field' );
    }

    /**
     * function used when ajax is on in order to init taxonomies
     * @var static string $taxonomies_init_calls  Should contain initTaxonomies function calls for each taxonomy on form
     * @return type
     */
    public function initTaxonomyFunction() {
        static $taxonomies_init_calls;
        $taxonomies_init_calls .= 'initTaxonomies("' . $this->values . '", "' . $this->getName() . '", "' . WPTOOLSET_FORMS_RELPATH . '", "' . $this->_nameField . '");';

        return '<script type="text/javascript">
                    function initCurrentTaxonomy() {
                            ' . $taxonomies_init_calls . '
                    }
                </script>';
    }

    /**
     * metaform
     * @return type
     */
    public function metaform() {
		$use_bootstrap = array_key_exists('use_bootstrap', $this->_data) && $this->_data['use_bootstrap'];
        $attributes = $this->getAttr();
        $taxonomy = $this->getName();

        $this->output = (isset( $attributes['output'] )) ? $attributes['output'] : "";
        $shortcode_class = array_key_exists( 'class', $attributes ) ? $attributes['class'] : "";

        $metaform = array();
        
		if ($this->output == 'bootstrap') {

	        $metaform[] = array(
		        '#type' => 'hidden',
		        '#title' => '',
		        '#description' => '',
		        '#name' => $taxonomy,
		        '#value' => $this->values,
		        '#attributes' => array( 'data-output' => $this->output ),
		        '#validate' => $this->getValidationData(),
	        );


	        $before = sprintf(
		        '<div class="tagchecklist tagchecklist-%s"></div>', $this->getName()
	        );

	        $metaform[] = array(
		        '#type' => 'textfield',
		        '#title' => '',
		        '#description' => '',
		        '#name' => "tmp_" . $taxonomy,
		        '#value' => $this->getValue(),
		        '#attributes' => array(
			        'data-taxonomy' => $taxonomy,
			        'data-taxtype' => 'flat',
			        'class' => "wpt-new-taxonomy-title js-wpt-new-taxonomy-title {$shortcode_class}"
		        ),
		        '#validate' => $this->getValidationData(),
		        '#after' => '',
		        '#before' => $before
	        );

	        $after = $this->getMostPopularTermsAsBootstrap();
	        $bootstrap_class = "btn btn-primary";

	        /**
	         * add button
	         */
	        $metaform[] = array(
		        '#type' => 'button',
		        '#title' => '',
		        '#description' => '',
		        '#name' => "new_tax_button_" . $taxonomy,
		        '#value' => apply_filters( 'toolset_button_add_text', esc_attr( $attributes['add_text'] ) ),
		        '#attributes' => array(
			        'class' => "wpt-taxonomy-add-new js-wpt-taxonomy-add-new {$bootstrap_class}",
			        'data-taxonomy' => $taxonomy,
		        ),
		        '#validate' => $this->getValidationData(),
		        '#after' => $after
	        );

	        $after = "";
	        $before = "";
	        $show = isset( $attributes['show_popular'] ) && $attributes['show_popular'] == 'true';

            $show_hide_taxonomy = "<a             
            style='display:none;'
            data-taxonomy='" . $this->getName() . "'
            data-after-selector='js-show-popular-after' 
            data-show-popular-text='" . apply_filters( 'toolset_button_show_popular_text', esc_attr( $attributes['show_popular_text'] ) ) . "' 
            data-hide-popular-text='" . apply_filters( 'toolset_button_hide_popular_text', esc_attr( $attributes['hide_popular_text'] ) ) . "' 
            class = 'popular wpt-taxonomy-popular-show-hide js-wpt-taxonomy-popular-show-hide dashicons-before dashicons-plus-alt'  
            data-output = '" . $this->output . "'   
            role = 'button' 
            name = 'sh_{$taxonomy}'                
            >" . apply_filters( 'toolset_button_show_popular_text', esc_attr( $attributes['show_popular_text'] ) ) . "</a>";

	        /**
	         * show popular button
	         */
	        $metaform[] = array(
		        '#type' => 'button',
		        '#title' => '',
		        '#description' => '',
		        '#name' => "sh_" . $taxonomy,
		        '#value' => apply_filters( 'toolset_button_show_popular_text', esc_attr( $attributes['show_popular_text'] ) ),
		        '#attributes' => array(
			        'class' => "popular wpt-taxonomy-popular-show-hide js-wpt-taxonomy-popular-show-hide {$bootstrap_class}",
			        'data-taxonomy' => $this->getName(),
			        'data-show-popular-text' => apply_filters( 'toolset_button_show_popular_text', esc_attr( $attributes['show_popular_text'] ) ),
			        'data-hide-popular-text' => apply_filters( 'toolset_button_hide_popular_text', esc_attr( $attributes['hide_popular_text'] ) ),
			        'data-after-selector' => 'js-show-popular-after',
			        'style' => $show ? '' : 'display:none;',
			        'data-output' => $this->output
		        ),
		        '#before' => $show_hide_taxonomy,
		        '#after' => $after . $this->initTaxonomyFunction(),
	        );

        } else {

	        $metaform[] = array(
		        '#type' => 'hidden',
		        '#title' => '',
		        '#description' => '',
		        '#name' => $taxonomy,
		        '#value' => $this->values,
		        '#attributes' => array(
		        ),
		        '#validate' => $this->getValidationData(),
	        );
	        $metaform[] = array(
		        '#type' => 'textfield',
		        '#title' => '',
		        '#description' => '',
		        '#name' => "tmp_" . $taxonomy,
		        '#value' => $this->getValue(),
		        '#attributes' => array(
			        'data-taxonomy' => $taxonomy,
			        'data-taxtype' => 'flat',
			        'class' => $use_bootstrap ? 'inline wpt-new-taxonomy-title js-wpt-new-taxonomy-title' : 'wpt-new-taxonomy-title js-wpt-new-taxonomy-title',
		        ),
		        '#validate' => $this->getValidationData(),
		        '#before' => $use_bootstrap ? '<div class="form-group">' : '',
	        );

	        /**
	         * add button
	         */
	        $metaform[] = array(
		        '#type' => 'button',
		        '#title' => '',
		        '#description' => '',
		        '#name' => "new_tax_button_" . $taxonomy,
		        '#value' => apply_filters('toolset_button_add_text', esc_attr($attributes['add_text'])),
		        '#attributes' => array(
			        'class' => $use_bootstrap ? 'btn btn-default wpt-taxonomy-add-new js-wpt-taxonomy-add-new' : 'wpt-taxonomy-add-new js-wpt-taxonomy-add-new',
			        'data-taxonomy' => $taxonomy,
		        ),
		        '#validate' => $this->getValidationData(),
		        '#after' => $use_bootstrap ? '</div>' : '',
	        );

	        $before = sprintf(
		        '<div class="tagchecklist tagchecklist-%s"></div>', $this->getName()
	        );
	        $after = $this->getMostPopularTerms();
	        if ($use_bootstrap) {
		        $before = '<div class="form-group">' . $before;
		        $after .= '</div>';
	        }
	        $show = isset($attributes['show_popular']) && $attributes['show_popular'] == 'true';
	        /**
	         * show popular button
	         */
	        $metaform[] = array(
		        '#type' => 'button',
		        '#title' => '',
		        '#description' => '',
		        '#name' => "sh_" . $taxonomy,
		        '#value' => apply_filters('toolset_button_show_popular_text', esc_attr($attributes['show_popular_text'])),
		        '#attributes' => array(
			        'class' => $use_bootstrap ? 'btn btn-default popular wpt-taxonomy-popular-show-hide js-wpt-taxonomy-popular-show-hide' : 'popular wpt-taxonomy-popular-show-hide js-wpt-taxonomy-popular-show-hide',
			        'data-taxonomy' => $this->getName(),
			        'data-show-popular-text' => apply_filters('toolset_button_show_popular_text', esc_attr($attributes['show_popular_text'])),
			        'data-hide-popular-text' => apply_filters('toolset_button_hide_popular_text', esc_attr($attributes['hide_popular_text'])),
			        'data-after-selector' => 'js-show-popular-after',
			        'style' => $show ? '' : 'display:none;'
		        ),
		        '#before' => $before,
		        '#after' => $after . $this->initTaxonomyFunction(),
	        );

        }

        return $metaform;
    }

    private function buildTerms($obj_terms) {
        $tax_terms = array();
        foreach ( $obj_terms as $term ) {
            $tax_terms[] = array(
                'name' => $term->name,
                'count' => $term->count,
                'parent' => $term->parent,
                'term_taxonomy_id' => $term->term_taxonomy_id,
                'term_id' => $term->term_id
            );
        }
        return $tax_terms;
    }

    private function buildCheckboxes($index, &$childs, &$names) {
        if ( isset( $childs[$index] ) ) {
            foreach ( $childs[$index] as $tid ) {
                $name = $names[$tid];
                ?>
                <div style='position:relative;line-height:0.9em;margin:2px 0;<?php if ( $tid != 0 ) echo 'margin-left:15px'; ?>' class='myzebra-taxonomy-hierarchical-checkbox'>
                    <label class='myzebra-style-label'><input type='checkbox' name='<?php echo $name; ?>' value='<?php echo $tid; ?>' <?php if ( isset( $values[$tid] ) ) echo 'checked="checked"'; ?> /><span class="myzebra-checkbox-replace"></span>
                        <span class='myzebra-checkbox-label-span' style='position:relative;font-size:12px;display:inline-block;margin:0;padding:0;margin-left:15px'><?php echo $names[$tid]; ?></span></label>
                        <?php
                        if ( isset( $childs[$tid] ) )
                            echo $this->buildCheckboxes( $tid, $childs, $names );
                        ?>
                </div>
                <?php
            }
        }
    }

    public function getMostPopularTermsAsBootstrap() {
        $term_args = array(
            'number' => 10,
            'orderby' => 'count',
            'order' => 'DESC'
        );
        $terms = get_terms( array($this->getName()), $term_args );
	    if ( empty( $terms )
		    || is_wp_error( $terms )
	    ) {
		    return '';
	    }
        $max = -1;
        $min = PHP_INT_MAX;
        foreach ( $terms as $term ) {
            if ( $term->count < $min ) {
                $min = $term->count;
            }
            if ( $term->count > $max ) {
                $max = $term->count;
            }
        }
        $add_sizes = $max > $min;

        $bootstrap_class = 'well well-default';

        $content = sprintf(
                '<div class="shmpt-%s wpt-taxonomy-show-popular-list js-show-popular-after %s" style="display:none">', $this->getName(), $bootstrap_class
        );

        foreach ( $terms as $term ) {
            $style = '';
            if ( $add_sizes ) {
                $font_size = ( ( $term->count - $min ) * 10 ) / ( $max - $min ) + 8;
                $style = sprintf( ' style="font-size:%fem;"', $font_size / 10 );
            }
            $classes = array('wpt-taxonomy-popular-add', 'js-wpt-taxonomy-popular-add');
            $classes[] = 'tax-' . $term->slug;
            $classes[] = 'taxonomy-' . $this->getName() . '-' . $term->term_id;
            $content .= sprintf(
                    '<a href="#" class="%s" data-slug="%s" data-name="%s" data-taxonomy="%s"%s>%s</a> ', implode( ' ', $classes ), $term->slug, $term->name, $this->getName(), $style, $term->name
            );
        }
        $content .= "</div>";
        return $content;
    }

	public function getMostPopularTerms() {
		$use_bootstrap = array_key_exists('use_bootstrap', $this->_data) && $this->_data['use_bootstrap'];

		$term_args = array(
			'number' => 10,
			'orderby' => 'count',
			'order' => 'DESC'
		);
		$terms = get_terms(array($this->getName()), $term_args);
		if (empty($terms)) {
			return '';
		}
		$max = -1;
		$min = PHP_INT_MAX;
		foreach ($terms as $term) {
			if ($term->count < $min) {
				$min = $term->count;
			}
			if ($term->count > $max) {
				$max = $term->count;
			}
		}
		$add_sizes = $max > $min;

		if ($use_bootstrap) {
			$content = sprintf(
				'<div class="shmpt-%s form-group wpt-taxonomy-show-popular-list js-show-popular-after" style="display:none">', $this->getName()
			);
		} else {
			$content = sprintf(
				'<div class="shmpt-%s wpt-taxonomy-show-popular-list js-show-popular-after" style="display:none">', $this->getName()
			);
		}

		foreach ($terms as $term) {
			$style = '';
			if ($add_sizes) {
				$font_size = ( ( $term->count - $min ) * 10 ) / ( $max - $min ) + 8;
				$style = sprintf(' style="font-size:%fem;"', $font_size / 10);
			}
			$clases = array('wpt-taxonomy-popular-add', 'js-wpt-taxonomy-popular-add');
			$clases[] = 'tax-' . $term->slug;
			$clases[] = 'taxonomy-' . $this->getName() . '-' . $term->term_id;
			$content .= sprintf(
				'<a href="#" class="%s" data-slug="%s" data-name="%s" data-taxonomy="%s"%s>%s</a> ', implode(' ', $clases), $term->slug, $term->name, $this->getName(), $style, $term->name
			);
		}
		$content .= "</div>";
		return $content;
	}

}
