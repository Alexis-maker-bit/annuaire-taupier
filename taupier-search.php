<?php
/**
 * Fonctions pour la recherche et le filtrage des taupiers
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enregistre une sidebar pour la page d'archive des taupiers
 */
function register_taupier_sidebar() {
    register_sidebar(array(
        'name'          => 'Sidebar des Taupiers',
        'id'            => 'taupier-archive-sidebar',
        'description'   => 'Widgets pour la page d\'archive des taupiers',
        'before_widget' => '<div class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
}
add_action('widgets_init', 'register_taupier_sidebar');

/**
 * Modifie la requête principale pour les recherches de taupiers
 */
function taupier_modify_search_query($query) {
    // Ne pas modifier les requêtes dans l'admin ou si ce n'est pas la requête principale
    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    // Appliquer ces règles uniquement si c'est une page d'archive de taupiers,
    // ou une taxonomie de taupiers, ou une recherche globale avec post_type=taupier.
    // Cette condition est clé pour ne pas affecter les autres recherches du site.
    $is_taupier_related_page = is_post_type_archive('taupier') || is_tax('taupier_category') || is_tax('taupier_tag');
    $is_taupier_search_param = ($query->is_search() && isset($_GET['post_type']) && $_GET['post_type'] === 'taupier');

    if ($is_taupier_related_page || $is_taupier_search_param) {

        // --- DÉBUT DE LA MODIFICATION CRUCIALE ---
        // S'assurer que le type de publication est TOUJOURS 'taupier' pour ces requêtes
        $query->set('post_type', 'taupier');
        // --- FIN DE LA MODIFICATION CRUCIALE ---

        $meta_query = array('relation' => 'AND'); // Pour combiner les conditions sur les champs personnalisés

        // 1. Recherche par ville
        if (isset($_GET['taupier_ville']) && !empty($_GET['taupier_ville'])) {
            $ville = sanitize_text_field($_GET['taupier_ville']);
            $meta_query[] = array(
                'key'     => '_taupier_ville',
                'value'   => $ville,
                'compare' => 'LIKE', // Recherche partielle pour plus de flexibilité
            );
        }

        // 2. Recherche par code postal
        if (isset($_GET['taupier_cp']) && !empty($_GET['taupier_cp'])) {
            $code_postal = sanitize_text_field($_GET['taupier_cp']);
            $meta_query[] = array(
                'key'     => '_taupier_code_postal',
                'value'   => $code_postal,
                'compare' => 'LIKE', // Peut être '=' si tu veux une correspondance exacte
            );
        }

        // 3. Recherche textuelle (nom, description, zone d'intervention, ville, code postal)
        // Ceci remplace la recherche standard 's' pour les taupiers afin d'inclure les champs personnalisés
        if (isset($_GET['s']) && !empty($_GET['s'])) {
            $search_term = sanitize_text_field($_GET['s']);

            // Si la recherche textuelle 's' est la seule requête ou si on veut l'appliquer globalement
            if (empty($_GET['taupier_ville']) && empty($_GET['taupier_cp']) && empty($_GET['taupier_region'])) {
                // Pour étendre la recherche 's' aux custom fields, on réinitialise 's'
                // et on passe le terme via une variable custom pour le filtre posts_where
                $query->set('s', '');
                $query->set('taupier_custom_search_term', $search_term);
            } else {
                 // Si d'autres filtres sont présents (ville, cp, region), on ajoute une OR clause à la meta_query
                 $search_meta_query = array(
                     'relation' => 'OR',
                     array(
                         'key'     => '_taupier_zone',
                         'value'   => $search_term,
                         'compare' => 'LIKE',
                     ),
                     array(
                         'key'     => '_taupier_ville',
                         'value'   => $search_term,
                         'compare' => 'LIKE',
                     ),
                     array(
                         'key'     => '_taupier_code_postal',
                         'value'   => $search_term,
                         'compare' => 'LIKE',
                     ),
                 );
                 // On combine cette recherche OR avec la meta_query principale (qui est AND)
                 $meta_query[] = $search_meta_query;
            }
        }

        // Appliquer la meta_query si elle contient des conditions
        if (count($meta_query) > 1) { // Si des conditions ont été ajoutées en plus de la relation 'AND'
            $current_meta_query = $query->get('meta_query');
            if (!empty($current_meta_query)) {
                $meta_query = array_merge($current_meta_query, $meta_query);
                $meta_query['relation'] = 'AND';
            }
            $query->set('meta_query', $meta_query);
        }

        // Filtrage par région si spécifié (existant)
        if (isset($_GET['taupier_region']) && !empty($_GET['taupier_region'])) {
            $tax_query = $query->get('tax_query');
            if (!is_array($tax_query)) {
                $tax_query = array();
            }

            $tax_query[] = array(
                'taxonomy' => 'taupier_region',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($_GET['taupier_region']),
            );

            $query->set('tax_query', $tax_query);
        }

        // Tri par défaut par titre
        if (!isset($_GET['orderby'])) {
            $query->set('orderby', 'title');
            $query->set('order', 'ASC');
        }
    }
}
add_action('pre_get_posts', 'taupier_modify_search_query');

/**
 * Hook to add custom search to post title and content and custom fields if 's' is used.
 * This extends the default WordPress search beyond just title/content.
 */
function taupier_search_by_meta_and_title($where, $query) {
    global $wpdb;
    // Utilise notre variable custom pour la recherche étendue
    $search_term = $query->get('taupier_custom_search_term');

    // S'applique uniquement si notre variable custom est définie ET que c'est la requête principale
    if ($search_term && $query->is_main_query()) {
        // Nettoie la clause WHERE par défaut si elle existe
        $where = '';

        // Construit notre propre clause WHERE incluant les meta_keys
        $where .= " AND (";
        $where .= "{$wpdb->posts}.post_title LIKE '%" . esc_sql($wpdb->esc_like($search_term)) . "%'";
        $where .= " OR {$wpdb->posts}.post_content LIKE '%" . esc_sql($wpdb->esc_like($search_term)) . "%'";
        $where .= " OR EXISTS ( SELECT 1 FROM {$wpdb->postmeta} WHERE post_id = {$wpdb->posts}.ID AND meta_key = '_taupier_zone' AND meta_value LIKE '%" . esc_sql($wpdb->esc_like($search_term)) . "%' )";
        $where .= " OR EXISTS ( SELECT 1 FROM {$wpdb->postmeta} WHERE post_id = {$wpdb->posts}.ID AND meta_key = '_taupier_ville' AND meta_value LIKE '%" . esc_sql($wpdb->esc_like($search_term)) . "%' )";
        $where .= " OR EXISTS ( SELECT 1 FROM {$wpdb->postmeta} WHERE post_id = {$wpdb->posts}.ID AND meta_key = '_taupier_code_postal' AND meta_value LIKE '%" . esc_sql($wpdb->esc_like($search_term)) . "%' )";
        $where .= ")";
    }
    return $where;
}
// Utilisation d'une priorité plus élevée (99) pour s'assurer que ce filtre s'exécute après d'autres filtres de recherche par défaut si nécessaire
add_filter('posts_where', 'taupier_search_by_meta_and_title', 99, 2);


/**
 * Ajoute des champs de filtrage supplémentaires sur la page d'archive
 */
function taupier_archive_filters() {
    // S'assurer que le formulaire n'apparaît que sur les pages d'archive taupier et taxonomies
    if (!is_post_type_archive('taupier') && !is_tax('taupier_category') && !is_tax('taupier_tag')) {
        return;
    }

    // Récupérer les valeurs de recherche actuelles
    $current_s = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $current_ville = isset($_GET['taupier_ville']) ? sanitize_text_field($_GET['taupier_ville']) : '';
    $current_cp = isset($_GET['taupier_cp']) ? sanitize_text_field($_GET['taupier_cp']) : '';
    $current_region = isset($_GET['taupier_region']) ? sanitize_text_field($_GET['taupier_region']) : '';

    echo '<div class="taupier-filter-wrapper">';
    echo '<form method="get" class="taupier-filter-form" action="' . esc_url(get_post_type_archive_link('taupier')) . '">'; // Toujours envoyer à l'archive principale

    // Champ caché pour le type de publication
    echo '<input type="hidden" name="post_type" value="taupier">';

    // Si nous sommes sur une page de taxonomie, conserver le terme (pour affiner la recherche DANS cette taxonomie)
    if (is_tax('taupier_category')) {
        echo '<input type="hidden" name="taupier_category" value="' . get_query_var('term') . '">';
    } elseif (is_tax('taupier_tag')) {
        echo '<input type="hidden" name="taupier_tag" value="' . get_query_var('term') . '">';
    }

    echo '<div class="filter-controls-group">'; // Groupe pour les champs de recherche textuelle
    echo '<div class="filter-control">';
    echo '<label for="search-taupier">Rechercher (Nom, Zone, Ville, CP) :</label>';
    echo '<input type="search" id="search-taupier" name="s" placeholder="Ex: Jean Dupont, Paris, 75001" value="' . esc_attr($current_s) . '">';
    echo '</div>'; // .filter-control

    // Champ de recherche par ville
    echo '<div class="filter-control">';
    echo '<label for="filter-ville">Affiner par Ville :</label>';
    echo '<input type="text" id="filter-ville" name="taupier_ville" placeholder="Ex: Lyon" value="' . esc_attr($current_ville) . '">';
    echo '</div>'; // .filter-control

    // Champ de recherche par code postal
    echo '<div class="filter-control">';
    echo '<label for="filter-cp">Affiner par Code Postal :</label>';
    echo '<input type="text" id="filter-cp" name="taupier_cp" placeholder="Ex: 13001" value="' . esc_attr($current_cp) . '">';
    echo '</div>'; // .filter-control
    echo '</div>'; // .filter-controls-group


    // Filtre par région (dropdown existant)
    $regions = get_terms(array(
        'taxonomy' => 'taupier_region',
        'hide_empty' => true,
    ));

    if (!empty($regions) && !is_wp_error($regions)) {
        echo '<div class="filter-control filter-dropdown">';
        echo '<label for="taupier_region">Filtrer par Région :</label>';
        echo '<select name="taupier_region" id="taupier_region">';
        echo '<option value="">Toutes les régions</option>';

        foreach ($regions as $region) {
            $selected = ($current_region === $region->slug) ? 'selected' : '';
            echo '<option value="' . esc_attr($region->slug) . '" ' . $selected . '>' . esc_html($region->name) . '</option>';
        }

        echo '</select>';
        echo '</div>';
    }

    echo '<div class="filter-actions">';
    echo '<button type="submit" class="filter-submit">Rechercher & Filtrer</button>';
    // Bouton pour réinitialiser les filtres
    echo '<a href="' . esc_url(get_post_type_archive_link('taupier')) . '" class="filter-reset-button">Réinitialiser</a>';
    echo '</div>';

    echo '</form>';
    echo '</div>';
}
add_action('taupier_before_archive', 'taupier_archive_filters');