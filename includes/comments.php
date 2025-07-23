<?php
/**
 * Comments customizations.
 *
 * @package    GP_Child_Theme
 * @version    22.7.16
 * @author     sh k & GP AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// -----------------------------------------------------------------------------
// Comments Customization
// -----------------------------------------------------------------------------

/**
 * Master function to hook all comment-related customizations.
 */
function gp_comments_customizations() {
    add_filter( 'comment_form_default_fields', 'gp_modify_comment_fields' );
    add_filter( 'comment_form_defaults', 'gp_customize_comment_form' );
    add_filter( 'preprocess_comment', 'gp_handle_anonymous_comment' );
    add_action( 'wp_insert_comment', 'gp_remove_anonymous_comment_filter', 10, 2 );
    add_filter( 'wp_list_comments_args', 'gp_customize_comments_display' );
}
add_action( 'after_setup_theme', 'gp_comments_customizations' );

/**
 * Customizes the display of comments (callback, depth, order).
 *
 * @param array $args Arguments for wp_list_comments().
 * @return array Modified arguments.
 */
function gp_customize_comments_display( $args ) {
    $args['callback'] = 'gp_custom_comment_html';
    $args['max_depth'] = 2; // Limits comment threading to two levels.
    $args['reverse_top_level'] = false; // Ensures newest comments are at the top.
    return $args;
}

/**
 * Generates the custom HTML for each comment.
 *
 * @param object $comment The comment object.
 * @param array  $args    An array of arguments.
 * @param int    $depth   The depth of the comment.
 */
function gp_custom_comment_html( $comment, $args, $depth ) {
    ?>
    <li <?php comment_class( empty( $args['has_children'] ) ? '' : 'parent' ) ?> id="comment-<?php comment_ID() ?>">
        <article id="div-comment-<?php comment_ID() ?>" class="comment-body">
            <div class="comment-author vcard">
                <?php if ( 0 != $args['avatar_size'] ) echo get_avatar( $comment, $args['avatar_size'] ); ?>
                <div class="comment-author-info">
                    <span class="fn"><?php echo get_comment_author_link(); ?></span>
                    <span class="comment-meta commentmetadata">
                        <a href="<?php echo esc_url( get_comment_link( $comment->comment_ID ) ); ?>">
                            <?php
                            // Translators: 1: date, 2: time.
                            printf(
                                '%1$s at %2$s',
                                get_comment_date( 'F j, Y' ), // e.g., "September 1, 2023"
                                date( 'g:i a', get_comment_time( 'U' ) ) // e.g., "3:30 pm"
                            );
                            ?>
                        </a>
                        <?php edit_comment_link( '(Edit)', ' &nbsp;&nbsp;', '' ); ?>
                    </span>
                </div>
            </div>

            <?php if ( '0' == $comment->comment_approved ) : ?>
                <p class="comment-awaiting-moderation">Your comment is awaiting moderation.</p>
            <?php endif; ?>

            <div class="comment-content">
                <?php comment_text(); ?>
            </div>

            <div class="reply">
                <?php
                comment_reply_link(
                    array_merge(
                        $args,
                        array(
                            'add_below' => 'div-comment',
                            'depth'     => $depth,
                            'max_depth' => $args['max_depth'],
                            'reply_text'=> 'Reply',
                        )
                    )
                );
                ?>
            </div>
        </article>
    <?php
}

/**
 * Modifies the default comment form fields (Name, Email, Website).
 *
 * @param array $fields The default comment fields.
 * @return array The modified fields.
 */
function gp_modify_comment_fields( $fields ) {
    $commenter = wp_get_current_commenter();
    $req       = get_option( 'require_name_email' );
    $aria_req  = ( $req ? " aria-required='true'" : '' );

    $fields['author'] = sprintf(
        '<p class="comment-form-author"><label for="author">%s%s</label> <input id="author" name="author" type="text" value="%s" size="30"%s /></p>',
        'Name',
        ( $req ? ' <span class="required">*</span>' : '' ),
        esc_attr( $commenter['comment_author'] ),
        $aria_req
    );

    $fields['email'] = sprintf(
        '<p class="comment-form-email"><label for="email">%s%s</label> <input id="email" name="email" type="email" value="%s" size="30" aria-describedby="email-notes"%s /></p>',
        'Email',
        ( $req ? ' <span class="required">*</span>' : '' ),
        esc_attr( $commenter['comment_author_email'] ),
        $aria_req
    );

    $fields['url'] = sprintf(
        '<p class="comment-form-url"><label for="url">%s</label><input id="url" name="url" type="url" value="%s" size="30" /></p>',
        'Website',
        esc_attr( $commenter['comment_author_url'] )
    );

    $fields['cookies'] = sprintf(
        '<p class="comment-form-cookies-consent"><input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes"%s /> <label for="wp-comment-cookies-consent">%s</label></p>',
        ( isset( $_COOKIE['comment_author_' . COOKIEHASH] ) ? ' checked="checked"' : '' ),
        'Save my name, email, and website in this browser for the next time I comment.'
    );

    return $fields;
}


/**
 * Customizes the overall comment form (notes, submit button, etc.).
 *
 * @param array $defaults The default comment form arguments.
 * @return array The modified arguments.
 */
function gp_customize_comment_form( $defaults ) {
    $defaults['comment_field'] = sprintf(
        '<p class="comment-form-comment"><label for="comment">%s</label><textarea id="comment" name="comment" cols="45" rows="8" maxlength="65525" required="required"></textarea></p>',
        'Comment'
    );
    $defaults['comment_notes_before'] = '<p class="comment-notes">Your email address will not be published.</p>';
    $defaults['comment_notes_after'] = ''; // Removes the "You may use these HTML tags..." message.
    $defaults['label_submit'] = 'Post Comment';
    $defaults['title_reply'] = 'Leave a Reply';
    $defaults['title_reply_to'] = 'Leave a Reply to %s';
    $defaults['cancel_reply_link'] = 'Cancel Reply';
    $defaults['id_form'] = 'commentform';
    $defaults['id_submit'] = 'submit';
    $defaults['title_reply_before'] = '<h3 id="reply-title" class="comment-reply-title">';
    $defaults['title_reply_after'] = '</h3>';

    return $defaults;
}

/**
 * Handles anonymous comments by clearing the email field if a privacy checkbox is ticked.
 *
 * @param array $commentdata Comment data.
 * @return array Modified comment data.
 */
function gp_handle_anonymous_comment( $commentdata ) {
    if ( isset( $_POST['wp-comment-email-privacy'] ) && $_POST['wp-comment-email-privacy'] === 'true' ) {
        // Temporarily disable the email requirement for this specific comment.
        add_filter( 'pre_option_require_name_email', '__return_false', 9999 );
        $commentdata['comment_author_email'] = '';
    }
    return $commentdata;
}

/**
 * Removes the temporary filter after an anonymous comment is inserted.
 *
 * @param int    $id      The comment ID.
 * @param object $comment The comment object.
 */
function gp_remove_anonymous_comment_filter( $id, $comment ) {
    if ( isset( $_POST['wp-comment-email-privacy'] ) && $_POST['wp-comment-email-privacy'] === 'true' ) {
        remove_filter( 'pre_option_require_name_email', '__return_false', 9999 );
    }
}
