<?php

/**
 * Returns the default available featured media types
 */
function largo_default_featured_media_types() {

	$media_types = apply_filters('largo_default_featured_media_types', array(
		'embed' => array(
			'title' => __( 'Featured embed code', 'largo' ),
			'id' => 'embed-code'
		),
		'video' => array(
			'title' =>  __( 'Featured video', 'largo' ),
			'id' => 'video'
		),
		'image' => array(
			'title' => __( 'Featured image', 'largo' ),
			'id' => 'image'
		),
		'gallery' => array(
			'title' => __( 'Featured photo gallery', 'largo' ),
			'id' => 'gallery'
		)
	));

	return array_values( $media_types );

}

/**
 * Prints DOM for hero image.
 *
 * Determines the type of featured media attached to a post,
 * and generates the DOM for that type of media.
 *
 * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global $post.
 * @param String $classes Optional. Class string to apply to outer div.hero
 */
function largo_hero( $post = null, $classes = '' ) {

	echo largo_get_hero( $post, $classes );

}

/**
 * Return DOM for hero image.
 *
 * Determines the type of featured media attached to a post,
 * and generates the DOM for that type of media.
 *
 * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global $post.
 * @param String $classes Optional. Class string to apply to outer div.hero
 */
function largo_get_hero( $post = null, $classes = '' ) {

	$post = get_post( $post );
	$hero_class = largo_hero_class( $post->ID, false );
	$ret = '';
	$values = get_post_custom( $post->ID );

	// If the box is checked to override the featured image display, obey it.
	// EXCEPT if a youtube_url is added in the old way for the post. This is to respect
	// behavior before v0.4,
	if ( isset( $values['featured-image-display'][0] ) && ! isset( $values['youtube_url'] ) ) {
		return $ret;
	}
	if ( largo_has_featured_media( $post->ID ) && $hero_class !== 'is-empty' ) {

		$featured_media = largo_get_featured_media( $post->ID );
		$ret = largo_get_featured_hero( $post->ID,$classes );
	}

	/**
	 * Filter the hero's DOM
	 *
	 * @since 0.5.1
	 *
	 * @param String $var    DOM for hero.
	 * @param WP_Post $post  post object.
	 */
	$ret = apply_filters( 'largo_get_hero', $ret, $post, $classes );
	return $ret;
}

/**
 * Prints DOM for a featured image hero.
 *
 * @since 0.5.1
 * @see largo_get_featured_hero()
 *
 * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global $post.
 * @param String $classes Optional. Class string to apply to outer div.hero
 */
function largo_featured_image_hero( $post = null, $classes = '' ) {
	echo largo_get_featured_hero( $post, $classes );
}

/**
 * Prints DOM for a featured image hero.
 *
 * @since 0.5.5
 *
 * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global $post.
 * @param String $classes Optional. Class string to apply to outer div.hero
 */
function largo_get_featured_hero( $post = null, $classes = '' ) {
	$the_post = get_post( $post );
	$featured_media = largo_get_featured_media( $the_post->ID );

	$hero_class = largo_hero_class( $the_post->ID, false );
	$classes = 'hero $hero_class $classes';

	if ( 'image' == $featured_media['type'] ) {
		$thumb_meta = null;
		if ( $thumb_id = get_post_thumbnail_id( $the_post->ID ) ) {
			$thumb_content = get_post( $thumb_id );
			$thumb_custom = get_post_custom( $thumb_id );

			$thumb_meta = array(
				'caption' => ( ! empty( $thumb_content->post_excerpt ) ) ? $thumb_content->post_excerpt : null,
				'credit' => ( ! empty( $thumb_custom['_media_credit'][0] ) ) ? $thumb_custom['_media_credit'][0] : null,
				'credit_url' => ( ! empty( $thumb_custom['_media_credit_url'][0] ) ) ? $thumb_custom['_media_credit_url'][0] : null,
				'organization' => ( ! empty( $thumb_custom['_navis_media_credit_org'][0] ) ) ? $thumb_custom['_navis_media_credit_org'][0] : null
			);
		}

	}

	$context = array(
		'classes' => $classes,
		'thumb_meta' => $thumb_meta,
		'the_post' => $the_post
	);

	switch( $featured_media['type'] ) {
		case 'video':
			$template_slug = 'video';
			break;
		case 'image':
			$template_slug = 'image';
			break;
		case 'embed-code':
			$template_slug = 'embed';
			break;
		case 'gallery':
			$template_slug = 'gallery';
			break;
	}

	ob_start();
	largo_render_template( 'partials/hero', 'featured-' . $template_slug, $context );
	$ret = ob_get_clean();
	return $ret;
}

/**
 * Prints DOM for an embed code hero.
 *
 * @since 0.5.1
 * @see largo_get_featured_hero()
 *
 * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global $post.
 * @param String $classes Optional. Class string to apply to outer div.hero
 */
function largo_featured_embed_hero( $post = null, $classes = '' ) {
	echo largo_get_featured_hero( $post, $classes );
}

/**
 * Prints DOM for a featured gallery hero.
 *
 * @since 0.5.1
 * @see largo_get_featured_hero()
 *
 * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global $post.
 * @param String $classes Optional. Class string to apply to outer div.hero
 */
function largo_featured_gallery_hero( $post = null, $classes = '' ) {
	echo largo_get_featured_hero( $post, $classes );
}

/**
 * Returns information about the featured media.
 *
 * @since 0.4
 *
 * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global $post.
 * @return array $post_type {
 *
 * 			'id' => int, 		// post id.
 * 			'type' => string, 	// the type of featured_media
 *
 * 			// ... other variables, dependent on what the type is.
 *
 * 		}
 */
function largo_get_featured_media( $post = null ) {

	$post = get_post( $post );

	$ret = get_post_meta( $post->ID, 'featured_media', true );

	// Check if the post has a thumbnail/featured image set.
	// If yes, send that back as the featured media.
	$post_thumbnail = get_post_thumbnail_id( $post->ID );
	if ( empty( $ret ) && ! empty( $post_thumbnail ) ) {
		$ret = array(
			'id' => $post->ID,
			'attachment' => $post_thumbnail,
			'type' => 'image'
		);
	} else if ( ! empty( $ret ) && in_array( $ret['type'], array( 'embed', 'video' ) ) && ! empty( $post_thumbnail ) ) {
		$attachment = wp_prepare_attachment_for_js( $post_thumbnail );
		$ret = array_merge( $ret, array( 'attachment_data' => $attachment ) );
	}

	// Backwards compatibility with posts that have a youtube_url set
	$youtube_url = get_post_meta( $post->ID, 'youtube_url', true );
	if ( empty( $ret ) && ! empty( $youtube_url ) ) {
		$ret = array(
			'id' => $post->ID,
			'url' => $youtube_url,
			'embed' => wp_oembed_get( $youtube_url ),
			'type' => 'video'
		);
	}
	return $ret;
}

/**
 * Does the post have featured media?
 *
 * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global $post.
 * @return bool If a post ID has featured media or not.
 */
function largo_has_featured_media( $post = null ) {
	$post = get_post( $post );
	$id = isset( $post->ID ) ? $post->ID : 0;
	$featured_media = largo_get_featured_media( $id );
	return ! empty( $featured_media );
}

/**
 * Functions that modify the dashboard/load the featured media functionality
 */

/**
 * Enqueue the featured media javascript
 *
 * @global $post
 * @global LARGO_DEBUG
 * @param array $hook The page that this function is being run on.
 */
function largo_enqueue_featured_media_js( $hook ) {
	if ( ! in_array( $hook, array( 'edit.php', 'edit-tags.php', 'post-new.php', 'post.php', 'term.php' ) ) ) {
		return;
	}
	global $post, $wp_query;

	// Run this action on term edit pages
	// edit-tags.php for wordpress before 4.5
	// term.php for 4.5 and after
	if ( in_array( $hook, array( 'edit-tags.php', 'term.php ') ) && is_numeric( $_GET['tag_ID'] ) ) {
		// After WordPress 4.5, the taxonomy is no longer in the URL
		// So to compensate, we get the taxonomy from the current screen
		$screen = get_current_screen();
		$post = get_post( largo_get_term_meta_post( $screen->taxonomy, $_GET['tag_ID'] ) );
	}

	$featured_image_display = get_post_meta( $post->ID, 'featured-image-display', true );

	// The scripts following depend upon the WordPress media APIs
	wp_enqueue_media();

	$suffix = ( LARGO_DEBUG ) ? '' : '.min';
	wp_enqueue_script(
		'largo_featured_media', get_template_directory_uri() . '/js/featured-media' . $suffix . '.js',
		array( 'media-models', 'media-views' ), false, 1
	);

	wp_enqueue_style(
		'largo_featured_media',
		get_template_directory_uri(). '/css/featured-media' . $suffix . '.css'
	);

	wp_localize_script( 'largo_featured_media', 'LFM', array(
		'options' => largo_default_featured_media_types(),
		'featured_image_display' => ! empty( $featured_image_display ),
		'has_featured_media' => (bool) largo_has_featured_media( $post->ID )
	));
}
add_action( 'admin_enqueue_scripts', 'largo_enqueue_featured_media_js' );

/**
 * Prints the templates used by featured media modal.
 */
function largo_featured_media_templates() { ?>
	<script type="text/template" id="tmpl-featured-embed-code">
		<form id="featured-embed-code-form">
			<input type="hidden" name="type" value="embed-code" />

			<# var model = data.controller.model #>
			<div>
				<label for="title"><span>Title</span></label>
				<input type="text" name="title" <# if (model.get('type') == 'embed-code') { #>value="{{ model.get('title') }}"<# } #> />
			</div>

			<div>
				<label for="caption"><span>Caption</span></label>
				<input type="text" name="caption" <# if (model.get('type') == 'embed-code') { #>value="{{ model.get('caption') }}"<# } #> />
			</div>

			<div>
				<label for="credit"><span>Credit</span></label>
				<input type="text" name="credit" <# if (model.get('type') == 'embed-code') { #>value="{{ model.get('credit') }}"<# } #> />
			</div>

			<div>
				<label for="url"><span>URL</span></label>
				<input type="text" name="url" <# if (model.get('type') == 'embed-code') { #>value="{{ model.get('url') }}"<# } #> />
			</div>

			<div>
				<label for="embed"><span>Embed code</span></label>
				<textarea name="embed"><# if (model.get('type') == 'embed-code') { #>{{ model.get('embed') }}<# } #></textarea>
			</div>

			<div>
				<label><span>Embed thumbnail</span></span></label>
				<div id="embed-thumb"></div>
			</div>
		</form>
	</script>

	<script type="text/template" id="tmpl-featured-video">
		<form id="featured-video-form">
			<input type="hidden" name="type" value="video" />

			<p>Enter a video URL to get started.</p>
			<# var model = data.controller.model #>
			<div>
				<label for="url"><span>Video URL  <span class="spinner" style="display: none;"></span></label>
				<input type="text" class="url" name="url" <# if (model.get('type') == 'video') { #>value="{{ model.get('url') }}"<# } #>/>
				<p class="error"></p>
			</div>

			<div>
				<label for="embed"><span>Video embed code</span></label>
				<textarea name="embed"><# if (model.get('type') == 'video') { #>{{ model.get('embed') }}<# } #></textarea>
			</div>

			<div>
				<label><span>Video thumbnail</span></span></label>
				<div id="embed-thumb"></div>
			</div>

			<div>
				<label for="title"><span>Title</span></span></label>
				<input type="text" name="title" <# if (model.get('type') == 'video') { #>value="{{ model.get('title') }}"<# } #> />
			</div>

			<div>
				<label for="caption"><span>Caption</span></label>
				<input type="text" name="caption" <# if (model.get('type') == 'video') { #>value="{{ model.get('caption') }}"<# } #> />
			</div>

			<div>
				<label for="credit"><span>Credit</span></label>
				<input type="text" name="credit" <# if (model.get('type') == 'video') { #>value="{{ model.get('credit') }}"<# } #> />
			</div>

		</form>
	</script>

	<script type="text/template" id="tmpl-featured-thumb">
		<div class="thumb-container">
			<# if (typeof data.model.get('sizes') !== 'undefined') { #>
				<img src="{{ data.model.get('sizes').medium.url }}" title="Thumbnail: '{{ data.model.get('title') }}'" />
				<input type="hidden" name="attachment" value="{{ data.model.get('id') }}" />
			<# } else if (data.model.get('thumbnail_url')) { #>
				<img src="{{ data.model.get('thumbnail_url') }}" title="Thumbnail for '{{ data.model.get('title') }}'" />
				<input type="hidden" name="thumbnail_url" value="{{ data.model.get('thumbnail_url') }}" />
				<input type="hidden" name="thumbnail_type" value="oembed" />
			<# } #>
			<a href="#" class="remove-thumb">Remove thumbnail</a>
		</div>
	</script>

	<script type="text/template" id="tmpl-featured-remove-featured">
		<h1><?php echo __( 'Are you sure you want to remove featured media from this post?', 'largo' ); ?></h1>
	</script>
<?php }
add_action( 'admin_print_footer_scripts', 'largo_featured_media_templates', 1 );

/**
 * Remove the default featured image meta box from post pages
 */
function largo_remove_featured_image_meta_box() {
	remove_meta_box( 'postimagediv', 'post', 'normal' );
	remove_meta_box( 'postimagediv', 'post', 'side' );
	remove_meta_box( 'postimagediv', 'post', 'advanced' );
}
add_action( 'do_meta_boxes', 'largo_remove_featured_image_meta_box' );

/**
 * Add new featured image meta box to post pages
 */
function largo_add_featured_image_meta_box() {
    add_meta_box(
        'largo_featured_image_metabox',
        __( 'Featured Image', 'largo' ),
        'largo_featured_image_metabox_callback',
        array( 'post' ),
        'side',
        'low'
    );
}
add_action( 'add_meta_boxes', 'largo_add_featured_image_meta_box' );

/**
 * Get post meta in a callback
 *
 * @param WP_Post $post    The current post.
 * @param array   $metabox With metabox id, title, callback, and args elements.
 */
function largo_featured_image_metabox_callback( $post, $metabox ) {
	global $post;

	$has_featured_media = largo_has_featured_media( $post->ID );
	$language = ( ! empty( $has_featured_media ) ) ? 'Edit' : 'Set';

	$checked = 'false' == get_post_meta( $post->ID, 'featured-image-display', true ) ? 'checked="checked"' : "";
	echo wp_nonce_field( basename( __FILE__ ), 'featured_image_display_nonce' );

	echo '<a href="#" id="set-featured-media-button" class="button set-featured-media add_media" data-editor="content" title="' . __( $language . ' Featured Media', 'largo' ) . '"></span> ' . __( $language . ' Featured Media', 'largo' ) . '</a> <span class="spinner" style="display: none;"></span>';

	echo '<p><label class="selectit"><input type="checkbox" value="true" name="featured-image-display"' . $checked .'> ' . __( 'Hide on Single Post display', 'largo' ) . '</label></p>';

	$has_featured_media = largo_has_featured_media( $post->ID );
	$language = ( ! empty( $has_featured_media ) ) ? 'Edit' : 'Set';
}

/**
 * Save data from meta box
 */
function largo_save_featured_media_data( $post_id, $post ) {

	// Verify the nonce before proceeding
	if ( !isset( $_POST['featured_image_display_nonce'] ) || !wp_verify_nonce( $_POST['featured_image_display_nonce'], basename( __FILE__ ) ) ) {
		return $post_id;
	}

	// Get the post type object
	$post_type = get_post_type_object( $post->post_type );

	// Check if the current user has permission to edit the post
	if ( !current_user_can( $post_type->cap->edit_post, $post_id ) ) {
		return $post_id;
	}

	// Get the posted data and sanitize it for use as an HTML class
	$new_meta_value = ( isset( $_POST['featured-image-display'] ) ? sanitize_html_class( $_POST['featured-image-display'] ) : '' );

	// Get the meta key
	$meta_key = 'featured-image-display';

	// Get the meta value of the custom field key
	$meta_value = get_post_meta( $post_id, $meta_key, true );

	/*
	 * If the checkbox was checked, update the meta_value, but save it as 'false' for compatibility with older Largo versions (<.5.5)
	 * If the checkbox was unchecked, delete the meta_value
	 */
	 if ( $new_meta_value && 'true' == $new_meta_value && '' == $meta_value ) {
		 add_post_meta( $post_id, $meta_key, 'false', true );
	} elseif ( empty( $new_meta_value ) ) {
		delete_post_meta( $post_id, $meta_key );
	}
}
add_action( 'save_post', 'largo_save_featured_media_data', 10, 2 );

/**
 * AJAX functions
 */

/**
 * Read the `featured_media` meta for a given post. Expects array $_POST['data']
 * with an `id` key corresponding to post ID to look up.
 */
function largo_featured_media_read() {
	if ( !empty( $_POST['data'] ) ) {
		$data = json_decode( stripslashes( $_POST['data'] ), true );
		$ret = largo_get_featured_media( $data['id'] );

		// Otherwise, check for `featured_media` post meta
		if ( !empty( $ret ) ) {
			print json_encode( $ret );
			wp_die();
		}

		// No featured thumbnail and not `featured_media`, so just return
		// an array with the post ID
		print json_encode( array( 'id' => $data['id'] ) );
		wp_die();
	}
}
add_action( 'wp_ajax_largo_featured_media_read', 'largo_featured_media_read' );

/**
 * Save `featured_media` post meta. Expects array $_POST['data'] with at least
 * an `id` key corresponding to the post ID that needs meta saved.
 */
function largo_featured_media_save() {
	if ( !empty( $_POST['data'] ) ) {
		$data = json_decode( stripslashes( $_POST['data'] ), true );

		// If an attachment ID is present, update the post thumbnail/featured image
		if ( !empty( $data['attachment'] ) ) {
			set_post_thumbnail( $data['id'], $data['attachment'] );
		} else {
			delete_post_thumbnail( $data['id'] );
		}
		// Get rid of the old youtube_url while we're saving
		$youtube_url = get_post_meta( $data['id'], 'youtube_url', true );
		if ( !empty( $youtube_url ) ) {
			delete_post_meta($data['id'], 'youtube_url');
		}
		// Set the featured image for embed or oembed types
		if ( isset( $data['thumbnail_url'] ) && isset( $data['thumbnail_type'] ) && $data['thumbnail_type'] == 'oembed' ) {
			$thumbnail_id = largo_media_sideload_image( $data['thumbnail_url'], null );
		} else if ( isset( $data['attachment'] ) ) {
			$thumbnail_id = $data['attachment'];
		}

		// If featured media is a gallery, use the first image as the representative thumbnail
		if ( $data['type'] == 'gallery' ) {
			$thumbnail_id = $data['gallery'][0];
		}

		if ( isset( $thumbnail_id ) ) {
			update_post_meta( $data['id'], '_thumbnail_id', $thumbnail_id );
			$data['attachment_data'] = wp_prepare_attachment_for_js( $thumbnail_id );
		}

		// Don't save the post ID in post meta
		$save = $data;
		unset( $save['id'] );

		// Save what's sent over the wire as `featured_media` post meta
		$ret = update_post_meta( $data['id'], 'featured_media', $save );

		print json_encode( $data );
		wp_die();
	}
}
add_action( 'wp_ajax_largo_featured_media_save', 'largo_featured_media_save' );

/**
 * Saves the option that determines whether a featured image should be displayed
 * at the top of the post page or not.
 */
function largo_save_featured_image_display() {
	if ( !empty( $_POST['data'] ) ) {
		$data = json_decode( stripslashes( $_POST['data'] ), true );

		$post_ID = (int) $data['id'];
		$post_type = get_post_type( $post_ID );
		$post_status = get_post_status( $post_ID );

		if ( $post_type && isset( $data['featured-image-display'] ) && $data['featured-image-display'] == 'on') {
			update_post_meta( $post_ID, 'featured-image-display', 'false' );
		} else {
			delete_post_meta( $post_ID, 'featured-image-display' );
		}
		print json_encode( $data );
		wp_die();
	}
}
add_action( 'wp_ajax_largo_save_featured_image_display', 'largo_save_featured_image_display' );

/**
 * When a URL is typed/pasted into the url field of the featured video view,
 * this function tries to fetch the oembed information for that video.
 */
function largo_fetch_video_oembed() {
	if ( !empty( $_POST['data'] ) ) {
		$data = json_decode( stripslashes( $_POST['data'] ), true );

		require_once( ABSPATH . WPINC . '/class-oembed.php' );
		$oembed = _wp_oembed_get_object();
		$url = $data['url'];
		$provider = $oembed->get_provider($url);
		$data = $oembed->fetch($provider, $url);
		$embed = $oembed->data2html($data, $url);
		$ret = array_merge( array( 'embed' => $embed ), (array) $data );
		print json_encode( $ret );
		wp_die();
	}
}
add_action( 'wp_ajax_largo_fetch_video_oembed', 'largo_fetch_video_oembed' );

/**
 * Add post classes to indicate whether a post has featured media and what type it is
 *
 * @since 0.5.2
 */
function largo_featured_media_post_classes( $classes ) {
	global $post;

	$featured = largo_get_featured_media( $post->ID );
	if ( !empty( $featured ) ) {
		$classes = array_merge( $classes, array(
			'featured-media',
			'featured-media-' . $featured['type']
		));
	}

	return $classes;
}
add_filter( 'post_class', 'largo_featured_media_post_classes' );

/**
 * Determines what type of hero image/video a single post should use
 * and returns a class that gets added to its container div
 *
 * @since 0.4
 */
if ( ! function_exists( 'largo_hero_class' ) ) {
	function largo_hero_class( $post_id, $echo = TRUE ) {
		$hero_class = 'is-empty';
		$featured_media = ( largo_has_featured_media( $post_id ) ) ? largo_get_featured_media( $post_id ) : array();
		$type = ( isset( $featured_media['type'] ) ) ? $featured_media['type'] : false;

		if ( get_post_meta( $post_id, 'youtube_url', true ) || $type == 'video' ) {
			$hero_class = 'is-video';
		} else if ( $type == 'gallery' ) {
			$hero_class = 'is-gallery';
		} else if ( $type == 'embed-code' ) {
			$hero_class = 'is-embed';
		} else if ( has_post_thumbnail( $post_id ) || $type == 'image') {
			$hero_class = 'is-image';
		}

		if ( $echo ) {
			echo $hero_class;
		} else {
			return $hero_class;
		}
	}
}
