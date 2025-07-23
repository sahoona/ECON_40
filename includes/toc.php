<?php
/**
 * Table of Contents functions
 *
 * @package GP_Child_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


// Insert TOC into post
function gp_insert_toc() {
    if (!is_single()) return;
    echo '<nav id="gp-toc-container" aria-label="Table of Contents" role="navigation">' .
            '<h2 class="gp-toc-title">Table of Contents <span class="gp-toc-toggle" aria-label="Toggle table of contents">[Hide]</span></h2>' .
            '<ol class="gp-toc-list" role="list"></ol>' .
         '</nav>';
}
