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
 * @param float $average_rating La note moyenne.
 * @param bool  $schema_org Indique si les microdonnées Schema.org doivent être incluses.
 * @return string Le HTML des étoiles.
 */
function taupier_display_stars($average_rating, $schema_org = false) {
    $output = '';
    $whole_stars = floor($average_rating);
    $half_star = ($average_rating - $whole_stars) >= 0.25 && ($average_rating - $whole_stars) < 0.75; // Adjusts for better half-star detection
    $empty_stars = 5 - $whole_stars - ($half_star ? 1 : 0);

    if ($schema_org) {
        $output .= '<div class="rating-stars" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">';
        $output .= '<meta itemprop="worstRating" content="1">';
        $output .= '<meta itemprop="bestRating" content="5">';
        $output .= '<meta itemprop="ratingValue" content="' . esc_attr(round($average_rating, 1)) . '">';
    } else {
        $output .= '<div class="rating-stars">';
    }

    // Full stars
    for ($i = 0; $i < $whole_stars; $i++) {
        $output .= '<span class="star full" aria-hidden="true">&#9733;</span>'; // Unicode star
    }

    // Half star
    if ($half_star) {
        $output .= '<span class="star half" aria-hidden="true">&#9733;</span>'; // Unicode star
    }

    // Empty stars
    for ($i = 0; $i < $empty_stars; $i++) {
        $output .= '<span class="star empty" aria-hidden="true">&#9734;</span>'; // Unicode empty star
    }
    $output .= '</div>'; // .rating-stars

    return $output;
}

?>

<div id="primary" class="content-area taupier-single-container">
    <main id="main" class="site-main">
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?> itemscope itemtype="https://schema.org/LocalBusiness">
            <?php
            // Récupérer les informations de base pour Schema.org
            $post_id = get_the_ID();
            $taupier_title = get_the_title();
            $taupier_permalink = get_permalink();
            $taupier_content = get_the_content();
            $taupier_excerpt = get_the_excerpt();

            $telephone = get_post_meta($post_id, '_taupier_telephone', true);
            if (empty($telephone)) {
                $telephone = get_post_meta($post_id, '_taupier_phone', true);
            }
            $email = get_post_meta($post_id, '_taupier_email', true);
            $zone = get_post_meta($post_id, '_taupier_zone', true);
            $experience = get_post_meta($post_id, '_taupier_experience', true);
            $adresse = get_post_meta($post_id, '_taupier_adresse', true);
            $horaires_text = get_post_meta($post_id, '_taupier_horaires', true);
            $ville = get_post_meta($post_id, '_taupier_ville', true);
            $code_postal = get_post_meta($post_id, '_taupier_code_postal', true);

            // Gérer les images pour Schema.org
            $main_image_url = '';
            $thumbnail_id = get_post_thumbnail_id($post_id);
            if ($thumbnail_id) {
                $image_array = wp_get_attachment_image_src($thumbnail_id, 'full');
                if ($image_array) {
                    $main_image_url = $image_array[0];
                }
            }
            if (empty($main_image_url)) {
                $attached_images = get_attached_media('image', $post_id);
                if (!empty($attached_images)) {
                    $first_image = reset($attached_images);
                    $image_array = wp_get_attachment_image_src($first_image->ID, 'full');
                    if ($image_array) {
                        $main_image_url = $image_array[0];
                    }
                }
            }

            // Calcul de la note moyenne et du nombre d'avis pour Schema.org
            $reviews_for_schema = get_posts(array(
                'post_type'      => 'taupier_review',
                'posts_per_page' => -1,
                'meta_query'     => array(
                    array(
                        'key'     => '_taupier_review_taupier_id',
                        'value'   => $post_id,
                        'compare' => '=',
                    ),
                    array(
                        'key'     => '_taupier_review_status',
                        'value'   => 'approved',
                        'compare' => '=',
                    ),
                ),
            ));
            $total_rating_schema = 0;
            $count_reviews_schema = count($reviews_for_schema);
            foreach ($reviews_for_schema as $review_schema) {
                $total_rating_schema += intval(get_post_meta($review_schema->ID, '_taupier_review_rating', true));
            }
            $average_rating_schema = $count_reviews_schema > 0 ? round($total_rating_schema / $count_reviews_schema, 1) : 0;
            ?>

            <meta itemprop="url" content="<?php echo esc_url($taupier_permalink); ?>">
            <?php if (!empty($main_image_url)) : ?>
                <meta itemprop="image" content="<?php echo esc_url($main_image_url); ?>">
            <?php endif; ?>
            <meta itemprop="name" content="<?php echo esc_attr($taupier_title); ?>">
            <meta itemprop="description" content="<?php echo esc_attr(wp_trim_words(strip_tags($taupier_excerpt ? $taupier_excerpt : $taupier_content), 30, '...')); ?>">

            <?php if (!empty($telephone)) : ?>
                <meta itemprop="telephone" content="<?php echo esc_attr($telephone); ?>">
            <?php endif; ?>
            <?php if (!empty($email)) : ?>
                <meta itemprop="email" content="<?php echo esc_attr($email); ?>">
            <?php endif; ?>

            <?php if (!empty($adresse) || !empty($ville) || !empty($code_postal)) : ?>
            <div itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
                <?php if (!empty($adresse)) : ?>
                    <meta itemprop="streetAddress" content="<?php echo esc_attr($adresse); ?>">
                <?php endif; ?>
                <?php if (!empty($ville)) : ?>
                    <meta itemprop="addressLocality" content="<?php echo esc_attr($ville); ?>">
                <?php endif; ?>
                <?php if (!empty($code_postal)) : ?>
                    <meta itemprop="postalCode" content="<?php echo esc_attr($code_postal); ?>">
                <?php endif; ?>
                <meta itemprop="addressCountry" content="FR">
            </div>
            <?php endif; ?>

            <?php
            // Ajout du priceRange (facultatif, mais aide Google)
            // Adapte ceci à la réalité de tes tarifs, ex: "€€" pour moyen, ou "50€ - 200€"
            echo '<meta itemprop="priceRange" content="€€">'; // Exemple: tu peux le modifier

            // Gestion des horaires pour Schema.org
            if (!empty($horaires_text)) {
                $days_of_week = [
                    'Lundi' => 'http://schema.org/Monday',
                    'Mardi' => 'http://schema.org/Tuesday',
                    'Mercredi' => 'http://schema.org/Wednesday',
                    'Jeudi' => 'http://schema.org/Thursday',
                    'Vendredi' => 'http://schema.org/Friday',
                    'Samedi' => 'http://schema.org/Saturday',
                    'Dimanche' => 'http://schema.org/Sunday',
                ];
                $parsed_hours = [];

                // Regex plus robuste pour capturer les jours et les heures
                // Ex: "Lundi-Vendredi: 8h-18h", "Samedi: 9h-12h", "7 jours sur 7"
                if (preg_match_all('/(Lundi|Mardi|Mercredi|Jeudi|Vendredi|Samedi|Dimanche)(?:-(\w+))?:\s*(\d{1,2}h(\d{2})?)?\s*-\s*(\d{1,2}h(\d{2})?)?|\s*(?:7 jours sur 7|24h\/24)/i', $horaires_text, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        if (isset($match[0]) && (strpos(strtolower($match[0]), '7 jours sur 7') !== false || strpos(strtolower($match[0]), '24h/24') !== false)) {
                            // Cas "7 jours sur 7" ou "24h/24"
                            $parsed_hours[] = [
                                'dayOfWeek' => 'http://schema.org/Monday http://schema.org/Tuesday http://schema.org/Wednesday http://schema.org/Thursday http://schema.org/Friday http://schema.org/Saturday http://schema.org/Sunday',
                                'opens' => '00:00',
                                'closes' => '23:59'
                            ];
                        } elseif (isset($match[1]) && isset($match[3]) && isset($match[5])) {
                            // Cas avec jours et heures spécifiques
                            $day_start_name = trim($match[1]);
                            $day_end_name = isset($match[2]) && !empty($match[2]) ? trim($match[2]) : $day_start_name;
                            $open_time = trim($match[3]);
                            $close_time = trim($match[5]);

                            $open_time_formatted = str_replace('h', ':', $open_time);
                            if (substr($open_time_formatted, -1) === ':') $open_time_formatted .= '00';
                            $close_time_formatted = str_replace('h', ':', $close_time);
                            if (substr($close_time_formatted, -1) === ':') $close_time_formatted .= '00';

                            // Map French day names to Schema.org day URIs
                            $start_day_uri = array_key_exists($day_start_name, $days_of_week) ? $days_of_week[$day_start_name] : '';
                            $end_day_uri = array_key_exists($day_end_name, $days_of_week) ? $days_of_week[$day_end_name] : '';

                            if ($start_day_uri && $end_day_uri) {
                                $day_uris = array_values($days_of_week);
                                $start_index = array_search($start_day_uri, $day_uris);
                                $end_index = array_search($end_day_uri, $day_uris);

                                if ($start_index !== false && $end_index !== false && $start_index <= $end_index) {
                                    for ($i = $start_index; $i <= $end_index; $i++) {
                                        $parsed_hours[] = [
                                            'dayOfWeek' => $day_uris[$i],
                                            'opens' => $open_time_formatted,
                                            'closes' => $close_time_formatted
                                        ];
                                    }
                                } elseif ($start_index !== false) { // Single day case
                                    $parsed_hours[] = [
                                        'dayOfWeek' => $start_day_uri,
                                        'opens' => $open_time_formatted,
                                        'closes' => $close_time_formatted
                                    ];
                                }
                            }
                        }
                    }
                }

                // Affichage des balises openingHoursSpecification
                if (!empty($parsed_hours)) {
                    foreach ($parsed_hours as $oh_spec) {
                        echo '<div itemprop="openingHoursSpecification" itemscope itemtype="https://schema.org/OpeningHoursSpecification">' . "\n";
                        echo '<meta itemprop="dayOfWeek" content="' . esc_attr($oh_spec['dayOfWeek']) . '">' . "\n";
                        echo '<meta itemprop="opens" content="' . esc_attr($oh_spec['opens']) . '">' . "\n";
                        echo '<meta itemprop="closes" content="' . esc_attr($oh_spec['closes']) . '">' . "\n";
                        echo '</div>' . "\n";
                    }
                } else {
                    // Fallback si le parsing échoue, utiliser la propriété générique openingHours
                    echo '<meta itemprop="openingHours" content="' . esc_attr($horaires_text) . '">' . "\n";
                }
            }
            ?>

            <?php if ($count_reviews_schema > 0) : ?>
                <div itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
                    <meta itemprop="ratingValue" content="<?php echo esc_attr($average_rating_schema); ?>">
                    <meta itemprop="reviewCount" content="<?php echo esc_attr($count_reviews_schema); ?>">
                    <meta itemprop="bestRating" content="5">
                    <meta itemprop="worstRating" content="1">
                </div>
            <?php endif; ?>

            <div class="taupier-row">
                <h1 class="taupier-row-title">Taupier professionnel <span class="entry-title-subtitle" itemprop="name"><?php echo esc_html($taupier_title); ?></span></h1>
                <div class="taupier-row-content">
                    <div class="taupier-main-info-row">
                        <div class="taupier-main-image-column">
                            <?php if (!empty($main_image_url)) : ?>
                                <div class="taupier-featured-image">
                                    <img src="<?php echo esc_url($main_image_url); ?>" alt="<?php echo esc_attr($taupier_title . ' - Photo principale'); ?>" class="taupier-thumbnail-img">
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
                                        <?php if (!empty($telephone)) : ?>
                                            <li><strong>Téléphone :</strong> <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', $telephone)); ?>" itemprop="telephone"><?php echo esc_html($telephone); ?></a></li>
                                        <?php endif; ?>

                                        <?php if (!empty($email)) : ?>
                                            <li><strong>Email :</strong> <a href="mailto:<?php echo esc_attr($email); ?>" itemprop="email"><?php echo esc_html($email); ?></a></li>
                                        <?php endif; ?>

                                        <?php if (!empty($zone)) : ?>
                                            <li><strong>Zone d'intervention :</strong> <span itemprop="areaServed"><?php echo esc_html($zone); ?></span></li>
                                        <?php endif; ?>

                                        <?php if (!empty($experience)) : ?>
                                            <li><strong>Expérience :</strong> <span><?php echo esc_html($experience); ?> ans</span></li>
                                        <?php endif; ?>

                                        <?php if (!empty($adresse) || !empty($ville) || !empty($code_postal)) : ?>
                                            <li>
                                                <strong>Adresse :</strong>
                                                <address>
                                                    <?php
                                                    if (!empty($adresse)) :
                                                        echo nl2br(esc_html($adresse));
                                                    endif;
                                                    if (!empty($ville)) :
                                                        echo '<br><span itemprop="addressLocality">' . esc_html($ville) . '</span>';
                                                    endif;
                                                    if (!empty($code_postal)) :
                                                        echo ' <span itemprop="postalCode">' . esc_html($code_postal) . '</span>';
                                                    endif;
                                                    ?>
                                                </address>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>

                                <?php if (!empty($horaires_text)) : ?>
                                    <div class="taupier-hours">
                                        <h2>Horaires d'intervention</h2>
                                        <div class="horaires-content"><?php echo nl2br(esc_html($horaires_text)); ?></div>
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
                                $gallery_images_ids = get_post_meta($post_id, '_taupier_gallery_images', true);
                                if (!is_array($gallery_images_ids)) {
                                    $gallery_images_ids = array();
                                }
                                
                                // Ajouter l'image mise en avant si elle n'est pas déjà dans la galerie
                                if ($thumbnail_id && !in_array($thumbnail_id, $gallery_images_ids)) {
                                    array_unshift($gallery_images_ids, $thumbnail_id);
                                }

                                if (!empty($gallery_images_ids)) {
                                    foreach ($gallery_images_ids as $index => $image_id) {
                                        $image_medium_large = wp_get_attachment_image_src($image_id, 'medium_large');
                                        $image_full = wp_get_attachment_image_src($image_id, 'full');

                                        if ($image_medium_large && $image_full) {
                                            echo '<div class="gallery-slide" data-index="' . $index . '" itemscope itemtype="https://schema.org/ImageObject">';
                                            echo '<meta itemprop="contentUrl" content="' . esc_url($image_full[0]) . '">';
                                            echo '<meta itemprop="thumbnailUrl" content="' . esc_url($image_medium_large[0]) . '">';
                                            echo '<meta itemprop="caption" content="' . esc_attr(get_the_title($image_id)) . '">';
                                            echo '<a href="' . esc_url($image_full[0]) . '" class="gallery-image-link" data-lightbox="taupier-gallery" aria-label="Agrandir l\'image ' . ($index + 1) . '">';
                                            echo '<img src="' . esc_url($image_medium_large[0]) . '" alt="' . esc_attr($taupier_title . ' - Image ' . ($index + 1)) . '" class="gallery-image" loading="lazy" itemprop="image">';
                                            echo '</a>';
                                            echo '</div>';
                                        }
                                    }
                                } else {
                                    echo '<p>Aucune image disponible pour la galerie.</p>';
                                }
                                ?>
                            </div>

                            <?php if (!empty($gallery_images_ids) && count($gallery_images_ids) > 1) : ?>
                                <div class="gallery-slider-controls">
                                    <button class="gallery-prev-btn" aria-label="Image précédente">&laquo;</button>
                                    <div class="gallery-dots">
                                        <?php
                                        for ($i = 0; $i < count($gallery_images_ids); $i++) {
                                            echo '<button class="gallery-dot' . ($i === 0 ? ' active' : '') . '" data-index="' . $i . '" aria-label="Image ' . ($i + 1) . '"></button>';
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
                    global $gestion_taupiers;
                    if (isset($gestion_taupiers) && method_exists($gestion_taupiers, 'display_taupe_products_highlighted')) {
                        echo $gestion_taupiers->display_taupe_products_highlighted();
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
                    $reviews = get_posts(array(
                        'post_type'      => 'taupier_review',
                        'posts_per_page' => -1,
                        'meta_query'     => array(
                            array(
                                'key'     => '_taupier_review_taupier_id',
                                'value'   => $post_id,
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

                    if (!empty($reviews)) {
                        echo '<div class="taupier-reviews" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">';

                        echo '<div class="average-rating">';
                        echo '<div class="rating-summary">';
                        echo '<span class="rating-value" itemprop="ratingValue">' . esc_html($average_rating_schema) . '</span><span class="rating-max">/5</span>';
                        echo '<meta itemprop="bestRating" content="5">';
                        echo '<meta itemprop="worstRating" content="1">';
                        echo '</div>';

                        echo taupier_display_stars($average_rating_schema);

                        echo '<span class="reviews-count">Basé sur <span itemprop="reviewCount">' . esc_html($count_reviews_schema) . '</span> avis</span>';
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
                        foreach ($reviews as $index => $review) {
                            $author_name = get_post_meta($review->ID, '_taupier_review_author_name', true);
                            $rating = get_post_meta($review->ID, '_taupier_review_rating', true);
                            $date = get_the_date('d/m/Y', $review->ID);
                            $iso_date = get_the_date('c', $review->ID);

                            echo '<div class="review-item" data-date="' . esc_attr(strtotime($iso_date)) . '" data-rating="' . esc_attr($rating) . '" itemprop="review" itemscope itemtype="https://schema.org/Review">';
                            echo '<div class="review-header">';
                            echo '<span class="review-author" itemprop="author" itemscope itemtype="https://schema.org/Person"><span itemprop="name">' . esc_html($author_name) . '</span></span>';
                            echo '<span class="review-date"><meta itemprop="datePublished" content="' . esc_attr($iso_date) . '">' . esc_html($date) . '</span>';
                            echo '</div>';

                            echo taupier_display_stars($rating, true);

                            echo '<div class="review-content" itemprop="reviewBody">' . wpautop(esc_html($review->post_content)) . '</div>';
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
                        $faq = get_post_meta($post_id, '_taupier_faq', true);
                        if (is_array($faq) && !empty($faq)) {
                            $has_faq = false;
                            foreach ($faq as $qa_pair) {
                                if (!empty($qa_pair['question']) && !empty($qa_pair['reponse'])) {
                                    $has_faq = true;
                                    break;
                                }
                            }

                            if ($has_faq) {
                                echo '<div class="taupier-faq" itemscope itemtype="https://schema.org/FAQPage">';

                                echo '<div class="faq-search-container">';
                                echo '<input type="text" id="faq-search" placeholder="Rechercher une question..." aria-label="Rechercher dans la FAQ">';
                                echo '</div>';

                                echo '<div class="faq-items">';
                                foreach ($faq as $index => $qa_pair) {
                                    if (!empty($qa_pair['question']) && !empty($qa_pair['reponse'])) {
                                        echo '<div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">';
                                        echo '<div class="faq-question" itemprop="name">' . esc_html($qa_pair['question']) . '</div>';
                                        echo '<div class="faq-reponse" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">';
                                        echo '<div itemprop="text">' . nl2br(esc_html($qa_pair['reponse'])) . '</div>';
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
                                <input type="hidden" name="taupier_id" value="<?php echo esc_attr($post_id); ?>">

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
                    $current_taupier_id = get_the_ID();
                    $categories = get_the_terms($current_taupier_id, 'taupier_category');

                    if ($categories && !is_wp_error($categories)) {
                        $category_ids = array_map(function($term) {
                            return $term->term_id;
                        }, $categories);

                        $args = array(
                            'post_type'      => 'taupier',
                            'posts_per_page' => 8,
                            'post__not_in'   => array($current_taupier_id),
                            'tax_query'      => array(
                                array(
                                    'taxonomy' => 'taupier_category',
                                    'field'    => 'term_id',
                                    'terms'    => $category_ids,
                                    'operator' => 'IN',
                                ),
                            ),
                            'orderby'        => 'rand',
                            'no_found_rows'  => true,
                            'update_post_meta_cache' => false,
                            'update_post_term_cache' => false,
                        );

                        $related_taupiers = new WP_Query($args);

                        if ($related_taupiers->have_posts()) {
                            echo '<div class="related-taupiers-wrapper">';

                            while ($related_taupiers->have_posts()) {
                                $related_taupiers->the_post();
                                $related_taupier_id = get_the_ID();
                                $taupier_title_related = get_the_title();
                                $taupier_link_related = get_permalink();
                                $taupier_zone_related = get_post_meta($related_taupier_id, '_taupier_zone', true);

                                $image_url_related = has_post_thumbnail() ? get_the_post_thumbnail_url($related_taupier_id, 'medium') : '';

                                $related_reviews_local = get_posts(array(
                                    'post_type'      => 'taupier_review',
                                    'posts_per_page' => -1,
                                    'meta_query'     => array(
                                        array(
                                            'key'     => '_taupier_review_taupier_id',
                                            'value'   => $related_taupier_id,
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

                                $avg_related_rating_local = 0;
                                $count_related_reviews_local = count($related_reviews_local);
                                if ($count_related_reviews_local > 0) {
                                    $total_related_rating_local = 0;
                                    foreach ($related_reviews_local as $r_review_local) {
                                        $total_related_rating_local += intval(get_post_meta($r_review_local->ID, '_taupier_review_rating', true));
                                    }
                                    $avg_related_rating_local = round($total_related_rating_local / $count_related_reviews_local, 1);
                                }

                                echo '<div class="related-taupier-card">';
                                echo '<a href="' . esc_url($taupier_link_related) . '" class="taupier-card-link">';

                                if (!empty($image_url_related)) {
                                    echo '<div class="related-taupier-image">';
                                    echo '<img src="' . esc_url($image_url_related) . '" alt="' . esc_attr($taupier_title_related) . '" loading="lazy">';
                                    echo '</div>';
                                }

                                echo '<div class="related-taupier-info">';
                                echo '<h3 class="related-taupier-title">' . esc_html($taupier_title_related) . '</h3>';

                                if (!empty($taupier_zone_related)) {
                                    echo '<p class="related-taupier-zone"><i class="fa fa-map-marker"></i> ' . esc_html($taupier_zone_related) . '</p>';
                                }

                                if ($count_related_reviews_local > 0) {
                                    echo '<div class="related-taupier-rating">';
                                    echo '<span class="rating-value">' . esc_html($avg_related_rating_local) . '</span>';
                                    echo taupier_display_stars($avg_related_rating_local);
                                    echo '<span class="reviews-count">(' . esc_html($count_related_reviews_local) . ')</span>';
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

<?php if (!empty($telephone)) : ?>
    <div class="quick-contact-btn">
        <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', $telephone)); ?>" class="call-btn" aria-label="Appeler le taupier">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0  0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
            </svg>
            <span>Appeler</span>
        </a>
    </div>
<?php endif; ?>

<?php
get_footer();
?>