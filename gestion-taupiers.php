<?php
/**
 * Plugin Name: Gestion des Taupiers
 * Description: Permet de créer et gérer des fiches de taupiers avec catégories et tags
 * Version: 1.0
 * Author: Votre Nom
 */

// Sécurité : Empêche l'accès direct au fichier.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe principale du plugin
 */
class Gestion_Taupiers {

    /**
     * Constructeur
     */
    public function __construct() {
        // Enregistrement du type de contenu personnalisé "taupier"
        add_action('init', array($this, 'register_taupier_post_type'));

        // Enregistrement des taxonomies (catégories et tags)
        add_action('init', array($this, 'register_taupier_taxonomies'));

        // Ajout de métaboxes pour les champs personnalisés
        add_action('add_meta_boxes', array($this, 'add_taupier_meta_boxes'));

        // Ajout d'une métabox pour la galerie d'images du taupier
        add_action('add_meta_boxes', array($this, 'add_taupier_gallery_meta_box'));

        // Sauvegarde des métadonnées
        add_action('save_post_taupier', array($this, 'save_taupier_meta'));

        // Sauvegarde des métadonnées de la galerie
        add_action('save_post_taupier', array($this, 'save_taupier_gallery_meta'));

        // Shortcode pour afficher la barre de recherche
        add_shortcode('taupier_search', array($this, 'taupier_search_shortcode'));

        // Ajout des styles CSS (enqueued pour toutes les pages front-end, pas seulement les taupiers pour les shortcodes)
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));

        // Ajout des scripts JavaScript (enqueued pour toutes les pages front-end pour les shortcodes et avis)
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Personnalisation du template pour les taupiers
        add_filter('single_template', array($this, 'taupier_template'));
        add_filter('template_include', array($this, 'taupier_archive_template'));

        // Suppression des éléments non désirés
        add_action('wp_head', array($this, 'remove_unwanted_elements'));

        // Enregistrement du type de commentaire personnalisé pour les avis
        add_action('init', array($this, 'register_taupier_review_post_type'));

        // Ajout des métaboxes pour les avis
        add_action('add_meta_boxes', array($this, 'add_taupier_review_meta_boxes'));

        // Sauvegarde des métadonnées des avis
        add_action('save_post_taupier_review', array($this, 'save_taupier_review_meta'));

        // Ajout de l'AJAX pour soumettre les avis
        add_action('wp_ajax_submit_taupier_review', array($this, 'submit_taupier_review'));
        add_action('wp_ajax_nopriv_submit_taupier_review', array($this, 'submit_taupier_review'));

        // Ajout de l'admin pour les scripts
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

        // Filtre pour remplacer le placeholder dans les permaliens
        add_filter('post_type_link', array($this, 'replace_taupier_category_in_permalinks'), 10, 2);
    }

    /**
     * Enregistre le type de contenu personnalisé "taupier"
     */
    public function register_taupier_post_type() {
        $labels = array(
            'name'                => 'Taupiers',
            'singular_name'       => 'Taupier',
            'menu_name'           => 'Taupiers',
            'add_new'             => 'Ajouter un taupier',
            'add_new_item'        => 'Ajouter un nouveau taupier',
            'edit_item'           => 'Modifier le taupier',
            'new_item'            => 'Nouveau taupier',
            'view_item'           => 'Voir le taupier',
            'search_items'        => 'Rechercher des taupiers',
            'not_found'           => 'Aucun taupier trouvé',
            'not_found_in_trash'  => 'Aucun taupier trouvé dans la corbeille'
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'annuaire-taupiers/%taupier_category%', 'with_front' => false),
            'capability_type'     => 'post',
            'has_archive'         => 'annuaire-taupiers',
            'hierarchical'        => false,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-businessman',
            'supports'            => array('title', 'editor', 'thumbnail', 'excerpt')
        );

        register_post_type('taupier', $args);
    }

    /**
     * Enregistre les taxonomies (catégories et tags) pour les taupiers
     */
    public function register_taupier_taxonomies() {
        // Catégories de taupiers
        $cat_labels = array(
            'name'                => 'Catégories de taupiers',
            'singular_name'       => 'Catégorie de taupier',
            'search_items'        => 'Rechercher des catégories',
            'all_items'           => 'Toutes les catégories',
            'parent_item'         => 'Catégorie parente',
            'parent_item_colon'   => 'Catégorie parente:',
            'edit_item'           => 'Modifier la catégorie',
            'update_item'         => 'Mettre à jour la catégorie',
            'add_new_item'        => 'Ajouter une nouvelle catégorie',
            'new_item_name'       => 'Nom de la nouvelle catégorie',
            'menu_name'           => 'Catégories'
        );

        register_taxonomy('taupier_category', 'taupier', array(
            'hierarchical'        => true,
            'labels'              => $cat_labels,
            'show_ui'             => true,
            'show_admin_column'   => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'annuaire-taupiers', 'with_front' => false)
        ));

        // Tags de taupiers
        $tag_labels = array(
            'name'                => 'Tags de taupiers',
            'singular_name'       => 'Tag de taupier',
            'search_items'        => 'Rechercher des tags',
            'all_items'           => 'Tous les tags',
            'edit_item'           => 'Modifier le tag',
            'update_item'         => 'Mettre à jour le tag',
            'add_new_item'        => 'Ajouter un nouveau tag',
            'new_item_name'       => 'Nom du nouveau tag',
            'menu_name'           => 'Tags'
        );

        register_taxonomy('taupier_tag', 'taupier', array(
            'hierarchical'        => false,
            'labels'              => $tag_labels,
            'show_ui'             => true,
            'show_admin_column'   => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'taupier-tag')
        ));
    }

    /**
     * Ajoute les métaboxes pour les champs personnalisés
     */
    public function add_taupier_meta_boxes() {
        add_meta_box(
            'taupier_info',
            'Informations du taupier',
            array($this, 'taupier_info_callback'),
            'taupier',
            'normal',
            'high'
        );
    }

    /**
     * Ajoute une métabox pour la galerie d'images du taupier.
     */
    public function add_taupier_gallery_meta_box() {
        add_meta_box(
            'taupier_gallery',
            'Galerie d\'images du taupier',
            array($this, 'taupier_gallery_callback'),
            'taupier',
            'normal',
            'high'
        );
    }

    /**
     * Affiche le contenu de la métabox pour la galerie d'images.
     */
    public function taupier_gallery_callback($post) {
        wp_nonce_field('taupier_gallery_meta_box', 'taupier_gallery_meta_box_nonce');
        $gallery_images_ids = get_post_meta($post->ID, '_taupier_gallery_images', true);
        if (!is_array($gallery_images_ids)) {
            $gallery_images_ids = array();
        }
        ?>
        <div class="taupier-gallery-container">
            <ul class="taupier-gallery-images">
                <?php
                if (!empty($gallery_images_ids)) {
                    foreach ($gallery_images_ids as $image_id) {
                        $image_url = wp_get_attachment_image_src($image_id, 'thumbnail');
                        if ($image_url) {
                            echo '<li data-id="' . esc_attr($image_id) . '">';
                            echo '<img src="' . esc_url($image_url[0]) . '" alt="">';
                            echo '<a href="#" class="remove-gallery-image" data-id="' . esc_attr($image_id) . '">X</a>';
                            echo '</li>';
                        }
                    }
                }
                ?>
            </ul>
            <input type="hidden" id="taupier_gallery_images_ids" name="taupier_gallery_images" value="<?php echo esc_attr(implode(',', $gallery_images_ids)); ?>">
            <button type="button" class="button add-taupier-gallery-image">Ajouter des images à la galerie</button>
        </div>
        <p class="description">Ajoutez des images pour créer une galerie sur la page du taupier. Faites glisser pour réordonner.</p>

        <style>
            .taupier-gallery-images {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                list-style: none;
                margin: 0;
                padding: 0;
            }
            .taupier-gallery-images li {
                position: relative;
                width: 100px;
                height: 100px;
                border: 1px solid #ddd;
                box-sizing: border-box;
                cursor: grab;
            }
            .taupier-gallery-images li img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }
            .taupier-gallery-images li .remove-gallery-image {
                position: absolute;
                top: -5px;
                right: -5px;
                background: #f00;
                color: #fff;
                border-radius: 50%;
                width: 20px;
                height: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
                text-decoration: none;
                line-height: 1;
            }
        </style>
        <script>
            jQuery(document).ready(function($) {
                // Media uploader for gallery
                $('.add-taupier-gallery-image').on('click', function(e) {
                    e.preventDefault();
                    var button = $(this);
                    var uploader = wp.media({
                        title: 'Ajouter des images à la galerie',
                        multiple: true,
                        library: { type: 'image' },
                    }).on('select', function() {
                        var attachments = uploader.state().get('selection').toJSON();
                        var current_ids = $('#taupier_gallery_images_ids').val().split(',').filter(Boolean);
                        var new_ids = [];
                        var new_html = '';

                        attachments.forEach(function(attachment) {
                            if (!current_ids.includes(String(attachment.id))) {
                                new_ids.push(attachment.id);
                                new_html += '<li data-id="' + attachment.id + '">';
                                new_html += '<img src="' + attachment.sizes.thumbnail.url + '" alt="">';
                                new_html += '<a href="#" class="remove-gallery-image" data-id="' + attachment.id + '">X</a>';
                                new_html += '</li>';
                            }
                        });

                        $('#taupier_gallery_images_ids').val(current_ids.concat(new_ids).join(','));
                        $('.taupier-gallery-images').append(new_html);
                    }).open();
                });

                // Remove image from gallery
                $('.taupier-gallery-images').on('click', '.remove-gallery-image', function(e) {
                    e.preventDefault();
                    var image_id_to_remove = $(this).data('id');
                    var current_ids = $('#taupier_gallery_images_ids').val().split(',').filter(Boolean);
                    var updated_ids = current_ids.filter(function(id) {
                        return id !== String(image_id_to_remove);
                    });
                    $('#taupier_gallery_images_ids').val(updated_ids.join(','));
                    $(this).closest('li').remove();
                });

                // Sortable gallery
                $('.taupier-gallery-images').sortable({
                    items: 'li',
                    cursor: 'grab',
                    axis: 'x,y',
                    tolerance: 'pointer',
                    containment: 'parent',
                    update: function(event, ui) {
                        var sorted_ids = [];
                        $(this).find('li').each(function() {
                            sorted_ids.push($(this).data('id'));
                        });
                        $('#taupier_gallery_images_ids').val(sorted_ids.join(','));
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Sauvegarde les métadonnées de la galerie.
     */
    public function save_taupier_gallery_meta($post_id) {
        if (!isset($_POST['taupier_gallery_meta_box_nonce']) || !wp_verify_nonce($_POST['taupier_gallery_meta_box_nonce'], 'taupier_gallery_meta_box')) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['taupier_gallery_images'])) {
            $gallery_ids_string = sanitize_text_field($_POST['taupier_gallery_images']);
            $gallery_ids_array = array_map('absint', explode(',', $gallery_ids_string));
            // Remove empty values that might result from an empty string or multiple commas
            $gallery_ids_array = array_filter($gallery_ids_array);
            update_post_meta($post_id, '_taupier_gallery_images', $gallery_ids_array);
        } else {
            delete_post_meta($post_id, '_taupier_gallery_images');
        }
    }

    /**
     * Affiche le contenu de la métabox
     */
    public function taupier_info_callback($post) {
        // Ajout d'un nonce pour la sécurité
        wp_nonce_field('taupier_meta_box', 'taupier_meta_box_nonce');

        // Récupération des valeurs existantes
        $telephone = get_post_meta($post->ID, '_taupier_telephone', true);
        $email = get_post_meta($post->ID, '_taupier_email', true);
        $zone = get_post_meta($post->ID, '_taupier_zone', true);
        $experience = get_post_meta($post->ID, '_taupier_experience', true);
        $adresse = get_post_meta($post->ID, '_taupier_adresse', true);
        $horaires = get_post_meta($post->ID, '_taupier_horaires', true);
        $ville = get_post_meta($post->ID, '_taupier_ville', true);
        $code_postal = get_post_meta($post->ID, '_taupier_code_postal', true);

        // Récupération des questions/réponses de la FAQ
        $faq = get_post_meta($post->ID, '_taupier_faq', true);
        if (!is_array($faq) || empty($faq)) {
            $faq = array(
                array('question' => '', 'reponse' => ''),
                array('question' => '', 'reponse' => ''),
                array('question' => '', 'reponse' => ''),
                array('question' => '', 'reponse' => ''),
                array('question' => '', 'reponse' => '')
            );
        }

        // Affichage des champs
        ?>
        <table class="form-table">
            <tr>
                <th><label for="taupier_telephone">Téléphone</label></th>
                <td><input type="text" id="taupier_telephone" name="taupier_telephone" value="<?php echo esc_attr($telephone); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="taupier_email">Email</label></th>
                <td><input type="email" id="taupier_email" name="taupier_email" value="<?php echo esc_attr($email); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="taupier_zone">Zone d'intervention</label></th>
                <td><input type="text" id="taupier_zone" name="taupier_zone" value="<?php echo esc_attr($zone); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="taupier_experience">Années d'expérience</label></th>
                <td><input type="number" id="taupier_experience" name="taupier_experience" value="<?php echo esc_attr($experience); ?>" class="small-text"></td>
            </tr>
            <tr>
                <th><label for="taupier_adresse">Adresse</label></th>
                <td><textarea id="taupier_adresse" name="taupier_adresse" rows="3" class="large-text"><?php echo esc_textarea($adresse); ?></textarea></td>
            </tr>
            <tr>
                <th><label for="taupier_horaires">Horaires d'intervention</label></th>
                <td><textarea id="taupier_horaires" name="taupier_horaires" rows="5" class="large-text"><?php echo esc_textarea($horaires); ?></textarea>
                <p class="description">Exemple : Lundi-Vendredi: 8h-18h, Samedi: 9h-12h</p></td>
            </tr>
            <tr>
                <th><label for="taupier_ville">Ville d'intervention principale</label></th>
                <td><input type="text" id="taupier_ville" name="taupier_ville" value="<?php echo esc_attr($ville); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="taupier_code_postal">Code Postal</label></th>
                <td><input type="text" id="taupier_code_postal" name="taupier_code_postal" value="<?php echo esc_attr($code_postal); ?>" class="small-text"></td>
            </tr>
        </table>

        <h3>FAQ du taupier</h3>
        <p>Ajoutez jusqu'à 5 questions fréquemment posées concernant ce taupier.</p>
        <div class="taupier-faq-container">
            <?php for ($i = 0; $i < 5; $i++) : ?>
                <div class="taupier-faq-item">
                    <p>
                        <label for="taupier_faq_question_<?php echo $i; ?>">Question <?php echo $i + 1; ?></label>
                        <input type="text" id="taupier_faq_question_<?php echo $i; ?>" name="taupier_faq[<?php echo $i; ?>][question]" value="<?php echo isset($faq[$i]['question']) ? esc_attr($faq[$i]['question']) : ''; ?>" class="large-text">
                    </p>
                    <p>
                        <label for="taupier_faq_reponse_<?php echo $i; ?>">Réponse <?php echo $i + 1; ?></label>
                        <textarea id="taupier_faq_reponse_<?php echo $i; ?>" name="taupier_faq[<?php echo $i; ?>][reponse]" rows="3" class="large-text"><?php echo isset($faq[$i]['reponse']) ? esc_textarea($faq[$i]['reponse']) : ''; ?></textarea>
                    </p>
                </div>
            <?php endfor; ?>
        </div>

        <?php
    }

    /**
     * Sauvegarde les métadonnées
     */
    public function save_taupier_meta($post_id) {
        // Vérification du nonce
        if (!isset($_POST['taupier_meta_box_nonce']) || !wp_verify_nonce($_POST['taupier_meta_box_nonce'], 'taupier_meta_box')) {
            return;
        }

        // Vérification des autorisations
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Sauvegarde des champs
        if (isset($_POST['taupier_telephone'])) {
            update_post_meta($post_id, '_taupier_telephone', sanitize_text_field($_POST['taupier_telephone']));
        }

        if (isset($_POST['taupier_email'])) {
            update_post_meta($post_id, '_taupier_email', sanitize_email($_POST['taupier_email']));
        }

        if (isset($_POST['taupier_zone'])) {
            update_post_meta($post_id, '_taupier_zone', sanitize_text_field($_POST['taupier_zone']));
        }

        if (isset($_POST['taupier_experience'])) {
            update_post_meta($post_id, '_taupier_experience', intval($_POST['taupier_experience']));
        }

        if (isset($_POST['taupier_adresse'])) {
            update_post_meta($post_id, '_taupier_adresse', sanitize_textarea_field($_POST['taupier_adresse']));
        }

        if (isset($_POST['taupier_horaires'])) {
            update_post_meta($post_id, '_taupier_horaires', sanitize_textarea_field($_POST['taupier_horaires']));
        }

        // Sauvegarde des nouveaux champs Ville et Code Postal
        if (isset($_POST['taupier_ville'])) {
            update_post_meta($post_id, '_taupier_ville', sanitize_text_field($_POST['taupier_ville']));
        }

        if (isset($_POST['taupier_code_postal'])) {
            update_post_meta($post_id, '_taupier_code_postal', sanitize_text_field($_POST['taupier_code_postal']));
        }

        // Sauvegarde de la FAQ
        if (isset($_POST['taupier_faq']) && is_array($_POST['taupier_faq'])) {
            $faq_sanitized = array();
            foreach ($_POST['taupier_faq'] as $index => $qa_pair) {
                if (!empty($qa_pair['question']) || !empty($qa_pair['reponse'])) {
                    $faq_sanitized[] = array(
                        'question' => sanitize_text_field($qa_pair['question']),
                        'reponse' => sanitize_textarea_field($qa_pair['reponse'])
                    );
                }
            }
            update_post_meta($post_id, '_taupier_faq', $faq_sanitized);
        }
    }

    /**
     * Shortcode pour afficher la barre de recherche
     */
    public function taupier_search_shortcode($atts) {
        // Récupération des catégories
        $categories = get_terms(array(
            'taxonomy' => 'taupier_category',
            'hide_empty' => false,
        ));

        // Récupération des tags
        $tags = get_terms(array(
            'taxonomy' => 'taupier_tag',
            'hide_empty' => false,
        ));

        // Début du HTML
        $output = '<div class="taupier-search-form">';
        $output .= '<form method="get" action="' . esc_url(home_url('/')) . '">';
        $output .= '<input type="hidden" name="post_type" value="taupier">';

        // Champ de recherche textuel
        $output .= '<div class="search-field">';
        $output .= '<label for="taupier-search">Rechercher un taupier :</label>';
        $output .= '<input type="text" id="taupier-search" name="s" placeholder="Nom, ville...">';
        $output .= '</div>';

        // Liste déroulante des catégories
        if (!empty($categories) && !is_wp_error($categories)) {
            $output .= '<div class="search-field">';
            $output .= '<label for="taupier-category">Catégorie :</label>';
            $output .= '<select id="taupier-category" name="taupier_category">';
            $output .= '<option value="">Toutes les catégories</option>';

            foreach ($categories as $category) {
                $output .= '<option value="' . esc_attr($category->slug) . '">' . esc_html($category->name) . '</option>';
            }

            $output .= '</select>';
            $output .= '</div>';
        }

        // Liste déroulante des tags
        if (!empty($tags) && !is_wp_error($tags)) {
            $output .= '<div class="search-field">';
            $output .= '<label for="taupier-tag">Tag :</label>';
            $output .= '<select id="taupier-tag" name="taupier_tag">';
            $output .= '<option value="">Tous les tags</option>';

            foreach ($tags as $tag) {
                $output .= '<option value="' . esc_attr($tag->slug) . '">' . esc_html($tag->name) . '</option>';
            }

            $output .= '</select>';
            $output .= '</div>';
        }

        // Bouton de recherche
        $output .= '<div class="search-field">';
        $output .= '<button type="submit" class="search-button">Rechercher</button>';
        $output .= '</div>';

        $output .= '</form>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Ajoute les styles CSS
     */
    public function enqueue_styles() {
        // Enqueue styles globally as shortcodes or archive pages can appear anywhere.
        wp_enqueue_style('taupier-styles', plugins_url('taupier-styles.css', __FILE__));
    }

    /**
     * Ajoute les scripts JavaScript
     */
    public function enqueue_scripts() {
        // Enqueue scripts globally as shortcodes or review forms can appear anywhere.
        wp_enqueue_script('taupier-scripts', plugins_url('taupier-scripts.js', __FILE__), array('jquery'), '1.0', true);
        wp_localize_script('taupier-scripts', 'taupier_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('taupier_review_nonce')
        ));
    }

    /**
     * Ajoute les scripts JavaScript dans l'admin
     */
    public function admin_enqueue_scripts($hook) {
        global $post;

        // Uniquement sur la page d'édition d'un taupier
        if ($hook == 'post.php' || $hook == 'post-new.php') {
            if (isset($post) && $post->post_type === 'taupier') {
                wp_enqueue_media();
                // Enqueue the admin-specific script that uses jQuery for the gallery sortable/add/remove
                wp_enqueue_script('taupier-admin-gallery', plugins_url('js/taupier-admin-gallery.js', __FILE__), array('jquery', 'jquery-ui-sortable'), '1.0', true);
            }
        }
    }

    /**
     * Remplace le placeholder %taupier_category% dans l'URL par le slug de la catégorie.
     */
    public function replace_taupier_category_in_permalinks($post_link, $post) {
        if (is_object($post) && $post->post_type == 'taupier') {
            $terms = wp_get_object_terms($post->ID, 'taupier_category');
            if ($terms) {
                // Remplacer le placeholder par le slug du premier terme trouvé
                return str_replace('%taupier_category%', $terms[0]->slug, $post_link);
            } else {
                // S'il n'y a pas de catégorie, on retire le placeholder et le slash qui le précède.
                // Ceci évite d'avoir une URL comme /annuaire-taupiers//nom-du-taupier
                // Il est préférable de s'assurer que chaque taupier a au moins une catégorie.
                $post_link = str_replace('/%taupier_category%', '', $post_link);
                return $post_link; 
            }
        }
        return $post_link;
    }

    /**
     * Utilise un template personnalisé pour les pages de taupiers
     */
    public function taupier_template($template) {
        global $post;

        if (is_object($post) && $post->post_type === 'taupier') { // Vérifier si $post est un objet
            $template_path = $this->get_taupier_template_path('single-taupier.php');
            if (file_exists($template_path)) {
                return $template_path;
            }
        }

        return $template;
    }

    /**
     * Utilise un template personnalisé pour les archives de taupiers
     */
    public function taupier_archive_template($template) {
        if (is_post_type_archive('taupier') || is_tax('taupier_category') || is_tax('taupier_tag')) {
            $template_path = $this->get_taupier_template_path('archive-taupier.php');
            if (file_exists($template_path)) {
                return $template_path;
            }
        }

        return $template;
    }

    /**
     * Supprime les éléments non désirés sur les pages de taupiers
     */
    public function remove_unwanted_elements() {
        // This CSS block should ideally be in taupier-styles.css for better maintainability.
        // For demonstration, it's kept here as per original structure.
        echo '<style>
            /* ===== Style général taupier ===== */
            .taupier-single-container, .taupier-archive-container {
                font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
                max-width: 1200px;
                margin: 0 auto;
                padding: 2rem;
            }

            /* ===== Titres ===== */
            .entry-title, .page-title, h3 {
                color: #207648;
                border-bottom: 2px solid #6B381F;
                padding-bottom: 0.5rem;
                margin-bottom: 1.5rem;
            }

            /* ===== Images ===== */
            .taupier-featured-image img,
            .taupier-photo-additionnelle img {
                width: 100%;
                border-radius: 10px;
                box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            }

            /* ===== Détails taupier ===== */
            .taupier-details, .taupier-faq, .taupier-reviews, .taupier-review-form {
                background-color: #fdfdfd;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 1.5rem;
                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
                margin-bottom: 2rem;
            }

            .taupier-info-list li {
                margin-bottom: 0.6rem;
            }

            /* ===== FAQ ===== */
            .taupier-faq .faq-item {
                background-color: #fff;
                border-left: 4px solid #207648;
                padding: 1rem;
                margin-bottom: 1rem;
                border-radius: 6px;
            }

            .faq-question {
                font-weight: bold;
                color: #6B381F;
            }

            /* ===== Barre de recherche ===== */
            .taupier-search-form {
                background-color: #207648;
                color: #fff;
                border-radius: 8px;
                padding: 1.5rem;
            }

            .taupier-search-form input,
            .taupier-search-form select {
                border: none;
                border-radius: 6px;
                padding: 0.7rem;
            }

            .taupier-search-form .search-button {
                background-color: #6B381F;
                color: #fff;
                border-radius: 6px;
                font-weight: bold;
            }

            .taupier-search-form .search-button:hover {
                background-color: #532d17;
            }

            /* ===== Avis clients ===== */
            .taupier-reviews .review-item {
                border-bottom: 1px solid #e2e8f0;
                padding-bottom: 1rem;
                margin-bottom: 1rem;
            }

            .review-rating .star {
                color: gold;
            }

            /* ===== Formulaire Avis ===== */
            .taupier-review-form input,
            .taupier-review-form textarea {
                border-radius: 6px;
                border: 1px solid #ddd;
                padding: 0.7rem;
            }

            .submit-review-button {
                background-color: #207648;
                color: white;
                border-radius: 6px;
                font-weight: bold;
            }

            .submit-review-button:hover {
                background-color: #185f38;
            }

            .rating-selector label {
                color: #ddd;
                cursor: pointer;
                font-size: 1.5rem;
            }

            .rating-selector input:checked ~ label,
            .rating-selector label:hover,
            .rating-selector label:hover ~ label {
                color: gold;
            }

            /* ===== Responsive Design ===== */
            @media (max-width: 768px) {
                .taupier-search-form form,
                .taupier-details,
                .taupier-faq,
                .taupier-reviews,
                .taupier-review-form {
                    padding: 1rem;
                }

                .taupier-single-container,
                .taupier-archive-container {
                    padding: 1rem;
                }
            }

        </style>';

        // Remove sidebar if theme supports it
        // This is a more direct way to remove sidebars conditionally
        if (is_singular('taupier') || is_post_type_archive('taupier') || is_tax('taupier_category') || is_tax('taupier_tag')) {
            add_filter('sidebars_widgets', function ($widgets) {
                // Return an empty array to remove all sidebars.
                // Be cautious with this, as it might remove sidebars from other parts of your site
                // if this filter is applied too broadly. It's usually better to control this via theme templates.
                return array();
            }, 100); // High priority to override other plugins/themes
        }

        // More targeted content filtering for specific strings
        add_filter('the_content', array($this, 'filter_taupier_content_unwanted_elements'), 999);
    }

    /**
     * Filters the content to remove unwanted elements based on patterns.
     * Moved from remove_unwanted_elements to ensure it acts on the content.
     * @param string $content The post content.
     * @return string Filtered content.
     */
    public function filter_taupier_content_unwanted_elements($content) {
        if (is_singular('taupier') || is_post_type_archive('taupier') || is_tax('taupier_category') || is_tax('taupier_tag')) {
            // Remove the "Découvrez combien de taupes..." questionnaire
            $pattern = '/Découvrez combien de taupes menacent votre jardin.*?3 mois<\/strong>/s';
            $content = preg_replace($pattern, '', $content);

            // Remove "This entry was posted in..."
            $pattern = '/<p class="post-data">This entry was posted in.*?<\/p>/s'; // More specific pattern
            $content = preg_replace($pattern, '', $content);
        }
        return $content;
    }


    /**
     * Enregistre le type de contenu personnalisé pour les avis
     */
    public function register_taupier_review_post_type() {
        $labels = array(
            'name'                => 'Avis sur les taupiers',
            'singular_name'       => 'Avis sur le taupier',
            'menu_name'           => 'Avis',
            'add_new'             => 'Ajouter un avis',
            'add_new_item'        => 'Ajouter un nouvel avis',
            'edit_item'           => 'Modifier l\'avis',
            'new_item'            => 'Nouvel avis',
            'view_item'           => 'Voir l\'avis',
            'search_items'        => 'Rechercher des avis',
            'not_found'           => 'Aucun avis trouvé',
            'not_found_in_trash'  => 'Aucun avis trouvé dans la corbeille'
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false, // Avis are not publicly queryable directly as a post type
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => 'edit.php?post_type=taupier', // Appears under "Taupiers" menu
            'query_var'           => false,
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_position'       => null,
            'supports'            => array('title', 'editor'),
            'menu_icon'           => 'dashicons-format-status'
        );

        register_post_type('taupier_review', $args);
    }

    /**
     * Ajoute les métaboxes pour les avis
     */
    public function add_taupier_review_meta_boxes() {
        add_meta_box(
            'taupier_review_info',
            'Informations sur l\'avis',
            array($this, 'taupier_review_info_callback'),
            'taupier_review',
            'normal',
            'high'
        );
    }

    /**
     * Affiche le contenu de la métabox pour les avis
     */
    public function taupier_review_info_callback($post) {
        // Ajout d'un nonce pour la sécurité
        wp_nonce_field('taupier_review_meta_box', 'taupier_review_meta_box_nonce');

        // Récupération des valeurs existantes
        $taupier_id = get_post_meta($post->ID, '_taupier_review_taupier_id', true);
        $rating = get_post_meta($post->ID, '_taupier_review_rating', true);
        $author_name = get_post_meta($post->ID, '_taupier_review_author_name', true);
        $author_email = get_post_meta($post->ID, '_taupier_review_author_email', true);
        $status = get_post_meta($post->ID, '_taupier_review_status', true);

        // Liste des taupiers pour le menu déroulant
        $taupiers = get_posts(array(
            'post_type' => 'taupier',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        // Affichage des champs
        ?>
        <table class="form-table">
            <tr>
                <th><label for="taupier_review_taupier_id">Taupier concerné</label></th>
                <td>
                    <select id="taupier_review_taupier_id" name="taupier_review_taupier_id">
                        <option value="">Sélectionner un taupier</option>
                        <?php foreach ($taupiers as $taupier) : ?>
                            <option value="<?php echo $taupier->ID; ?>" <?php selected($taupier_id, $taupier->ID); ?>><?php echo $taupier->post_title; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="taupier_review_rating">Note</label></th>
                <td>
                    <select id="taupier_review_rating" name="taupier_review_rating">
                        <option value="5" <?php selected($rating, 5); ?>>5 étoiles</option>
                        <option value="4" <?php selected($rating, 4); ?>>4 étoiles</option>
                        <option value="3" <?php selected($rating, 3); ?>>3 étoiles</option>
                        <option value="2" <?php selected($rating, 2); ?>>2 étoiles</option>
                        <option value="1" <?php selected($rating, 1); ?>>1 étoile</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="taupier_review_author_name">Nom de l'auteur</label></th>
                <td><input type="text" id="taupier_review_author_name" name="taupier_review_author_name" value="<?php echo esc_attr($author_name); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="taupier_review_author_email">Email de l'auteur</label></th>
                <td><input type="email" id="taupier_review_author_email" name="taupier_review_author_email" value="<?php echo esc_attr($author_email); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="taupier_review_status">Statut de l'avis</label></th>
                <td>
                    <select id="taupier_review_status" name="taupier_review_status">
                        <option value="approved" <?php selected($status, 'approved'); ?>>Approuvé</option>
                        <option value="pending" <?php selected($status, 'pending'); ?>>En attente</option>
                        <option value="rejected" <?php selected($status, 'rejected'); ?>>Rejeté</option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Sauvegarde les métadonnées des avis
     */
    public function save_taupier_review_meta($post_id) {
        // Vérification du nonce
        if (!isset($_POST['taupier_review_meta_box_nonce']) || !wp_verify_nonce($_POST['taupier_review_meta_box_nonce'], 'taupier_review_meta_box')) {
            return;
        }

        // Vérification des autorisations
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Sauvegarde des champs
        if (isset($_POST['taupier_review_taupier_id'])) {
            update_post_meta($post_id, '_taupier_review_taupier_id', intval($_POST['taupier_review_taupier_id']));
        }

        if (isset($_POST['taupier_review_rating'])) {
            update_post_meta($post_id, '_taupier_review_rating', intval($_POST['taupier_review_rating']));
        }

        if (isset($_POST['taupier_review_author_name'])) {
            update_post_meta($post_id, '_taupier_review_author_name', sanitize_text_field($_POST['taupier_review_author_name']));
        }

        if (isset($_POST['taupier_review_author_email'])) {
            update_post_meta($post_id, '_taupier_review_author_email', sanitize_email($_POST['taupier_review_author_email']));
        }

        if (isset($_POST['taupier_review_status'])) {
            update_post_meta($post_id, '_taupier_review_status', sanitize_text_field($_POST['taupier_review_status']));
        }
    }

    /**
     * Fonction AJAX pour soumettre un avis
     */
    public function submit_taupier_review() {
        // Vérification du nonce
        check_ajax_referer('taupier_review_nonce', 'nonce');

        // Récupération des données
        $taupier_id = isset($_POST['taupier_id']) ? intval($_POST['taupier_id']) : 0;
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
        $author_name = isset($_POST['author_name']) ? sanitize_text_field($_POST['author_name']) : '';
        $author_email = isset($_POST['author_email']) ? sanitize_email($_POST['author_email']) : '';
        $review_text = isset($_POST['review_text']) ? sanitize_textarea_field($_POST['review_text']) : '';

        // Validation des données
        $errors = array();

        if (empty($taupier_id) || get_post_type($taupier_id) !== 'taupier') {
            $errors[] = 'Taupier invalide.';
        }

        if ($rating < 1 || $rating > 5) {
            $errors[] = 'Note invalide. Veuillez donner une note entre 1 et 5.';
        }

        if (empty($author_name)) {
            $errors[] = 'Veuillez entrer votre nom.';
        }

        if (empty($author_email) || !is_email($author_email)) {
            $errors[] = 'Veuillez entrer une adresse email valide.';
        }

        if (empty($review_text)) {
            $errors[] = 'Veuillez entrer votre avis.';
        }

        // Traitement des erreurs
        if (!empty($errors)) {
            wp_send_json_error(array(
                'message' => 'Erreur lors de la soumission de l\'avis.',
                'errors' => $errors
            ));
        }

        // Création de l'avis
        $review = array(
            'post_title' => 'Avis de ' . $author_name . ' sur ' . get_the_title($taupier_id),
            'post_content' => $review_text,
            'post_status' => 'publish', // Default to publish for now, but `pending` is a good practice
            'post_type' => 'taupier_review'
        );

        // Insertion de l'avis
        $review_id = wp_insert_post($review);

        if (is_wp_error($review_id)) {
            wp_send_json_error(array(
                'message' => 'Erreur lors de la création de l\'avis.',
                'errors' => array($review_id->get_error_message())
            ));
        }

        // Sauvegarde des métadonnées
        update_post_meta($review_id, '_taupier_review_taupier_id', $taupier_id);
        update_post_meta($review_id, '_taupier_review_rating', $rating);
        update_post_meta($review_id, '_taupier_review_author_name', $author_name);
        update_post_meta($review_id, '_taupier_review_author_email', $author_email);
        update_post_meta($review_id, '_taupier_review_status', 'pending'); // Par défaut en attente de modération

        // Notification par email à l'administrateur
        $admin_email = get_option('admin_email');
        $subject = 'Nouvel avis sur un taupier à modérer';
        $message = "Un nouvel avis a été soumis pour le taupier : " . get_the_title($taupier_id) . "\n\n";
        $message .= "Auteur : " . $author_name . "\n";
        $message .= "Email : " . $author_email . "\n";
        $message .= "Note : " . $rating . " étoiles\n\n";
        $message .= "Avis : " . $review_text . "\n\n";
        $message .= "Pour modérer cet avis, veuillez vous connecter à votre tableau de bord WordPress: " . admin_url('post.php?post=' . $review_id . '&action=edit');


        wp_mail($admin_email, $subject, $message);

        // Réponse de succès
        wp_send_json_success(array(
            'message' => 'Merci pour votre avis ! Il sera publié après modération.',
            'review_id' => $review_id
        ));
    }

    /**
     * Fonction pour trouver le chemin des templates
     */
    public function get_taupier_template_path($template_name) {
        $template_path = plugin_dir_path(__FILE__) . 'templates/' . $template_name;

        // Si le template n'existe pas dans le dossier du plugin, on le crée
        if (!file_exists($template_path)) {
            if (!is_dir(plugin_dir_path(__FILE__) . 'templates/')) {
                mkdir(plugin_dir_path(__FILE__) . 'templates/', 0755, true); // Added recursive to true for mkdir
            }

            // Template par défaut pour single-taupier.php
            if ($template_name === 'single-taupier.php') {
                $template_content = <<<EOT
<?php
/**
 * Template pour l'affichage d'un taupier
 */
get_header();

// Sécurité : Empêche l'accès direct au fichier.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fonction d'aide pour afficher les étoiles de notation.
 *
 * @param float \$average_rating La note moyenne.
 * @param bool  \$schema_org Indique si les microdonnées Schema.org doivent être incluses.
 * @return string Le HTML des étoiles.
 */
function taupier_display_stars(\$average_rating, \$schema_org = false) {
    \$output = '';
    \$whole_stars = floor(\$average_rating);
    \$half_star = (\$average_rating - \$whole_stars) >= 0.25 && (\$average_rating - \$whole_stars) < 0.75; // Adjusts for better half-star detection
    \$empty_stars = 5 - \$whole_stars - (\$half_star ? 1 : 0);

    if (\$schema_org) {
        \$output .= '<div class="rating-stars" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">';
        \$output .= '<meta itemprop="worstRating" content="1">';
        \$output .= '<meta itemprop="bestRating" content="5">';
        \$output .= '<meta itemprop="ratingValue" content="' . esc_attr(round(\$average_rating, 1)) . '">';
    } else {
        \$output .= '<div class="rating-stars">';
    }

    // Full stars
    for (\$i = 0; \$i < \$whole_stars; \$i++) {
        \$output .= '<span class="star full" aria-hidden="true">&#9733;</span>'; // Unicode star
    }

    // Half star
    if (\$half_star) {
        \$output .= '<span class="star half" aria-hidden="true">&#9733;</span>'; // Unicode star
    }

    // Empty stars
    for (\$i = 0; \$i < \$empty_stars; \$i++) {
        \$output .= '<span class="star empty" aria-hidden="true">&#9734;</span>'; // Unicode empty star
    }
    \$output .= '</div>'; // .rating-stars

    return \$output;
}

?>

<div id="primary" class="content-area taupier-single-container">
    <main id="main" class="site-main">
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?> itemscope itemtype="https://schema.org/LocalBusiness">
            <?php
            // Récupérer les informations de base pour Schema.org
            \$post_id = get_the_ID();
            \$taupier_title = get_the_title();
            \$taupier_permalink = get_permalink();
            \$taupier_content = get_the_content();
            \$taupier_excerpt = get_the_excerpt();

            \$telephone = get_post_meta(\$post_id, '_taupier_telephone', true);
            if (empty(\$telephone)) {
                \$telephone = get_post_meta(\$post_id, '_taupier_phone', true);
            }
            \$email = get_post_meta(\$post_id, '_taupier_email', true);
            \$zone = get_post_meta(\$post_id, '_taupier_zone', true);
            \$experience = get_post_meta(\$post_id, '_taupier_experience', true);
            \$adresse = get_post_meta(\$post_id, '_taupier_adresse', true);
            \$horaires_text = get_post_meta(\$post_id, '_taupier_horaires', true);
            \$ville = get_post_meta(\$post_id, '_taupier_ville', true);
            \$code_postal = get_post_meta(\$post_id, '_taupier_code_postal', true);

            // Gérer les images pour Schema.org
            \$main_image_url = '';
            \$thumbnail_id = get_post_thumbnail_id(\$post_id);
            if (\$thumbnail_id) {
                \$image_array = wp_get_attachment_image_src(\$thumbnail_id, 'full');
                if (\$image_array) {
                    \$main_image_url = \$image_array[0];
                }
            }
            if (empty(\$main_image_url)) {
                \$attached_images = get_attached_media('image', \$post_id);
                if (!empty(\$attached_images)) {
                    \$first_image = reset(\$attached_images);
                    \$image_array = wp_get_attachment_image_src(\$first_image->ID, 'full');
                    if (\$image_array) {
                        \$main_image_url = \$image_array[0];
                    }
                }
            }

            // Calcul de la note moyenne et du nombre d'avis pour Schema.org
            \$reviews_for_schema = get_posts(array(
                'post_type'      => 'taupier_review',
                'posts_per_page' => -1,
                'meta_query'     => array(
                    array(
                        'key'     => '_taupier_review_taupier_id',
                        'value'   => \$post_id,
                        'compare' => '=',
                    ),
                    array(
                        'key'     => '_taupier_review_status',
                        'value'   => 'approved',
                        'compare' => '=',
                    ),
                ),
            ));
            \$total_rating_schema = 0;
            \$count_reviews_schema = count(\$reviews_for_schema);
            foreach (\$reviews_for_schema as \$review_schema) {
                \$total_rating_schema += intval(get_post_meta(\$review_schema->ID, '_taupier_review_rating', true));
            }
            \$average_rating_schema = \$count_reviews_schema > 0 ? round(\$total_rating_schema / \$count_reviews_schema, 1) : 0;
            ?>

            <meta itemprop="url" content="<?php echo esc_url(\$taupier_permalink); ?>">
            <?php if (!empty(\$main_image_url)) : ?>
                <meta itemprop="image" content="<?php echo esc_url(\$main_image_url); ?>">
            <?php endif; ?>
            <meta itemprop="name" content="<?php echo esc_attr(\$taupier_title); ?>">
            <meta itemprop="description" content="<?php echo esc_attr(wp_trim_words(strip_tags(\$taupier_excerpt ? \$taupier_excerpt : \$taupier_content), 30, '...')); ?>">

            <?php if (!empty(\$telephone)) : ?>
                <meta itemprop="telephone" content="<?php echo esc_attr(\$telephone); ?>">
            <?php endif; ?>
            <?php if (!empty(\$email)) : ?>
                <meta itemprop="email" content="<?php echo esc_attr(\$email); ?>">
            <?php endif; ?>

            <?php if (!empty(\$adresse) || !empty(\$ville) || !empty(\$code_postal)) : ?>
            <div itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
                <?php if (!empty(\$adresse)) : ?>
                    <meta itemprop="streetAddress" content="<?php echo esc_attr(\$adresse); ?>">
                <?php endif; ?>
                <?php if (!empty(\$ville)) : ?>
                    <meta itemprop="addressLocality" content="<?php echo esc_attr(\$ville); ?>">
                <?php endif; ?>
                <?php if (!empty(\$code_postal)) : ?>
                    <meta itemprop="postalCode" content="<?php echo esc_attr(\$code_postal); ?>">
                <?php endif; ?>
                <meta itemprop="addressCountry" content="FR">
            </div>
            <?php endif; ?>

            <?php
            // Ajout du priceRange (facultatif, mais aide Google)
            // Adapte ceci à la réalité de tes tarifs, ex: "€€" pour moyen, ou "50€ - 200€"
            echo '<meta itemprop="priceRange" content="€€">'; // Exemple: tu peux le modifier

            // Gestion des horaires pour Schema.org
            if (!empty(\$horaires_text)) {
                \$days_of_week = [
                    'Lundi' => 'http://schema.org/Monday',
                    'Mardi' => 'http://schema.org/Tuesday',
                    'Mercredi' => 'http://schema.org/Wednesday',
                    'Jeudi' => 'http://schema.org/Thursday',
                    'Vendredi' => 'http://schema.org/Friday',
                    'Samedi' => 'http://schema.org/Saturday',
                    'Dimanche' => 'http://schema.org/Sunday',
                ];
                \$parsed_hours = [];

                // Regex plus robuste pour capturer les jours et les heures
                // Ex: "Lundi-Vendredi: 8h-18h", "Samedi: 9h-12h", "7 jours sur 7"
                if (preg_match_all('/(Lundi|Mardi|Mercredi|Jeudi|Vendredi|Samedi|Dimanche)(?:-(\w+))?:\s*(\d{1,2}h(\d{2})?)?\s*-\s*(\d{1,2}h(\d{2})?)?|\s*(?:7 jours sur 7|24h\/24)/i', \$horaires_text, \$matches, PREG_SET_ORDER)) {
                    foreach (\$matches as \$match) {
                        if (isset(\$match[0]) && (strpos(strtolower(\$match[0]), '7 jours sur 7') !== false || strpos(strtolower(\$match[0]), '24h/24') !== false)) {
                            // Cas "7 jours sur 7" ou "24h/24"
                            \$parsed_hours[] = [
                                'dayOfWeek' => 'http://schema.org/Monday http://schema.org/Tuesday http://schema.org/Wednesday http://schema.org/Thursday http://schema.org/Friday http://schema.org/Saturday http://schema.org/Sunday',
                                'opens' => '00:00',
                                'closes' => '23:59'
                            ];
                        } elseif (isset(\$match[1]) && isset(\$match[3]) && isset(\$match[5])) {
                            // Cas avec jours et heures spécifiques
                            \$day_start_name = trim(\$match[1]);
                            \$day_end_name = isset(\$match[2]) && !empty(\$match[2]) ? trim(\$match[2]) : \$day_start_name;
                            \$open_time = trim(\$match[3]);
                            \$close_time = trim(\$match[5]);

                            \$open_time_formatted = str_replace('h', ':', \$open_time);
                            if (substr(\$open_time_formatted, -1) === ':') \$open_time_formatted .= '00';
                            \$close_time_formatted = str_replace('h', ':', \$close_time);
                            if (substr(\$close_time_formatted, -1) === ':') \$close_time_formatted .= '00';

                            // Map French day names to Schema.org day URIs
                            \$start_day_uri = array_key_exists(\$day_start_name, \$days_of_week) ? \$days_of_week[\$day_start_name] : '';
                            \$end_day_uri = array_key_exists(\$day_end_name, \$days_of_week) ? \$days_of_week[\$day_end_name] : '';

                            if (\$start_day_uri && \$end_day_uri) {
                                \$day_uris = array_values(\$days_of_week);
                                \$start_index = array_search(\$start_day_uri, \$day_uris);
                                \$end_index = array_search(\$end_day_uri, \$day_uris);

                                if (\$start_index !== false && \$end_index !== false && \$start_index <= \$end_index) {
                                    for (\$i = \$start_index; \$i <= \$end_index; \$i++) {
                                        \$parsed_hours[] = [
                                            'dayOfWeek' => \$day_uris[\$i],
                                            'opens' => \$open_time_formatted,
                                            'closes' => \$close_time_formatted
                                        ];
                                    }
                                } elseif (\$start_index !== false) { // Single day case
                                    \$parsed_hours[] = [
                                        'dayOfWeek' => \$start_day_uri,
                                        'opens' => \$open_time_formatted,
                                        'closes' => \$close_time_formatted
                                    ];
                                }
                            }
                        }
                    }
                }

                // Affichage des balises openingHoursSpecification
                if (!empty(\$parsed_hours)) {
                    foreach (\$parsed_hours as \$oh_spec) {
                        echo '<div itemprop="openingHoursSpecification" itemscope itemtype="https://schema.org/OpeningHoursSpecification">' . "\n";
                        echo '<meta itemprop="dayOfWeek" content="' . esc_attr(\$oh_spec['dayOfWeek']) . '">' . "\n";
                        echo '<meta itemprop="opens" content="' . esc_attr(\$oh_spec['opens']) . '">' . "\n";
                        echo '<meta itemprop="closes" content="' . esc_attr(\$oh_spec['closes']) . '">' . "\n";
                        echo '</div>' . "\n";
                    }
                } else {
                    // Fallback si le parsing échoue, utiliser la propriété générique openingHours
                    echo '<meta itemprop="openingHours" content="' . esc_attr(\$horaires_text) . '">' . "\n";
                }
            }
            ?>

            <?php if (\$count_reviews_schema > 0) : ?>
                <div itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
                    <meta itemprop="ratingValue" content="<?php echo esc_attr(\$average_rating_schema); ?>">
                    <meta itemprop="reviewCount" content="<?php echo esc_attr(\$count_reviews_schema); ?>">
                    <meta itemprop="bestRating" content="5">
                    <meta itemprop="worstRating" content="1">
                </div>
            <?php endif; ?>

            <div class="taupier-row">
                <h1 class="taupier-row-title">Taupier professionnel <span class="entry-title-subtitle" itemprop="name"><?php echo esc_html(\$taupier_title); ?></span></h1>
                <div class="taupier-row-content">
                    <div class="taupier-main-info-row">
                        <div class="taupier-main-image-column">
                            <?php if (!empty(\$main_image_url)) : ?>
                                <div class="taupier-featured-image">
                                    <img src="<?php echo esc_url(\$main_image_url); ?>" alt="<?php echo esc_attr(\$taupier_title . ' - Photo principale'); ?>" class="taupier-thumbnail-img">
                                </div>
                            <?php else : ?>
                                <div class="taupier-no-image"><p>Aucune image disponible pour ce taupier.</p></div>
                            <?php endif; ?>
                        </div>

                        <div class="taupier-info-column">
                            <div class="taupier-details">
                                <div class="taupier-coordinates">
                                    <h2>Coordonnées</h2>
                                    <ul class="taupier-info-list">
                                        <?php if (!empty(\$telephone)) : ?>
                                            <li><strong>Téléphone :</strong> <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', \$telephone)); ?>" itemprop="telephone"><?php echo esc_html(\$telephone); ?></a></li>
                                        <?php endif; ?>

                                        <?php if (!empty(\$email)) : ?>
                                            <li><strong>Email :</strong> <a href="mailto:<?php echo esc_attr(\$email); ?>" itemprop="email"><?php echo esc_html(\$email); ?></a></li>
                                        <?php endif; ?>

                                        <?php if (!empty(\$zone)) : ?>
                                            <li><strong>Zone d'intervention :</strong> <span itemprop="areaServed"><?php echo esc_html(\$zone); ?></span></li>
                                        <?php endif; ?>

                                        <?php if (!empty(\$experience)) : ?>
                                            <li><strong>Expérience :</strong> <span><?php echo esc_html(\$experience); ?> ans</span></li>
                                        <?php endif; ?>

                                        <?php if (!empty(\$adresse) || !empty(\$ville) || !empty(\$code_postal)) : ?>
                                            <li>
                                                <strong>Adresse :</strong>
                                                <address>
                                                    <?php
                                                    if (!empty(\$adresse)) :
                                                        echo nl2br(esc_html(\$adresse));
                                                    endif;
                                                    if (!empty(\$ville)) :
                                                        echo '<br><span itemprop="addressLocality">' . esc_html(\$ville) . '</span>';
                                                    endif;
                                                    if (!empty(\$code_postal)) :
                                                        echo ' <span itemprop="postalCode">' . esc_html(\$code_postal) . '</span>';
                                                    endif;
                                                    ?>
                                                </address>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>

                                <?php if (!empty(\$horaires_text)) : ?>
                                    <div class="taupier-hours">
                                        <h2>Horaires d'intervention</h2>
                                        <div class="horaires-content"><?php echo nl2br(esc_html(\$horaires_text)); ?></div>
                                    </div>
                                <?php endif; ?>

                                </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="taupier-row">
                <h2 class="taupier-row-title">Description et services proposés</h2>
                <div class="taupier-row-content">
                    <div class="taupier-content" itemprop="articleBody">
                        <?php the_content(); ?>
                    </div>
                </div>
            </div>

            <div class="taupier-row">
                <h2 class="taupier-row-title">Galerie photos</h2>
                <div class="taupier-row-content">
                    <div class="taupier-gallery-slider">
                        <div class="gallery-slider-container">
                            <div class="gallery-slider-wrapper">
                                <?php
                                // Récupérer les IDs des images de la galerie
                                \$gallery_images_ids = get_post_meta(\$post_id, '_taupier_gallery_images', true);
                                if (!is_array(\$gallery_images_ids)) {
                                    \$gallery_images_ids = array();
                                }
                                
                                // Ajouter l'image mise en avant si elle n'est pas déjà dans la galerie
                                if (\$thumbnail_id && !in_array(\$thumbnail_id, \$gallery_images_ids)) {
                                    array_unshift(\$gallery_images_ids, \$thumbnail_id);
                                }

                                if (!empty(\$gallery_images_ids)) {
                                    foreach (\$gallery_images_ids as \$index => \$image_id) {
                                        \$image_medium_large = wp_get_attachment_image_src(\$image_id, 'medium_large');
                                        \$image_full = wp_get_attachment_image_src(\$image_id, 'full');

                                        if (\$image_medium_large && \$image_full) {
                                            echo '<div class="gallery-slide" data-index="' . \$index . '" itemscope itemtype="https://schema.org/ImageObject">';
                                            echo '<meta itemprop="contentUrl" content="' . esc_url(\$image_full[0]) . '">';
                                            echo '<meta itemprop="thumbnailUrl" content="' . esc_url(\$image_medium_large[0]) . '">';
                                            echo '<meta itemprop="caption" content="' . esc_attr(get_the_title(\$image_id)) . '">';
                                            echo '<a href="' . esc_url(\$image_full[0]) . '" class="gallery-image-link" data-lightbox="taupier-gallery" aria-label="Agrandir l\'image ' . (\$index + 1) . '">';
                                            echo '<img src="' . esc_url(\$image_medium_large[0]) . '" alt="' . esc_attr(\$taupier_title . ' - Image ' . (\$index + 1)) . '" class="gallery-image" loading="lazy" itemprop="image">';
                                            echo '</a>';
                                            echo '</div>';
                                        }
                                    }
                                } else {
                                    echo '<p>Aucune image disponible pour la galerie.</p>';
                                }
                                ?>
                            </div>

                            <?php if (!empty(\$gallery_images_ids) && count(\$gallery_images_ids) > 1) : ?>
                                <div class="gallery-slider-controls">
                                    <button class="gallery-prev-btn" aria-label="Image précédente">&laquo;</button>
                                    <div class="gallery-dots">
                                        <?php
                                        for (\$i = 0; \$i < count(\$gallery_images_ids); \$i++) {
                                            echo '<button class="gallery-dot' . (\$i === 0 ? ' active' : '') . '" data-index="' . \$i . '" aria-label="Image ' . (\$i + 1) . '"></button>';
                                        }
                                        ?>
                                    </div>
                                    <button class="gallery-next-btn" aria-label="Image suivante">&raquo;</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="taupier-row">
                <h2 class="taupier-row-title">Équipements recommandés</h2>
                <div class="taupier-row-content">
                    <?php
                    // Appeler la méthode display_taupe_products_highlighted via l'instance du plugin
                    global \$gestion_taupiers;
                    if (isset(\$gestion_taupiers) && method_exists(\$gestion_taupiers, 'display_taupe_products_highlighted')) {
                        echo \$gestion_taupiers->display_taupe_products_highlighted();
                    } else {
                        echo '<p>La fonctionnalité du slider de produits n\'est pas disponible. Vérifiez l\'activation du plugin de gestion des taupiers et de WooCommerce.</p>';
                    }
                    ?>
                </div>
            </div>

            <div class="taupier-row">
                <h2 class="taupier-row-title">Avis des clients</h2>
                <div class="taupier-row-content">
                    <?php
                    \$reviews = get_posts(array(
                        'post_type'      => 'taupier_review',
                        'posts_per_page' => -1,
                        'meta_query'     => array(
                            array(
                                'key'     => '_taupier_review_taupier_id',
                                'value'   => \$post_id,
                                'compare' => '=',
                            ),
                            array(
                                'key'     => '_taupier_review_status',
                                'value'   => 'approved',
                                'compare' => '=',
                            ),
                        ),
                        'orderby'        => 'post_date',
                        'order'          => 'DESC',
                    ));

                    if (!empty(\$reviews)) {
                        echo '<div class="taupier-reviews" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">';

                        echo '<div class="average-rating">';
                        echo '<div class="rating-summary">';
                        echo '<span class="rating-value" itemprop="ratingValue">' . esc_html(\$average_rating_schema) . '</span><span class="rating-max">/5</span>';
                        echo '<meta itemprop="bestRating" content="5">';
                        echo '<meta itemprop="worstRating" content="1">';
                        echo '</div>';

                        echo taupier_display_stars(\$average_rating_schema);

                        echo '<span class="reviews-count">Basé sur <span itemprop="reviewCount">' . esc_html(\$count_reviews_schema) . '</span> avis</span>';
                        echo '</div>';

                        echo '<div class="reviews-filters">';
                        echo '<div class="filter-options">';
                        echo '<span>Filtrer par : </span>';
                        echo '<button class="filter-btn active" data-filter="all">Tous</button>';
                        echo '<button class="filter-btn" data-filter="positive">Les plus positifs</button>';
                        echo '<button class="filter-btn" data-filter="recent">Les plus récents</button>';
                        echo '</div>';
                        echo '</div>';

                        echo '<div class="reviews-list">';
                        foreach (\$reviews as \$index => \$review) {
                            \$author_name = get_post_meta(\$review->ID, '_taupier_review_author_name', true);
                            \$rating = get_post_meta(\$review->ID, '_taupier_review_rating', true);
                            \$date = get_the_date('d/m/Y', \$review->ID);
                            \$iso_date = get_the_date('c', \$review->ID);

                            echo '<div class="review-item" data-date="' . esc_attr(strtotime(\$iso_date)) . '" data-rating="' . esc_attr(\$rating) . '" itemprop="review" itemscope itemtype="https://schema.org/Review">';
                            echo '<div class="review-header">';
                            echo '<span class="review-author" itemprop="author" itemscope itemtype="https://schema.org/Person"><span itemprop="name">' . esc_html(\$author_name) . '</span></span>';
                            echo '<span class="review-date"><meta itemprop="datePublished" content="' . esc_attr(\$iso_date) . '">' . esc_html(\$date) . '</span>';
                            echo '</div>';

                            echo taupier_display_stars(\$rating, true);

                            echo '<div class="review-content" itemprop="reviewBody">' . wpautop(esc_html(\$review->post_content)) . '</div>';
                            echo '</div>';
                        }
                        echo '</div>';
                        echo '</div>';
                    } else {
                        echo '<div class="taupier-reviews-empty">';
                        echo '<p>Aucun avis n\'a encore été publié pour ce taupier.</p>';
                        echo '<p>Soyez le premier à donner votre opinion !</p>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>

            <div class="taupier-faq-form-container">
                <div class="taupier-row taupier-faq-column">
                    <h2 class="taupier-row-title">Questions fréquentes</h2>
                    <div class="taupier-row-content">
                        <?php
                        \$faq = get_post_meta(\$post_id, '_taupier_faq', true);
                        if (is_array(\$faq) && !empty(\$faq)) {
                            \$has_faq = false;
                            foreach (\$faq as \$qa_pair) {
                                if (!empty(\$qa_pair['question']) && !empty(\$qa_pair['reponse'])) {
                                    \$has_faq = true;
                                    break;
                                }
                            }

                            if (\$has_faq) {
                                echo '<div class="taupier-faq" itemscope itemtype="https://schema.org/FAQPage">';

                                echo '<div class="faq-search-container">';
                                echo '<input type="text" id="faq-search" placeholder="Rechercher une question..." aria-label="Rechercher dans la FAQ">';
                                echo '</div>';

                                echo '<div class="faq-items">';
                                foreach (\$faq as \$index => \$qa_pair) {
                                    if (!empty(\$qa_pair['question']) && !empty(\$qa_pair['reponse'])) {
                                        echo '<div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">';
                                        echo '<div class="faq-question" itemprop="name">' . esc_html(\$qa_pair['question']) . '</div>';
                                        echo '<div class="faq-reponse" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">';
                                        echo '<div itemprop="text">' . nl2br(esc_html(\$qa_pair['reponse'])) . '</div>';
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                }
                                echo '</div>';

                                echo '<div id="faq-no-results" style="display: none;">Aucune question ne correspond à votre recherche.</div>';
                                echo '</div>';
                            } else {
                                echo '<div class="taupier-faq-empty">';
                                echo '<p>Aucune question fréquente disponible pour ce taupier.</p>';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="taupier-faq-empty">';
                            echo '<p>Aucune question fréquente disponible pour ce taupier.</p>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>

                <div class="taupier-row taupier-form-column">
                    <h2 class="taupier-row-title">Laissez votre avis</h2>
                    <div class="taupier-row-content">
                        <div class="taupier-review-form">
                            <form id="submit-taupier-review" action="" method="post">
                                <input type="hidden" name="taupier_id" value="<?php echo esc_attr(\$post_id); ?>">

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="author_name">Votre nom <span class="required">*</span></label>
                                        <input type="text" name="author_name" id="author_name" required placeholder="Jean Dupont">
                                    </div>

                                    <div class="form-group">
                                        <label for="author_email">Votre email <span class="required">*</span></label>
                                        <input type="email" name="author_email" id="author_email" required placeholder="jean.dupont@example.com">
                                    </div>
                                </div>

                                <div class="form-group rating-field">
                                    <label>Votre note <span class="required">*</span></label>
                                    <div class="star-rating">
                                        <input type="radio" id="rating-5" name="rating" value="5" required>
                                        <label for="rating-5" title="5 étoiles - Excellent"></label>

                                        <input type="radio" id="rating-4" name="rating" value="4">
                                        <label for="rating-4" title="4 étoiles - Très bien"></label>

                                        <input type="radio" id="rating-3" name="rating" value="3">
                                        <label for="rating-3" title="3 étoiles - Bien"></label>

                                        <input type="radio" id="rating-2" name="rating" value="2">
                                        <label for="rating-2" title="2 étoiles - Moyen"></label>

                                        <input type="radio" id="rating-1" name="rating" value="1">
                                        <label for="rating-1" title="1 étoile - Mauvais"></label>
                                    </div>
                                    <div class="rating-help">Cliquez sur les étoiles pour noter</div>
                                </div>

                                <div class="form-group">
                                    <label for="review_text">Votre avis <span class="required">*</span></label>
                                    <textarea name="review_text" id="review_text" rows="5" required maxlength="500" placeholder="Partagez votre expérience avec ce taupier..."></textarea>
                                    <div class="textarea-counter">0/500 caractères</div>
                                </div>

                                <button type="submit" class="submit-review-button">Envoyer mon avis</button>

                                <div class="form-message"></div>
                            </form>

                            <div class="review-submission-feedback" style="display: none;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--primary-color)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                <h4 style="color: var(--primary-color);">Merci pour votre avis !</h4>
                                <p>Votre commentaire a été soumis avec succès et sera publié après modération.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </article>
        <div class="taupier-row">
            <h2 class="taupier-row-title">Autres taupiers dans votre région</h2>
            <div class="taupier-row-content">
                <div class="related-taupiers-slider">
                    <?php
                    \$current_taupier_id = get_the_ID();
                    \$categories = get_the_terms(\$current_taupier_id, 'taupier_category');

                    if (\$categories && !is_wp_error(\$categories)) {
                        \$category_ids = array_map(function(\$term) {
                            return \$term->term_id;
                        }, \$categories);

                        \$args = array(
                            'post_type'      => 'taupier',
                            'posts_per_page' => 8,
                            'post__not_in'   => array(\$current_taupier_id),
                            'tax_query'      => array(
                                array(
                                    'taxonomy' => 'taupier_category',
                                    'field'    => 'term_id',
                                    'terms'    => \$category_ids,
                                    'operator' => 'IN',
                                ),
                            ),
                            'orderby'        => 'rand',
                            'no_found_rows'  => true,
                            'update_post_meta_cache' => false,
                            'update_post_term_cache' => false,
                        );

                        \$related_taupiers = new WP_Query(\$args);

                        if (\$related_taupiers->have_posts()) {
                            echo '<div class="related-taupiers-wrapper">';

                            while (\$related_taupiers->have_posts()) {
                                \$related_taupiers->the_post();
                                \$related_taupier_id = get_the_ID();
                                \$taupier_title_related = get_the_title();
                                \$taupier_link_related = get_permalink();
                                \$taupier_zone_related = get_post_meta(\$related_taupier_id, '_taupier_zone', true);

                                \$image_url_related = has_post_thumbnail() ? get_the_post_thumbnail_url(\$related_taupier_id, 'medium') : '';

                                \$related_reviews_local = get_posts(array(
                                    'post_type'      => 'taupier_review',
                                    'posts_per_page' => -1,
                                    'meta_query'     => array(
                                        array(
                                            'key'     => '_taupier_review_taupier_id',
                                            'value'   => \$related_taupier_id,
                                            'compare' => '=',
                                        ),
                                        array(
                                            'key'     => '_taupier_review_status',
                                            'value'   => 'approved',
                                            'compare' => '=',
                                        ),
                                    ),
                                    'no_found_rows'  => true,
                                    'update_post_meta_cache' => false,
                                    'update_post_term_cache' => false,
                                ));

                                \$avg_related_rating_local = 0;
                                \$count_related_reviews_local = count(\$related_reviews_local);
                                if (\$count_related_reviews_local > 0) {
                                    \$total_related_rating_local = 0;
                                    foreach (\$related_reviews_local as \$r_review_local) {
                                        \$total_related_rating_local += intval(get_post_meta(\$r_review_local->ID, '_taupier_review_rating', true));
                                    }
                                    \$avg_related_rating_local = round(\$total_related_rating_local / \$count_related_reviews_local, 1);
                                }

                                echo '<div class="related-taupier-card">';
                                echo '<a href="' . esc_url(\$taupier_link_related) . '" class="taupier-card-link">';

                                if (!empty(\$image_url_related)) {
                                    echo '<div class="related-taupier-image">';
                                    echo '<img src="' . esc_url(\$image_url_related) . '" alt="' . esc_attr(\$taupier_title_related) . '" loading="lazy">';
                                    echo '</div>';
                                }

                                echo '<div class="related-taupier-info">';
                                echo '<h3 class="related-taupier-title">' . esc_html(\$taupier_title_related) . '</h3>';

                                if (!empty(\$taupier_zone_related)) {
                                    echo '<p class="related-taupier-zone"><i class="fa fa-map-marker"></i> ' . esc_html(\$taupier_zone_related) . '</p>';
                                }

                                if (\$count_related_reviews_local > 0) {
                                    echo '<div class="related-taupier-rating">';
                                    echo '<span class="rating-value">' . esc_html(\$avg_related_rating_local) . '</span>';
                                    echo taupier_display_stars(\$avg_related_rating_local);
                                    echo '<span class="reviews-count">(' . esc_html(\$count_related_reviews_local) . ')</span>';
                                    echo '</div>';
                                }

                                echo '<div class="related-taupier-button">Voir le profil</div>';
                                echo '</div>';
                                echo '</a>';
                                echo '</div>';
                            }
                            wp_reset_postdata();
                            echo '</div>';

                            echo '<div class="related-slider-controls">';
                            echo '<button class="related-prev-btn" aria-label="Taupiers précédents">&laquo;</button>';
                            echo '<button class="related-next-btn" aria-label="Taupiers suivants">&raquo;</button>';
                            echo '</div>';

                        } else {
                            echo '<p>Aucun autre taupier disponible dans cette catégorie pour le moment.</p>';
                        }
                    } else {
                        echo '<p>Aucune catégorie définie pour ce taupier ou une erreur est survenue.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </main>
</div>

<?php if (!empty(\$telephone)) : ?>
    <div class="quick-contact-btn">
        <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', \$telephone)); ?>" class="call-btn" aria-label="Appeler le taupier">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0  0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
            </svg>
            <span>Appeler</span>
        </a>
    </div>
<?php endif; ?>

<?php
get_footer();
EOT;
                file_put_contents($template_path, $template_content);
            }

            // Template par défaut pour archive-taupier.php
            if ($template_name === 'archive-taupier.php') {
                $template_content = <<<EOT
<?php
/**
 * Template pour l'archive des taupiers
 */
get_header();
?>

<div id="primary" class="content-area taupier-archive-container">
    <main id="main" class="site-main">
        <header class="page-header">
            <h1 class="page-title">
                <?php
                // Le titre de la page est maintenant toujours "Tous les taupiers" ou un titre d'archive générique
                // Les mentions spécifiques aux catégories et tags ont été supprimées
                echo 'Tous les taupiers'; 
                ?>
            </h1>
            <?php
            // La description de la taxonomie a été supprimée
            // \$term_description = term_description();
            // if (!empty(\$term_description)) {
            //     echo '<div class="archive-description">' . \$term_description . '</div>';
            // }
            ?>
        </header>

        <?php if (have_posts()) : ?>
            <div class="taupier-list">
                <?php while (have_posts()) : the_post(); ?>
                    <div class="taupier-item" itemscope itemtype="https://schema.org/LocalBusiness">
                        <h2><a href="<?php the_permalink(); ?>" itemprop="url"><span itemprop="name"><?php the_title(); ?></span></a></h2>
                        
                        <?php if (has_post_thumbnail()) : ?>
                            <a href="<?php the_permalink(); ?>" class="taupier-thumbnail">
                                <?php the_post_thumbnail('medium', array('itemprop' => 'image')); ?>
                            </a>
                        <?php endif; ?>
                        
                        <div class="taupier-excerpt" itemprop="description">
                            <?php the_excerpt(); ?>
                        </div>
                        
                        <?php
                        // Affichage des informations principales
                        \$zone = get_post_meta(get_the_ID(), '_taupier_zone', true);
                        \$experience = get_post_meta(get_the_ID(), '_taupier_experience', true);
                        
                        echo '<div class="taupier-meta">';
                        if (!empty(\$zone)) {
                            echo '<span class="taupier-zone" itemprop="serviceArea"><strong>Zone : </strong>' . esc_html(\$zone) . '</span> | ';
                        }
                        if (!empty(\$experience)) {
                            echo '<span class="taupier-experience"><strong>Expérience : </strong>' . esc_html(\$experience) . ' ans</span>';
                        }
                        echo '</div>';

                        // Récupération des avis pour afficher la note moyenne
                        \$reviews = get_posts(array(
                            'post_type' => 'taupier_review',
                            'posts_per_page' => -1,
                            'meta_query' => array(
                                array(
                                    'key' => '_taupier_review_taupier_id',
                                    'value' => get_the_ID(),
                                    'compare' => '='
                                ),
                                array(
                                    'key' => '_taupier_review_status',
                                    'value' => 'approved',
                                    'compare' => '='
                                )
                            )
                        ));
                        
                        if (!empty(\$reviews)) {
                            // Calcul de la note moyenne
                            \$total_rating = 0;
                            \$count_reviews = count(\$reviews);
                            
                            foreach (\$reviews as \$review) {
                                \$rating = get_post_meta(\$review->ID, '_taupier_review_rating', true);
                                \$total_rating += intval(\$rating);
                            }
                            
                            \$average_rating = \$count_reviews > 0 ? round(\$total_rating / \$count_reviews, 1) : 0;
                            
                            echo '<div class="taupier-item-rating" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">';
                            echo '<span class="rating-value" itemprop="ratingValue">' . \$average_rating . '</span>/5';
                            echo '<meta itemprop="bestRating" content="5">';
                            echo '<span class="reviews-count">(<span itemprop="reviewCount">' . \$count_reviews . '</span> avis)</span>';
                            echo '</div>';
                        }
                        ?>
                        
                        <a href="<?php the_permalink(); ?>" class="taupier-link">Voir les détails</a>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <div class="taupier-pagination">
                <?php the_posts_pagination(); ?>
            </div>
            
        <?php else : ?>
            <p>Aucun taupier trouvé.</p>
        <?php endif; ?>
    </main>
</div>

<?php
get_footer();
EOT;
                file_put_contents($template_path, $template_content);
            }
        }

        return $template_path;
    }

    /**
     * Fonction pour afficher les produits WooCommerce mis en avant sous forme de slider.
     * Cette fonction est maintenant une méthode de la classe Gestion_Taupiers.
     */
    public function display_taupe_products_highlighted() {
        if (!class_exists('WooCommerce')) {
            return '<p>WooCommerce n\'est pas actif. Impossible d\'afficher les produits.</p>';
        }

        // Arguments pour WP_Query pour récupérer des produits aléatoires et non les plus chers.
        // On veut des produits qui peuvent être mis en avant, pas nécessairement les plus chers.
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => 5, // On en veut 5
            'orderby'        => 'rand', // Aléatoire
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => '_featured', // Produits mis en avant
                    'value'   => 'yes',
                    'compare' => '=',
                ),
                // Alternativement, si pas de produits en vedette, prendre des produits en stock
                array(
                    'key'     => '_stock_status',
                    'value'   => 'instock',
                    'compare' => '=',
                ),
            ),
            'post_status' => 'publish', // Only published products
        );
        $loop = new WP_Query($args);
        $products = [];

        // Récupération des produits
        if ($loop->have_posts()) {
            while ($loop->have_posts()) {
                $loop->the_post();
                global $product;
                $products[] = $product;
            }
        }
        wp_reset_postdata();

        // If less than 5 products are found, try to complete with other random in-stock products
        if (count($products) < 5) {
            $remaining_needed = 5 - count($products);
            $existing_product_ids = array_map(function($p) { return $p->get_id(); }, $products);

            $args_fallback = array(
                'post_type'      => 'product',
                'posts_per_page' => $remaining_needed,
                'orderby'        => 'rand',
                'post__not_in'   => $existing_product_ids, // Exclude those already found
                'meta_query'     => array(
                    array(
                        'key'     => '_stock_status',
                        'value'   => 'instock',
                        'compare' => '=',
                    ),
                ),
                'post_status' => 'publish', // Only published products
            );
            $fallback_loop = new WP_Query($args_fallback);
            if ($fallback_loop->have_posts()) {
                while ($fallback_loop->have_posts()) {
                    $fallback_loop->the_post();
                    global $product;
                    $products[] = $product;
                }
            }
            wp_reset_postdata();
        }

        ob_start();

        // Introductions pour le slider de produits
        $introductions = [
            'Découvrez aussi les pièges à taupe QuickTaupe !',
            'Vous préférez piéger vous-mêmes ? Voici nos solutions !',
            'Équipez-vous avec des pièges traditionnels de qualité !'
        ];
        $intro_text = $introductions[array_rand($introductions)];
        ?>

        <div class="taupe-product-section" itemscope itemtype="https://schema.org/Product">
            <meta itemprop="name" content="Pièges à Taupe Professionnels">
            <meta itemprop="description" content="Découvrez notre sélection de pièges à taupe professionnels et écologiques pour une solution durable.">
            <meta itemprop="image" content="<?php echo esc_url(plugin_dir_url(__FILE__) . 'assets/images/piege-taupe-generique.jpg'); ?>">
            
            <?php
            // Calculate aggregate rating for the *general* product category if possible
            // This would require querying all products in relevant categories and their reviews
            // For now, we'll focus on individual product ratings in the loop below.
            // This top-level Product schema acts more as a category/overview page for products.
            ?>

            <div itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                <meta itemprop="price" content="0.00">
                <meta itemprop="priceCurrency" content="EUR">
                <meta itemprop="availability" content="https://schema.org/InStock">
                <meta itemprop="url" content="<?php echo esc_url(home_url('/produits-taupes/')); ?>">
            </div>

            <p style="font-size: 1.2rem; font-weight: 600; color: var(--primary-color); margin-top: 0;"><?php echo esc_html($intro_text); ?></p>
            
            <div class="product-slider-container">
                <div class="product-slider-wrapper">
                    <?php foreach ($products as $product) {
                        $average_rating = $product->get_average_rating();
                        $review_count = $product->get_review_count();
                        $sale_price_dates_to = $product->get_date_on_sale_to();
                        ?>
                        <div class="product-item" itemscope itemtype="https://schema.org/Product">
                            <meta itemprop="name" content="<?php echo esc_attr($product->get_name()); ?>">
                            <meta itemprop="url" content="<?php echo esc_url(get_permalink($product->get_id())); ?>">
                            <meta itemprop="image" content="<?php echo esc_url(wp_get_attachment_image_url($product->get_image_id(), 'medium')); ?>">
                            <meta itemprop="description" content="<?php echo esc_attr(wp_trim_words($product->get_description(), 15, '...')); ?>">

                            <?php if ($review_count > 0) : ?>
                                <div itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
                                    <meta itemprop="ratingValue" content="<?php echo esc_attr(round($average_rating, 1)); ?>">
                                    <meta itemprop="reviewCount" content="<?php echo esc_attr($review_count); ?>">
                                    <meta itemprop="bestRating" content="5">
                                    <meta itemprop="worstRating" content="1">
                                </div>
                            <?php endif; ?>

                            <div itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                                <meta itemprop="url" content="<?php echo esc_url(get_permalink($product->get_id())); ?>">
                                <meta itemprop="priceCurrency" content="<?php echo esc_attr(get_woocommerce_currency()); ?>">
                                <meta itemprop="price" content="<?php echo esc_attr($product->get_price()); ?>">
                                <meta itemprop="availability" content="https://schema.org/<?php echo $product->is_in_stock() ? 'InStock' : 'OutOfStock'; ?>">
                                <?php if ($sale_price_dates_to) : ?>
                                    <meta itemprop="priceValidUntil" content="<?php echo esc_attr($sale_price_dates_to->format('Y-m-d')); ?>">
                                <?php endif; ?>
                            </div>

                            <a href="<?php echo esc_url(get_permalink($product->get_id())); ?>" title="<?php echo esc_attr($product->get_name()); ?>">
                                <?php echo $product->get_image('medium', array('class' => 'product-thumbnail-img')); ?>
                                <div class="product-title"><?php echo esc_html(wp_trim_words($product->get_name(), 3, '...')); ?></div>
                                <div class="product-price"><?php echo $product->get_price_html(); ?></div>
                                <button class="buy-button">Découvrir</button>
                            </a>
                        </div>
                    <?php } ?>
                </div>
                <?php if (count($products) > 1) : // Afficher les contrôles seulement si plus d'un produit ?>
                    <button class="product-prev-btn" aria-label="Produit précédent">&laquo;</button>
                    <button class="product-next-btn" aria-label="Produit suivant">&raquo;</button>
                <?php endif; ?>
            </div>
        </div>
        <style>
            /* Styles pour le slider de produits - à ajouter dans votre fichier CSS principal pour plus de propreté */
            .taupe-product-section {
                padding: 20px;
                background-color: #ffffff;
                border-radius: 10px;
                margin: 30px 0;
                border: 3px dashed #ffa500;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                position: relative;
            }
            .taupe-product-section:before {
                content: "🎯 En avant !";
                position: absolute;
                top: -15px;
                left: 20px;
                background-color: #ffa500;
                color: white;
                font-size: 12px;
                font-weight: bold;
                padding: 3px 8px;
                border-radius: 5px;
            }
            .product-slider-container {
                position: relative;
                overflow: hidden;
                padding: 0 40px; /* Espace pour les boutons de navigation */
            }
            .product-slider-wrapper {
                display: flex;
                overflow-x: auto;
                scroll-snap-type: x mandatory;
                -webkit-overflow-scrolling: touch; /* pour iOS */
                scrollbar-width: none; /* Firefox */
                -ms-overflow-style: none;  /* IE and Edge */
            }
            .product-slider-wrapper::-webkit-scrollbar {
                display: none; /* Chrome, Safari, Opera */
            }
            .product-item {
                flex: 0 0 auto;
                width: 180px; /* Taille fixe pour chaque élément */
                text-align: center;
                border-radius: 8px;
                padding: 10px;
                background: #f9f9f9;
                scroll-snap-align: center;
                border: 1px solid #ddd;
                transition: transform 0.3s, box-shadow 0.3s;
                margin-right: 10px; /* Espacement entre les produits */
            }
            .product-item:last-child {
                margin-right: 0;
            }
            .product-item:hover {
                transform: translateY(-5px);
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            }
            .product-item img {
                max-width: 100%;
                height: auto;
                border-radius: 5px;
            }
            .product-title {
                font-size: 14px;
                color: #333;
                margin: 5px 0;
                min-height: 42px; /* Pour éviter le décalage si le titre est sur 2 lignes */
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .product-price {
                font-size: 13px;
                color: #888;
                margin-bottom: 10px;
            }
            .buy-button {
                font-size: 12px;
                background-color: #28a745;
                color: white;
                border: none;
                padding: 8px 12px;
                border-radius: 5px;
                cursor: pointer;
                transition: background-color 0.3s;
                width: 100%;
                box-sizing: border-box;
            }
            .buy-button:hover {
                background-color: #218838;
            }

            .product-slider-controls {
                position: absolute;
                top: 50%;
                width: 100%;
                display: flex;
                justify-content: space-between;
                transform: translateY(-50%);
                pointer-events: none; /* Permet de cliquer à travers les boutons si besoin */
            }
            .product-prev-btn, .product-next-btn {
                background-color: rgba(0, 0, 0, 0.5);
                color: white;
                border: none;
                padding: 10px 15px;
                cursor: pointer;
                border-radius: 50%;
                font-size: 1.5rem;
                line-height: 1;
                z-index: 10;
                pointer-events: auto; /* Réactive les clics sur les boutons */
            }
            .product-prev-btn {
                left: 0;
                border-top-left-radius: 0;
                border-bottom-left-radius: 0;
            }
            .product-next-btn {
                right: 0;
                border-top-right-radius: 0;
                border-bottom-right-radius: 0;
            }

            @media (max-width: 768px) {
                .product-item {
                    width: 150px;
                }
                .product-slider-container {
                    padding: 0 20px;
                }
            }
            @media (max-width: 600px) {
                .product-item {
                    width: 130px;
                }
            }
        </style>
        <script>
            jQuery(document).ready(function($) {
                var sliderWrapper = $('.product-slider-wrapper');
                var prevBtn = $('.product-prev-btn');
                var nextBtn = $('.product-next-btn');
                var scrollAmount = 200; // Ajustez la quantité de défilement

                nextBtn.on('click', function() {
                    sliderWrapper.scrollLeft(sliderWrapper.scrollLeft() + scrollAmount);
                });

                prevBtn.on('click', function() {
                    sliderWrapper.scrollLeft(sliderWrapper.scrollLeft() - scrollAmount);
                });

                // Optionnel: masquer/afficher les boutons si le début/fin est atteint
                sliderWrapper.on('scroll', function() {
                    if (sliderWrapper.scrollLeft() === 0) {
                        prevBtn.hide();
                    } else {
                        prevBtn.show();
                    }
                    if (sliderWrapper.scrollLeft() + sliderWrapper.innerWidth() >= sliderWrapper[0].scrollWidth) {
                        nextBtn.hide();
                    } else {
                        nextBtn.show();
                    }
                }).trigger('scroll'); // Déclencher au chargement pour l'état initial
            });
        </script>
        <?php
        return ob_get_clean();
    }
}

// Initialisation du plugin
$gestion_taupiers = new Gestion_Taupiers();

/**
 * Classe pour l'optimisation SEO
 */
class Gestion_Taupiers_SEO {

    /**
     * Constructeur
     */
    public function __construct() {
        // Ajout des métaboxes pour le SEO
        add_action('add_meta_boxes', array($this, 'add_taupier_seo_meta_boxes'));

        // Sauvegarde des métadonnées SEO
        add_action('save_post_taupier', array($this, 'save_taupier_seo_meta'));

        // Modification des titres des pages
        add_filter('document_title_parts', array($this, 'taupier_document_title_parts'), 999); // Higher priority

        // Ajout des données structurées dans le head (handled by the template now for LocalBusiness)
        // add_action('wp_head', array($this, 'taupier_head_metadata'), 1); // This function will now focus on meta tags only

        // Modifications SEO pour l'extrait
        add_filter('get_the_excerpt', array($this, 'taupier_custom_excerpt'), 10, 2);

        // Support pour les sitemaps
        add_filter('wp_sitemaps_post_types', array($this, 'taupier_add_to_sitemap'));

        // Ajout des options pour les archives
        add_action('admin_init', array($this, 'taupier_register_archive_settings'));

        // Ajout d'OpenGraph et Twitter Cards
        add_action('wp_head', array($this, 'taupier_opengraph_twitter_tags')); // Combined and renamed
    }

    /**
     * Ajoute une métabox pour les options SEO du taupier
     */
    public function add_taupier_seo_meta_boxes() {
        add_meta_box(
            'taupier_seo_meta',
            'Optimisation SEO du taupier',
            array($this, 'taupier_seo_meta_callback'),
            'taupier',
            'normal',
            'default'
        );
    }

    /**
     * Affiche le contenu de la métabox SEO
     */
    public function taupier_seo_meta_callback($post) {
        // Ajout d'un nonce pour la sécurité
        wp_nonce_field('taupier_seo_meta_box', 'taupier_seo_meta_box_nonce');

        // Récupération des valeurs existantes
        $seo_title = get_post_meta($post->ID, '_taupier_seo_title', true);
        $seo_description = get_post_meta($post->ID, '_taupier_seo_description', true);
        $seo_keywords = get_post_meta($post->ID, '_taupier_seo_keywords', true);
        $seo_focus_keyword = get_post_meta($post->ID, '_taupier_seo_focus_keyword', true);

        // Affichage des champs
        ?>
        <p><strong>Ces champs permettent d'optimiser le référencement de la fiche du taupier dans les moteurs de recherche.</strong></p>

        <table class="form-table">
            <tr>
                <th><label for="taupier_seo_title">Titre SEO</label></th>
                <td>
                    <input type="text" id="taupier_seo_title" name="taupier_seo_title" value="<?php echo esc_attr($seo_title); ?>" class="large-text">
                    <p class="description">Titre optimisé pour les moteurs de recherche (max. 70 caractères). Laissez vide pour utiliser le titre par défaut.</p>
                    <div class="taupier-seo-counter"><span id="taupier_seo_title_counter">0</span>/70</div>
                </td>
            </tr>
            <tr>
                <th><label for="taupier_seo_description">Description SEO</label></th>
                <td>
                    <textarea id="taupier_seo_description" name="taupier_seo_description" rows="3" class="large-text"><?php echo esc_textarea($seo_description); ?></textarea>
                    <p class="description">Description optimisée pour les moteurs de recherche (max. 160 caractères). Laissez vide pour utiliser l'extrait par défaut.</p>
                    <div class="taupier-seo-counter"><span id="taupier_seo_description_counter">0</span>/160</div>
                </td>
            </tr>
            <tr>
                <th><label for="taupier_seo_keywords">Mots-clés SEO</label></th>
                <td>
                    <input type="text" id="taupier_seo_keywords" name="taupier_seo_keywords" value="<?php echo esc_attr($seo_keywords); ?>" class="large-text">
                    <p class="description">Mots-clés séparés par des virgules (ex: taupier, taupes, jardin, etc.).</p>
                </td>
            </tr>
            <tr>
                <th><label for="taupier_seo_focus_keyword">Mot-clé principal</label></th>
                <td>
                    <input type="text" id="taupier_seo_focus_keyword" name="taupier_seo_focus_keyword" value="<?php echo esc_attr($seo_focus_keyword); ?>" class="regular-text">
                    <p class="description">Le mot-clé principal sur lequel vous souhaitez positionner cette fiche.</p>
                </td>
            </tr>
        </table>

        <script>
        jQuery(document).ready(function($) {
            // Compteur de caractères pour le titre SEO
            $('#taupier_seo_title').on('input', function() {
                var charCount = $(this).val().length;
                $('#taupier_seo_title_counter').text(charCount);

                if (charCount > 70) {
                    $('#taupier_seo_title_counter').css('color', 'red');
                } else {
                    $('#taupier_seo_title_counter').css('color', '');
                }
            }).trigger('input');

            // Compteur de caractères pour la description SEO
            $('#taupier_seo_description').on('input', function() {
                var charCount = $(this).val().length;
                $('#taupier_seo_description_counter').text(charCount);

                if (charCount > 160) {
                    $('#taupier_seo_description_counter').css('color', 'red');
                } else {
                    $('#taupier_seo_description_counter').css('color', '');
                }
            }).trigger('input');
        });
        </script>
        <?php
    }

    /**
     * Sauvegarde les métadonnées SEO
     */
    public function save_taupier_seo_meta($post_id) {
        // Vérification du nonce
        if (!isset($_POST['taupier_seo_meta_box_nonce']) || !wp_verify_nonce($_POST['taupier_seo_meta_box_nonce'], 'taupier_seo_meta_box')) {
            return;
        }

        // Vérification des autorisations
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Sauvegarde des champs SEO
        if (isset($_POST['taupier_seo_title'])) {
            update_post_meta($post_id, '_taupier_seo_title', sanitize_text_field($_POST['taupier_seo_title']));
        }

        if (isset($_POST['taupier_seo_description'])) {
            update_post_meta($post_id, '_taupier_seo_description', sanitize_textarea_field($_POST['taupier_seo_description']));
        }

        if (isset($_POST['taupier_seo_keywords'])) {
            update_post_meta($post_id, '_taupier_seo_keywords', sanitize_text_field($_POST['taupier_seo_keywords']));
        }

        if (isset($_POST['taupier_seo_focus_keyword'])) {
            update_post_meta($post_id, '_taupier_seo_focus_keyword', sanitize_text_field($_POST['taupier_seo_focus_keyword']));
        }
    }

    /**
     * Modifie le titre des pages de taupiers
     */
    public function taupier_document_title_parts($title_parts) {
        if (is_singular('taupier')) {
            global $post;
            if (is_object($post)) { // S'assurer que $post est un objet
                // Utilisation du titre SEO personnalisé s'il existe
                $seo_title = get_post_meta($post->ID, '_taupier_seo_title', true);

                if (!empty($seo_title)) {
                    $title_parts['title'] = $seo_title;
                } else {
                    // Titre par défaut amélioré
                    $default_title = $post->post_title . ' | Taupier Professionnel';
                    $zone = get_post_meta($post->ID, '_taupier_zone', true);
                    if (!empty($zone)) {
                        $default_title .= ' - ' . $zone;
                    }
                    $title_parts['title'] = $default_title;
                }
            }
        } elseif (is_post_type_archive('taupier')) {
            $archive_title = get_option('taupier_archive_title', 'Annuaire des Taupiers Professionnels | Trouvez un Taupier Près de Chez Vous');
            $title_parts['title'] = $archive_title;
        } elseif (is_tax('taupier_category')) {
            $term = get_queried_object();
            if (is_object($term)) { // S'assurer que $term est un objet
                 $title_parts['title'] = 'Taupiers en ' . $term->name . ' | Annuaire Professionnel';
            }
        } elseif (is_tax('taupier_tag')) {
             $term = get_queried_object();
            if (is_object($term)) { // S'assurer que $term est un objet
                $title_parts['title'] = 'Experts Taupiers : ' . $term->name . ' | Annuaire Spécialisé';
            }
        }

        return $title_parts;
    }

    /**
     * Ajoute des métadonnées SEO dans le head (meta description et keywords)
     */
    public function taupier_head_metadata() {
        if (is_singular('taupier')) {
            global $post;
            if(!is_object($post)) return; // S'assurer que $post est un objet

            // Récupération des données SEO personnalisées
            $seo_description = get_post_meta($post->ID, '_taupier_seo_description', true);
            $seo_keywords = get_post_meta($post->ID, '_taupier_seo_keywords', true);

            // Si pas de description SEO, on utilise l'extrait
            if (empty($seo_description)) {
                if (has_excerpt($post->ID)) {
                    $seo_description = get_the_excerpt($post->ID);
                } else {
                    $seo_description = wp_trim_words(strip_shortcodes(wp_strip_all_tags($post->post_content)), 30, '...');
                }
            }
            $seo_description = sanitize_text_field($seo_description); // Ensure description is clean

            // If no SEO keywords, generate from categories and tags
            if (empty($seo_keywords)) {
                $keywords = array();

                // Add categories
                $categories = get_the_terms($post->ID, 'taupier_category');
                if ($categories && !is_wp_error($categories)) {
                    foreach ($categories as $category) {
                        $keywords[] = $category->name;
                    }
                }

                // Add tags
                $tags = get_the_terms($post->ID, 'taupier_tag');
                if ($tags && !is_wp_error($tags)) {
                    foreach ($tags as $tag) {
                        $keywords[] = $tag->name;
                    }
                }

                // Add default keywords
                $keywords = array_merge($keywords, array('taupier', 'taupes', 'jardin', 'service'));

                $seo_keywords = implode(', ', array_unique($keywords));
            }
            $seo_keywords = sanitize_text_field($seo_keywords); // Ensure keywords are clean

            // Output meta tags
            if (!empty($seo_description)) {
                echo '<meta name="description" content="' . esc_attr($seo_description) . '">' . "\n";
            }

            if (!empty($seo_keywords)) {
                echo '<meta name="keywords" content="' . esc_attr($seo_keywords) . '">' . "\n";
            }

            // Canonical tag
            echo '<link rel="canonical" href="' . esc_url(get_permalink($post->ID)) . '">' . "\n";

            // Language tag (usually handled by WordPress itself, but good to ensure)
            echo '<meta property="og:locale" content="' . esc_attr(get_locale()) . '">' . "\n";

        } elseif (is_post_type_archive('taupier')) {
            $archive_description = get_option('taupier_archive_description', 'Découvrez notre annuaire de taupiers professionnels. Trouvez un expert en élimination de taupes près de chez vous pour protéger votre jardin.');
            echo '<meta name="description" content="' . esc_attr($archive_description) . '">' . "\n";
            echo '<link rel="canonical" href="' . esc_url(get_post_type_archive_link('taupier')) . '">' . "\n";
        } elseif (is_tax('taupier_category') || is_tax('taupier_tag')) {
            $term = get_queried_object();
            if(is_object($term)){ // S'assurer que $term est un objet
                $term_description = term_description($term->term_id, $term->taxonomy); // term_description() sanitizes
                if (empty($term_description)) {
                    if ($term->taxonomy === 'taupier_category') {
                        $term_description = 'Trouvez les meilleurs taupiers professionnels dans la catégorie ' . $term->name . '. Services experts pour éliminer les taupes de votre jardin.';
                    } elseif ($term->taxonomy === 'taupier_tag') {
                         $term_description = 'Liste de taupiers avec la compétence ' . $term->name . '. Contactez un spécialiste pour un service efficace.';
                    }
                }
                echo '<meta name="description" content="' . esc_attr(strip_tags($term_description)) . '">' . "\n"; // strip_tags pour enlever le <p> éventuel
                echo '<link rel="canonical" href="' . esc_url(get_term_link($term)) . '">' . "\n";
            }
        }
    }

    /**
     * Modifie l'extrait pour l'optimisation SEO
     */
    public function taupier_custom_excerpt($excerpt, $post) {
        if (is_object($post) && $post->post_type === 'taupier') { // Vérifier si $post est un objet
            // Récupération de la description SEO personnalisée
            $seo_description = get_post_meta($post->ID, '_taupier_seo_description', true);

            if (!empty($seo_description)) {
                return $seo_description;
            }

            // Si pas d'extrait défini, créer un extrait optimisé
            if (empty($excerpt)) {
                $content = $post->post_content;
                $content = strip_shortcodes($content);
                $content = wp_strip_all_tags($content);

                // Ajout d'informations importantes du taupier
                $zone = get_post_meta($post->ID, '_taupier_zone', true);
                $experience = get_post_meta($post->ID, '_taupier_experience', true);

                $taupier_info = '';
                if (!empty($zone)) {
                    $taupier_info .= 'Zone d\'intervention : ' . $zone . '. ';
                }
                if (!empty($experience)) {
                    $taupier_info .= 'Expérience : ' . $experience . ' ans. ';
                }

                $excerpt = $taupier_info . wp_trim_words($content, 20, '...');
            }
        }

        return $excerpt;
    }

    /**
     * Ajoute le type de contenu taupier aux sitemaps
     */
    public function taupier_add_to_sitemap($post_types) {
        $post_types['taupier'] = get_post_type_object('taupier');
        return $post_types;
    }

    /**
     * Enregistre les options pour les archives de taupiers
     */
    public function taupier_register_archive_settings() {
        register_setting('general', 'taupier_archive_description', 'sanitize_textarea_field');
        register_setting('general', 'taupier_archive_title', 'sanitize_text_field'); // New: for archive title

        add_settings_section(
            'taupier_archive_section',
            'Paramètres SEO de l\'annuaire des taupiers',
            array($this, 'taupier_archive_section_callback'),
            'general'
        );

        add_settings_field(
            'taupier_archive_title', // New field
            'Titre SEO de l\'annuaire',
            array($this, 'taupier_archive_title_callback'),
            'general',
            'taupier_archive_section'
        );

        add_settings_field(
            'taupier_archive_description',
            'Description de l\'annuaire',
            array($this, 'taupier_archive_description_callback'),
            'general',
            'taupier_archive_section'
        );
    }

    /**
     * Callback pour la section des archives
     */
    public function taupier_archive_section_callback() {
        echo '<p>Paramètres pour optimiser le référencement de l\'annuaire des taupiers.</p>';
    }

    /**
     * Callback for the archive SEO title field.
     */
    public function taupier_archive_title_callback() {
        $value = get_option('taupier_archive_title', 'Annuaire des Taupiers Professionnels | Trouvez un Taupier Près de Chez Vous');
        echo '<input type="text" name="taupier_archive_title" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Titre SEO pour la page d\'archive des taupiers (max. 70 caractères).</p>';
    }

    /**
     * Callback pour le champ de description des archives
     */
    public function taupier_archive_description_callback() {
        $value = get_option('taupier_archive_description', 'Découvrez notre annuaire de taupiers professionnels. Trouvez un expert en élimination de taupes près de chez vous pour protéger votre jardin.');
        echo '<textarea name="taupier_archive_description" rows="4" cols="50" class="regular-text">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">Description affichée sur la page principale de l\'annuaire des taupiers et utilisée pour le SEO.</p>';
    }

    /**
     * Ajout des balises OpenGraph et Twitter Cards
     */
    public function taupier_opengraph_twitter_tags() {
        $og_title = '';
        $og_description = '';
        $og_image = '';
        $og_url = '';
        $og_type = 'website'; // Default type

        if (is_singular('taupier')) {
            global $post;
            if(!is_object($post)) return; // S'assurer que $post est un objet

            $og_title = get_post_meta($post->ID, '_taupier_seo_title', true);
            if (empty($og_title)) {
                $og_title = get_the_title($post->ID);
            }

            $og_description = get_post_meta($post->ID, '_taupier_seo_description', true);
            if (empty($og_description)) {
                $og_description = has_excerpt($post->ID) ? get_the_excerpt($post->ID) : wp_trim_words(strip_shortcodes(wp_strip_all_tags($post->post_content)), 30, '...');
            }

            if (has_post_thumbnail($post->ID)) {
                $og_image = get_the_post_thumbnail_url($post->ID, 'large');
            }
            $og_url = get_permalink($post->ID);
            $og_type = 'article';

        } elseif (is_post_type_archive('taupier')) {
            $og_title = get_option('taupier_archive_title', 'Annuaire des Taupiers Professionnels');
            $og_description = get_option('taupier_archive_description', 'Découvrez notre annuaire de taupiers professionnels.');
            $og_url = get_post_type_archive_link('taupier');

        } elseif (is_tax('taupier_category') || is_tax('taupier_tag')) {
            $term = get_queried_object();
             if(is_object($term)){ // S'assurer que $term est un objet
                if ($term->taxonomy === 'taupier_category') {
                    $og_title = 'Taupiers en ' . $term->name . ' | Annuaire Professionnel';
                } elseif ($term->taxonomy === 'taupier_tag') {
                    $og_title = 'Experts Taupiers : ' . $term->name . ' | Annuaire Spécialisé';
                } else {
                    $og_title = get_option('taupier_archive_title', 'Annuaire des Taupiers Professionnels');
                }

                $og_description = term_description($term->term_id, $term->taxonomy);
                if(empty($og_description)){
                    if ($term->taxonomy === 'taupier_category') {
                         $og_description = 'Trouvez les meilleurs taupiers professionnels dans la catégorie ' . $term->name . '.';
                    } elseif ($term->taxonomy === 'taupier_tag') {
                        $og_description = 'Liste de taupiers avec la compétence ' . $term->name . '.';
                    } else {
                        $og_description = get_option('taupier_archive_description', 'Découvrez notre annuaire de taupiers professionnels.');
                    }
                }
                $og_url = get_term_link($term);
             }
        }

        if (!empty($og_title)) { // Output only if we have a title
            // Ensure values are sanitized
            $og_title = sanitize_text_field($og_title);
            $og_description = sanitize_text_field(strip_tags($og_description)); // strip_tags for descriptions from term_description
            $og_url = esc_url($og_url);
            $og_image = esc_url($og_image);

            // Output OpenGraph tags
            echo '<meta property="og:title" content="' . $og_title . '">' . "\n";
            echo '<meta property="og:description" content="' . $og_description . '">' . "\n";
            echo '<meta property="og:type" content="' . $og_type . '">' . "\n";
            echo '<meta property="og:url" content="' . $og_url . '">' . "\n";

            if (!empty($og_image)) {
                echo '<meta property="og:image" content="' . $og_image . '">' . "\n";
            }

            // Site name
            echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";

            // Twitter Cards
            echo '<meta name="twitter:card" content="summary_large_image">' . "\n"; // summary_large_image is better if an image exists
            echo '<meta name="twitter:title" content="' . $og_title . '">' . "\n";
            echo '<meta name="twitter:description" content="' . $og_description . '">' . "\n";

            if (!empty($og_image)) {
                echo '<meta name="twitter:image" content="' . $og_image . '">' . "\n";
            }
        }
    }
}

// Initialisation de la classe SEO
$gestion_taupiers_seo = new Gestion_Taupiers_SEO();

// Include external files
require_once plugin_dir_path(__FILE__) . 'taupier-sitemap.php';
require_once plugin_dir_path(__FILE__) . 'taupier-search.php';
