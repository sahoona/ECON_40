<?php
/**
 * SEO functions
 *
 * @package GP_Child_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Prevent dark mode flash
function gp_prevent_dark_mode_flash() {
    ?>
    <style>
    html.dark-mode-active {
        background-color: #18191a !important;
        color: #e4e6eb !important;
    }
    </style>
    <script>
    (function() {
        try {
            var preference = localStorage.getItem('darkMode');
            if (preference === 'true') {
                document.documentElement.classList.add('dark-mode-active');
            } else if (preference === 'false') {
                document.documentElement.classList.remove('dark-mode-active');
            }
        } catch (e) {
            console.error('Error applying initial dark mode preference:', e);
        }
    })();
    </script>
    <?php
}
add_action('wp_head', 'gp_prevent_dark_mode_flash', 0);

function gp_add_viewport_meta() {
    echo '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">' . "\n";
    echo '<meta http-equiv="X-UA-Compatible" content="IE=edge">' . "\n";
    echo '<meta name="format-detection" content="telephone=no">' . "\n";
}
add_action('wp_head', 'gp_add_viewport_meta', 0);

function gp_add_faq_schema() {
    if ( ! is_singular('post') ) {
        return;
    }

    $post = get_queried_object();
    if ( ! $post || ! isset($post->post_content) ) {
        return;
    }

    $content = $post->post_content;

    // Use a more specific and unique class to avoid conflicts
    if ( strpos($content, 'gp-faq-section') === false ) {
        return;
    }

    // Suppress DOMDocument warnings for malformed HTML
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    // Load content with UTF-8 encoding
    $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $faq_container = $xpath->query("//div[contains(@class, 'gp-faq-section')]")->item(0);

    if ( ! $faq_container ) {
        return;
    }

    // Assuming H3 for questions, but could be adapted for other heading levels
    $questions = $xpath->query(".//h3", $faq_container);
    $qa_pairs = [];

    foreach ($questions as $question_node) {
        $question_text = trim($question_node->textContent);
        $answer_node = null;
        $next_sibling = $question_node->nextSibling;

        // Find the next element sibling
        while ($next_sibling && $next_sibling->nodeType !== XML_ELEMENT_NODE) {
            $next_sibling = $next_sibling->nextSibling;
        }

        if ($next_sibling) {
            $answer_node = $next_sibling;
        }

        if ($question_text && $answer_node) {
            $answer_html = trim($dom->saveHTML($answer_node));

            $qa_pairs[] = [
                '@type'          => 'Question',
                'name'           => $question_text,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $answer_html,
                ],
            ];
        }
    }

    if (empty($qa_pairs)) {
        return;
    }

    $schema = [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $qa_pairs,
    ];

    echo "\n" . '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}
add_action('wp_head', 'gp_add_faq_schema', 20);

