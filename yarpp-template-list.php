<?php
/*
YARPP Template: Background Image Layout
Description: A template that uses the post thumbnail as a background for each related post item.
Author: Jules
*/
?>

<?php if (have_posts()): ?>
<section class="related-posts-container">
    <ol class="related-posts-list">
        <?php while (have_posts()) : the_post(); ?>
            <?php
            $thumbnail_url = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'large') : '';
            $item_style = $thumbnail_url ? 'style="background-image: url(' . esc_url($thumbnail_url) . ');"' : '';
            ?>
            <li class="related-posts-item" <?php echo $item_style; ?>>
                <a href="<?php the_permalink() ?>" rel="bookmark" class="related-posts-link">
                    <div class="related-posts-content">
                        <h3 class="related-posts-post-title"><?php the_title(); ?></h3>
                        <div class="related-posts-meta">
                            <span class="related-posts-date"><?php echo get_the_date(); ?></span>
                            <span class="related-posts-separator">·</span>
                            <span class="related-posts-reading-time"><?php echo gp_get_reading_time( get_the_ID() ); ?></span>
                        </div>
                    </div>
                </a>
            </li>
        <?php endwhile; ?>
    </ol>
</section>
<?php else: ?>
<!-- No related posts found -->
<?php endif; ?>
