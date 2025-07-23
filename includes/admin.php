<?php
/**
 * Admin functions
 *
 * @package GP_Child_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Register meta box
function gp_register_meta_box() {
	add_meta_box( 'gp-post-options', 'GP 테마 설정', 'gp_meta_box_callback', 'post', 'side', 'high' );
}
add_action( 'add_meta_boxes', 'gp_register_meta_box' );

// Meta box callback
function gp_meta_box_callback( $post ) {
	wp_nonce_field( 'gp_save_meta_box_data', 'gp_meta_box_nonce' );

	$is_checked = ( 'off' !== get_post_meta( $post->ID, '_gp_show_reactions', true ) );
	$reactions = ['like' => 'Like', 'love' => 'Love', 'helpful' => 'Helpful', 'fun' => 'Interesting'];
	$total_score = get_post_meta($post->ID, '_gp_star_rating_total_score', true) ?: 0;
	$vote_count = get_post_meta($post->ID, '_gp_star_rating_vote_count', true) ?: 0;
	$average_rating = $vote_count > 0 ? round($total_score / $vote_count, 2) : 0;
	?>
	<p><label><input type="checkbox" name="gp_show_reactions" <?php checked( $is_checked ); ?> /> <strong>글 반응 섹션 표시</strong></label></p><hr>
	<p><strong>반응 수치 직접 조절:</strong></p>
	<?php foreach ( $reactions as $key => $label ) :
		$count = get_post_meta($post->ID, '_gp_reaction_' . $key, true); ?>
		<p style="display:flex; justify-content:space-between; align-items:center;">
			<label for="gp_reaction_<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?>:</label>
			<input type="number" id="gp_reaction_<?php echo esc_attr($key); ?>" name="gp_reaction_<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($count ?: 0); ?>" min="0" style="width: 70px;" />
		</p>
	<?php endforeach; ?>
	<hr>
	<p><strong>별점 평가 조절:</strong></p>
	<p style="display:flex; justify-content:space-between; align-items:center;">
		<label for="gp_star_average_rating">평균 별점:</label>
		<input type="number" step="0.1" id="gp_star_average_rating" name="gp_star_average_rating" value="<?php echo esc_attr($average_rating); ?>" min="0" max="5" style="width: 70px;" />
	</p>
	<p style="display:flex; justify-content:space-between; align-items:center;">
		<label for="gp_star_vote_count">총 투표 수:</label>
		<input type="number" id="gp_star_vote_count" name="gp_star_vote_count" value="<?php echo esc_attr($vote_count); ?>" min="0" style="width: 70px;" />
	</p>
	<?php
}

// Save meta box data
function gp_save_meta_box_data( $post_id ) {
	if ( ! isset( $_POST['gp_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['gp_meta_box_nonce'], 'gp_save_meta_box_data' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	update_post_meta( $post_id, '_gp_show_reactions', isset( $_POST['gp_show_reactions'] ) ? 'on' : 'off' );
	foreach ( ['like', 'love', 'helpful', 'fun'] as $key ) {
		if ( isset( $_POST['gp_reaction_' . $key] ) ) {
			update_post_meta( $post_id, '_gp_reaction_' . $key, max(0, intval($_POST['gp_reaction_' . $key])) );
		}
	}

	if ( isset( $_POST['gp_star_average_rating'] ) && isset( $_POST['gp_star_vote_count'] ) ) {
		$avg = floatval($_POST['gp_star_average_rating']);
		$count = intval($_POST['gp_star_vote_count']);
		update_post_meta($post_id, '_gp_star_rating_total_score', round($avg * $count));
		update_post_meta($post_id, '_gp_star_rating_vote_count', $count);
	}
}
add_action( 'save_post', 'gp_save_meta_box_data' );

// Table of Contents Meta Box
function gp_toc_settings_meta_box() {
    add_meta_box(
        'gp_toc_options_mb',
        'Table of Contents Settings',
        'gp_toc_options_meta_box_html',
        'post',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'gp_toc_settings_meta_box');

function gp_toc_options_meta_box_html($post) {
    wp_nonce_field('gp_toc_save_meta_box_data', 'gp_toc_meta_box_nonce');
    $levels = ['h2', 'h3', 'h4', 'h5', 'h6'];
    $defaults = ['h2' => 'on', 'h3' => 'on', 'h4' => '', 'h5' => '', 'h6' => ''];
    echo '<p>Select heading levels to include in TOC:</p>';
    foreach ($levels as $level) {
        $meta_key = '_gp_toc_include_' . $level;
        $checked_value = get_post_meta($post->ID, $meta_key, true);
        if ($checked_value === '') {
             $is_checked = ($defaults[$level] === 'on');
        } else {
             $is_checked = ($checked_value === 'on');
        }
        echo '<p>';
        echo '<input type="checkbox" id="gp_toc_include_' . esc_attr($level) . '" name="' . esc_attr($meta_key) . '" value="on" ' . checked($is_checked, true, false) . ' />';
        echo '<label for="gp_toc_include_' . esc_attr($level) . '">Include ' . strtoupper($level) . ' headings</label>';
        echo '</p>';
    }
}

function gp_toc_save_meta_box_data($post_id) {
    if (!isset($_POST['gp_toc_meta_box_nonce']) || !wp_verify_nonce($_POST['gp_toc_meta_box_nonce'], 'gp_toc_save_meta_box_data')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    $levels = ['h2', 'h3', 'h4', 'h5', 'h6'];
    foreach ($levels as $level) {
        $meta_key = '_gp_toc_include_' . $level;
        if (isset($_POST[$meta_key]) && $_POST[$meta_key] === 'on') {
            update_post_meta($post_id, $meta_key, 'on');
        } else {
            update_post_meta($post_id, $meta_key, 'off');
        }
    }
}
add_action('save_post', 'gp_toc_save_meta_box_data');
