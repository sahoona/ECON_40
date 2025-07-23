<?php
/**
 * Post features
 *
 * @package GP_Child_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function gp_render_top_meta_bar() {
    echo '<div class="gp-top-meta-bar-container"><div class="gp-top-meta-bar">';
    echo '<div class="left-buttons">';
    gp_add_copy_url_button();
    echo '</div>';
    echo '<div class="breadcrumb-lang-wrapper">';
    gp_language_switcher_output();
    echo '</div></div></div>';
}

function gp_add_copy_url_button() {
    echo '<button class="gp-copy-url-btn" data-post-id="' . get_the_ID() . '" title="' . esc_attr(get_the_title()) . ' ' . __('Copy URL', 'gp-child-theme') . '" aria-label="' . __('Copy the URL of the current post to the clipboard', 'gp-child-theme') . '" type="button"><span class="sr-only">' . __('Copy URL', 'gp-child-theme') . '</span></button>';
}

function gp_breadcrumb_output() {
    if ( is_singular() ) {
        echo '<div class="gp-top-meta-bar-container"><div class="gp-top-meta-bar">';
        echo '<div class="left-buttons">';
        gp_add_copy_url_button();
        echo '</div>';
    }

    $categories = get_the_category();
    if ( empty( $categories ) ) {
        if (is_singular()) { echo '</div></div>'; }
        return;
    }

    echo '<div class="gp-post-category">';
    $cat_id = $categories[0]->term_id;
    $parent_ids = array_reverse( get_ancestors( $cat_id, 'category' ) );
    foreach ( $parent_ids as $parent_id ) {
        echo '<a href="' . esc_url( get_category_link( $parent_id ) ) . '">' . esc_html(get_cat_name( $parent_id )) . '</a> <span class="breadcrumb-separator">»</span> ';
    }
    echo '<a href="' . esc_url( get_category_link( $cat_id ) ) . '">' . esc_html(get_cat_name( $cat_id )) . '</a>';
    echo '</div>';

    if ( is_singular() ) {
        gp_language_switcher_output();
        echo '</div></div>';
    }
}

function gp_home_breadcrumb_output() {
    gp_breadcrumb_output();
}

function gp_language_switcher_output() {
    if (!function_exists('pll_the_languages')) {
        return;
    }

    $translations = pll_the_languages(['raw' => 1, 'hide_if_empty' => false]);

    if (empty($translations) || !is_array($translations)) {
        return;
    }

    $current_lang_details = null;
    $current_lang_slug_for_button = 'LANG';

    foreach ($translations as $lang_item) {
        if (!empty($lang_item['current_lang'])) {
            $current_lang_details = $lang_item;
            break;
        }
    }

    if (!$current_lang_details && !empty($translations)) {
        $current_lang_details = reset($translations);
        $first_lang_key = array_key_first($translations);
        if ($first_lang_key !== null) {
            $translations[$first_lang_key]['current_lang'] = true;
        }
    }

    if (!$current_lang_details) {
        return;
    }

    $button_text_slug = strtoupper($current_lang_details['slug']);
    $current_lang_slug_for_button = ($button_text_slug === 'KO') ? 'KR' : $button_text_slug;

    echo '<div class="gp-language-switcher" id="gp-language-switcher">';
    $aria_label_select_language = function_exists('__') ? __('Select language', 'gp_theme') : 'Select language';
    echo '<button id="gp-lang-switcher-button" class="gp-language-button" aria-haspopup="true" aria-expanded="false" aria-controls="gp-lang-switcher-list" aria-label="' . esc_attr($aria_label_select_language) . '">';
    echo esc_html($current_lang_slug_for_button);
    echo '<span class="dropdown-icon" aria-hidden="true">▼</span>';
    echo '</button>';
    echo '<ul id="gp-lang-switcher-list" class="language-list" role="listbox" aria-labelledby="gp-lang-switcher-button" hidden>';

    foreach ($translations as $lang) {
        $lang_name_attr = esc_attr($lang['name']);
        $lang_slug_attr = esc_attr($lang['slug']);
        $lang_code_display = strtoupper($lang_slug_attr);
        if ($lang_code_display === 'KO') {
            $lang_code_display = 'KR';
        }
        $list_item_display_text = esc_html($lang_code_display);
        $is_current = !empty($lang['current_lang']);

        echo '<li class="lang-item' . ($is_current ? ' current-lang' : '') . '" role="option" aria-selected="' . ($is_current ? 'true' : 'false') . '" lang="' . $lang_slug_attr . '">';

        if ($is_current) {
            $current_lang_aria_label = function_exists('__') ? sprintf(__('Current language: %s', 'gp_theme'), $lang_name_attr) : "Current language: {$lang_name_attr}";
            echo '<span class="lang-text" aria-label="' . esc_attr($current_lang_aria_label) . '">' . $list_item_display_text . '</span>';
        } else {
            $switch_to_lang_aria_label = function_exists('__') ? sprintf(__('Switch to %s', 'gp_theme'), $lang_name_attr) : "Switch to {$lang_name_attr}";
            echo '<a href="' . esc_url($lang['url']) . '" hreflang="' . $lang_slug_attr . '" lang="' . $lang_slug_attr . '" class="lang-link" aria-label="' . esc_attr($switch_to_lang_aria_label) . '">' . $list_item_display_text . '</a>';
        }
        echo '</li>';
    }
    echo '</ul>';
    echo '</div>';
}

function gp_featured_image_output() {
    if ( ! has_post_thumbnail() ) return;

    $is_singular_page = is_singular();
    $post_id = get_the_ID();
    $thumbnail_id = get_post_thumbnail_id($post_id);
    $image_alt = get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true);
    if (empty($image_alt)) {
        $image_alt = get_the_title($post_id);
    }

    $image_html = '';
    if ($is_singular_page) {
        // Singular page logic (existing logic can be kept or adjusted)
        $image_size_to_use = 'full';
        $fetch_priority_attr = ' fetchpriority="high"';
        $image_src_array = wp_get_attachment_image_src($thumbnail_id, $image_size_to_use);
        if ($image_src_array) {
            $image_url = $image_src_array[0];
            $width = $image_src_array[1];
            $height = $image_src_array[2];
            $srcset = wp_get_attachment_image_srcset($thumbnail_id, $image_size_to_use);
            $sizes = '(max-width: 940px) 100vw, 940px';
            $image_html = sprintf(
                '<img src="%s" srcset="%s" sizes="%s" alt="%s" width="%d" height="%d" class="attachment-%s size-%s wp-post-image"%s>',
                esc_url($image_url), esc_attr($srcset), esc_attr($sizes), esc_attr($image_alt),
                esc_attr($width), esc_attr($height), esc_attr($image_size_to_use), esc_attr($image_size_to_use), $fetch_priority_attr
            );
        }
    } else {
        // Non-singular page (homepage/archives) logic to match AJAX handler
        $image_src_data = wp_get_attachment_image_src($thumbnail_id, 'medium_large');
        if ($image_src_data) {
            $image_url = $image_src_data[0];
            $width = $image_src_data[1];
            $height = $image_src_data[2];
            $placeholder_src = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7";
            $image_html = sprintf(
                '<img src="%s" data-src="%s" alt="%s" width="%d" height="%d" class="lazy-load">',
                esc_url($placeholder_src), esc_url($image_url), esc_attr($image_alt),
                esc_attr($width), esc_attr($height)
            );
        }
    }

    $post_time = get_the_time('U');
    $modified_time = get_the_modified_time('U');
    $current_time = current_time('U');
    $is_new = ($current_time - $post_time) < (7 * DAY_IN_SECONDS);
    $is_updated = ($modified_time > $post_time + DAY_IN_SECONDS) && (($current_time - $modified_time) < (7 * DAY_IN_SECONDS));
    $badge_html = '';
    if ($is_new) { $badge_html = '<span class="gp-new-badge">NEW</span>'; }
    elseif ($is_updated) { $badge_html = '<span class="gp-updated-badge">UPDATED</span>'; }

    $final_output_image_html = '';
    if ( !$is_singular_page ) {
        $final_output_image_html = sprintf('<a href="%s" rel="bookmark">%s%s</a>', esc_url(get_permalink()), $badge_html, $image_html);
    } else {
        $final_output_image_html = $badge_html . $image_html;
    }

    printf('<div class="%s">%s</div>', ($is_singular_page ? 'featured-image' : 'post-image'), $final_output_image_html);
}

function gp_meta_after_title() {
    if ( ! is_singular('post') ) return;

    global $post;
    if ( ! is_a( $post, 'WP_Post' ) ) return;

    $author_display_name = get_the_author_meta('display_name', $post->post_author);
    $is_updated = get_the_modified_time('U') > get_the_time('U') + DAY_IN_SECONDS;
    $reading_data = gp_get_reading_time( $post->ID ); // This now returns an array
    $reading_time = $reading_data['time'];
    $word_count = $reading_data['words'];

    echo "<!-- GP_DEBUG: Post ID {$post->ID}, Word Count: {$word_count}, Reading Time: {$reading_time} -->";
    ?>
    <div class="gp-meta-bar-after-title">
        <div class="posted-on-wrapper is-updatable" title="<?php echo $is_updated ? 'Click to see publish date' : ''; ?>">
            <div class="date-primary">
                <?php if ($is_updated) : ?>
                    <span class="date-label">Updated:</span>
                    <time class="entry-date" datetime="<?php echo esc_attr(get_the_modified_date('c')); ?>"><?php echo esc_html(get_the_modified_date('Y.m.d')); ?></time>
                <?php else : ?>
                    <span class="date-label">Published:</span>
                    <time class="entry-date" datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date('Y.m.d')); ?></time>
                <?php endif; ?>
            </div>
            <?php if ($is_updated) : ?>
                <div class="date-secondary"><span class="date-label">Published:</span><time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date('Y.m.d')); ?></time></div>
            <?php endif; ?>
        </div>
        <span class="reading-time-meta" data-tooltip-text="<?php echo number_format($word_count); ?> words"><?php echo $reading_time; ?></span>
        <span class="byline"><span class="author-label">by</span><span class="author-name-no-link"><?php echo esc_html($author_display_name); ?></span></span>
    </div>
    <?php
}

function gp_read_more_btn_output(){
	if ( is_singular() ) return;
	$post_title = the_title_attribute( 'echo=0' );
	$read_more_label = sprintf( 'Read more about %s', $post_title );
	$link_text = 'Read More<span class="sr-only"> about ' . esc_html( $post_title ) . '</span>';
	echo '<div class="read-more-container"><a href="'.esc_url( get_permalink() ).'" class="gp-read-more-btn" aria-label="' . esc_attr( $read_more_label ) . '">' . $link_text . '</a></div>';
}
function gp_add_tags_to_list(){ if(!is_singular()&&has_tag() && function_exists('gp_get_tags_list')){ echo '<div class="list-tags-container">' . gp_get_tags_list() . '</div>'; }}

function gp_add_star_rating_to_list() {
    if ( is_singular() ) return;

    $post_id = get_the_ID();
    $total_score = get_post_meta($post_id, '_gp_star_rating_total_score', true) ?: 0;
    $vote_count = get_post_meta($post_id, '_gp_star_rating_vote_count', true) ?: 0;
    if ($vote_count > 0) {
        $average_rating = round($total_score / $vote_count, 1);
        $rating_text = $vote_count >= 50 ? number_format($average_rating, 1) . " ({$vote_count} votes)" : number_format($average_rating, 1) . " rating";
        ?>
        <div class="gp-list-star-rating">
            <div class="list-stars-wrapper"><span class="list-stars-background"></span><span class="list-stars-foreground" style="width: <?php echo $average_rating / 5 * 100; ?>%;"></span></div>
            <span class="rating-info"><?php echo esc_html($rating_text); ?></span>
        </div>
        <?php
    }
}

function gp_render_post_footer_sections() {
    if ( !is_single() ) return;
    echo '<div class="post-footer-sections">';
    gp_post_reactions_output();
    gp_star_rating_output();
    gp_add_social_share_buttons();
    echo '</div>';
}

function gp_post_reactions_output(){
    if(!is_single() || 'off' === get_post_meta(get_the_ID(),'_gp_show_reactions',true)) return;
    $reactions=['like'=>['label'=>'Like','icon'=>'👍'],'love'=>['label'=>'Love','icon'=>'❤️'],'helpful'=>['label'=>'Helpful','icon'=>'💡'],'fun'=>['label'=>'Interesting','icon'=>'😄']];
    ?><div class="post-reactions-container"><p class="section-label">How was this post?</p><div class="reaction-buttons"><?php foreach($reactions as $key=>$value): $count=get_post_meta(get_the_ID(),'_gp_reaction_'.$key,true);?><button class="reaction-btn" data-reaction="<?php echo esc_attr($key);?>" data-post-id="<?php echo get_the_ID();?>"><span class="reaction-icon"><?php echo $value['icon'];?></span><span class="reaction-label sr-only"><?php echo esc_html($value['label']);?></span><span class="reaction-count"><?php echo $count ? intval($count) : 0;?></span></button><?php endforeach;?></div></div><?php
}

function gp_star_rating_output(){
    if(!is_single()) return;
    $post_id = get_the_ID();
    $total_score = get_post_meta($post_id, '_gp_star_rating_total_score', true) ?: 0;
    $vote_count = get_post_meta($post_id, '_gp_star_rating_vote_count', true) ?: 0;
    $average_rating = $vote_count > 0 ? round($total_score / $vote_count, 1) : 0;
    ?>
    <div class="gp-star-rating-container" data-post-id="<?php echo $post_id; ?>">
        <p class="section-label">Rate this post</p>
        <div class="stars-wrapper" aria-label="<?php printf( __( 'Rating: %s out of 5 stars', 'gp-child-theme' ), $average_rating ); ?>">
            <div class="stars-background" aria-hidden="true"><?php for($i = 1; $i <= 5; $i++) echo '<div class="star" data-rating="'.$i.'" role="button" tabindex="0" aria-label="'.sprintf(__('%d star','gp-child-theme'),$i).'"></div>'; ?></div>
            <div class="stars-foreground" style="width: <?php echo $average_rating / 5 * 100; ?>%;" aria-hidden="true"><?php for($i = 1; $i <= 5; $i++) echo '<div class="star"></div>'; ?></div>
        </div>
        <div class="rating-text" title="<?php printf('%d votes', $vote_count); ?>" data-initial-average="<?php echo number_format($average_rating, 1); ?>">
            <span><?php echo number_format($average_rating, 1); ?></span> / <span>5.0</span>
        </div>
        <?php if ($vote_count > 20): ?><div class="vote-count-display"><?php echo $vote_count; ?> votes</div><?php endif; ?>
        <div class="user-rating-text" aria-live="polite"></div>
        <div class="rating-buttons-container">
            <button class="edit-rating-btn rating-action-btn" aria-label="<?php _e( 'Edit rating', 'gp-child-theme' ); ?>">Edit</button>
            <button class="submit-rating-btn rating-action-btn" aria-label="<?php _e( 'Submit rating', 'gp-child-theme' ); ?>">Submit</button>
        </div>
    </div>
    <?php
}

function gp_add_social_share_buttons(){
    if(!is_single())return;
    $permalink=urlencode(get_permalink());$title=urlencode(get_the_title());
    ?><div class="gp-social-share-container"><p class="section-label">Share this post</p><div class="share-buttons">
    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $permalink;?>" target="_blank" rel="noopener noreferrer" class="social-share-btn facebook" aria-label="Share on Facebook">F</a>
    <a href="https://x.com/intent/tweet?url=<?php echo $permalink;?>&text=<?php echo $title;?>" target="_blank" rel="noopener noreferrer" class="social-share-btn x-btn" aria-label="Share on X">X</a>
    <a href="<?php echo esc_url(get_permalink()); ?>" class="social-share-btn u-btn" aria-label="Copy link to clipboard">U</a>
    </div></div><?php
}

function gppress_tags_before_related() {
    if ( is_single() && has_tag() && function_exists('gp_get_tags_list') ) {
        echo gp_get_tags_list();
    }
}
