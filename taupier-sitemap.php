<?php
/**
 * taupier-sitemap.php
 *
 * Génère deux sitemaps XML pour le CPT "taupier" et ses taxonomies.
 * Ne dépend plus du flush dans init ; utilise activation hook.
 * Ajoute des en-têtes pour éviter le caching par Fastest Cache / SiteGround.
 */

// 1) Rewrite rules sur init
function taupier_add_sitemap_rewrite_rules() {
    add_rewrite_rule('^taupier-sitemap\.xml$', 'index.php?taupier_sitemap=1', 'top');
    add_rewrite_rule('^taupier-categories-sitemap\.xml$', 'index.php?taupier_categories_sitemap=1', 'top');
}
add_action('init', 'taupier_add_sitemap_rewrite_rules');

// 2) Enregistrement des variables de requête
function taupier_register_sitemap_query_vars($vars) {
    $vars[] = 'taupier_sitemap';
    $vars[] = 'taupier_categories_sitemap';
    return $vars;
}
add_filter('query_vars', 'taupier_register_sitemap_query_vars');

// 3) Template redirect pour produire le XML
function taupier_sitemap_template_redirect() {
    // Sitemap des CPT taupiers
    if (get_query_var('taupier_sitemap') == 1) {
        // Têtes pour éviter le cache
        header('Content-Type: application/xml; charset=UTF-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');

        echo '<?xml version="1.0" encoding="UTF-8"?>\n';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n';

        $taupiers = get_posts(array(
            'post_type'      => 'taupier',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        ));

        foreach ($taupiers as $taupier) {
            $loc      = esc_url(get_permalink($taupier->ID));
            $lastmod  = get_post_modified_time('c', true, $taupier->ID);
            echo "\t<url>\n";
            echo "\t\t<loc>$loc</loc>\n";
            echo "\t\t<lastmod>$lastmod</lastmod>\n";
            echo "\t\t<changefreq>monthly</changefreq>\n";
            echo "\t\t<priority>0.8</priority>\n";
            echo "\t</url>\n";
        }

        // Archive principale
        $archive = get_post_type_archive_link('taupier');
        if ($archive) {
            echo "\t<url>\n";
            echo "\t\t<loc>" . esc_url($archive) . "</loc>\n";
            echo "\t\t<changefreq>weekly</changefreq>\n";
            echo "\t\t<priority>0.9</priority>\n";
            echo "\t</url>\n";
        }

        echo '</urlset>';
        exit;
    }

    // Sitemap des taxonomies (catégories & tags)
    if (get_query_var('taupier_categories_sitemap') == 1) {
        header('Content-Type: application/xml; charset=UTF-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');

        echo '<?xml version="1.0" encoding="UTF-8"?>\n';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n';

        // Catégories
        $categories = get_terms(array(
            'taxonomy'   => 'taupier_category',
            'hide_empty' => false,
        ));
        if (!is_wp_error($categories)) {
            foreach ($categories as $cat) {
                $link = get_term_link($cat);
                if (!is_wp_error($link)) {
                    echo "\t<url>\n";
                    echo "\t\t<loc>" . esc_url($link) . "</loc>\n";
                    echo "\t\t<lastmod>" . date('c') . "</lastmod>\n";
                    echo "\t\t<changefreq>weekly</changefreq>\n";
                    echo "\t\t<priority>0.7</priority>\n";
                    echo "\t</url>\n";
                }
            }
        }

        // Tags
        $tags = get_terms(array(
            'taxonomy'   => 'taupier_tag',
            'hide_empty' => false,
        ));
        if (!is_wp_error($tags)) {
            foreach ($tags as $tag) {
                $link = get_term_link($tag);
                if (!is_wp_error($link)) {
                    echo "\t<url>\n";
                    echo "\t\t<loc>" . esc_url($link) . "</loc>\n";
                    echo "\t\t<lastmod>" . date('c') . "</lastmod>\n";
                    echo "\t\t<changefreq>weekly</changefreq>\n";
                    echo "\t\t<priority>0.6</priority>\n";
                    echo "\t</url>\n";
                }
            }
        }

        echo '</urlset>';
        exit;
    }
}
add_action('template_redirect', 'taupier_sitemap_template_redirect', 0);