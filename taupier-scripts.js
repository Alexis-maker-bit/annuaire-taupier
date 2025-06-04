document.addEventListener('DOMContentLoaded', function() {
    // Gestion de la FAQ (Accordion)
    const faqQuestions = document.querySelectorAll('.faq-question');
    const faqNoResults = document.getElementById('faq-no-results');

    // Cacher toutes les réponses de la FAQ au chargement
    faqQuestions.forEach(function(question) {
        const reponse = question.nextElementSibling;
        reponse.style.maxHeight = '0';
        reponse.classList.remove('open'); // S'assurer qu'elles ne sont pas ouvertes par défaut
    });

    faqQuestions.forEach(function(question) {
        question.addEventListener('click', function() {
            const reponse = this.nextElementSibling;
            const wasActive = this.classList.contains('active');

            // Fermer toutes les autres questions
            faqQuestions.forEach(function(q) {
                if (q !== question) {
                    q.classList.remove('active');
                    const resp = q.nextElementSibling;
                    resp.classList.remove('open');
                    resp.style.maxHeight = '0';
                }
            });

            // Toggle la question active actuelle
            if (!wasActive) {
                this.classList.add('active');
                reponse.classList.add('open');
                reponse.style.maxHeight = reponse.scrollHeight + 'px'; // Définir la hauteur pour l'animation
            } else {
                this.classList.remove('active');
                reponse.classList.remove('open');
                reponse.style.maxHeight = '0';
            }
        });
    });

    // Recherche dans la FAQ
    const searchInput = document.getElementById('faq-search');
    const faqItems = document.querySelectorAll('.faq-item');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            let hasResults = false;

            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question');
                const answer = item.querySelector('.faq-reponse div');
                const questionText = question ? question.textContent.toLowerCase() : '';
                const answerText = answer ? answer.textContent.toLowerCase() : '';

                if (questionText.includes(searchTerm) || answerText.includes(searchTerm)) {
                    item.style.display = 'block';
                    hasResults = true;
                } else {
                    item.style.display = 'none';
                }

                // Fermer les réponses qui ne correspondent pas à la recherche
                if (question && question.classList.contains('active') && (!questionText.includes(searchTerm) && !answerText.includes(searchTerm))) {
                    question.classList.remove('active');
                    const reponse = question.nextElementSibling;
                    reponse.classList.remove('open');
                    reponse.style.maxHeight = '0';
                }
            });

            if (faqNoResults) {
                faqNoResults.style.display = hasResults ? 'none' : 'block';
            }
        });
    }

    // Compteur de caractères pour le textarea d'avis
    const reviewTextarea = document.getElementById('review_text');
    const charCounter = document.querySelector('.textarea-counter');
    const MAX_CHARS = 500;

    if (reviewTextarea && charCounter) {
        reviewTextarea.addEventListener('input', function() {
            const count = this.value.length;
            charCounter.textContent = `${count}/${MAX_CHARS} caractères`;

            if (count > MAX_CHARS * 0.9) { // Plus de 90%
                charCounter.style.color = '#e67e22';
            } else if (count > MAX_CHARS) { // Au-delà de la limite
                charCounter.style.color = '#e74c3c';
            } else {
                charCounter.style.color = '';
            }
        });
        // Initialiser le compteur au chargement
        reviewTextarea.dispatchEvent(new Event('input'));
    }

    // Filtrage des avis
    const filterButtons = document.querySelectorAll('.reviews-filters .filter-btn');
    const reviewsListContainer = document.querySelector('.reviews-list');
    const reviewItems = reviewsListContainer ? Array.from(reviewsListContainer.querySelectorAll('.review-item')) : [];

    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Mise à jour des boutons actifs
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            const filter = this.dataset.filter;

            // Filtrer et/ou trier les avis
            const filteredItems = reviewItems.filter(item => {
                const rating = parseInt(item.dataset.rating);
                switch (filter) {
                    case 'positive':
                        return rating >= 4;
                    case 'all':
                    default:
                        return true;
                }
            });

            // Trier spécifiquement pour 'recent'
            if (filter === 'recent') {
                filteredItems.sort((a, b) => {
                    return parseInt(b.dataset.date) - parseInt(a.dataset.date); // Tri descendant par date
                });
            } else if (filter === 'positive' || filter === 'all') {
                // Pour "positif" et "tous", trier par note descendante, puis par date descendante
                filteredItems.sort((a, b) => {
                    const ratingA = parseInt(a.dataset.rating);
                    const ratingB = parseInt(b.dataset.rating);
                    const dateA = parseInt(a.dataset.date);
                    const dateB = parseInt(b.dataset.date);

                    if (ratingB !== ratingA) {
                        return ratingB - ratingA; // Trier par note (descendant)
                    }
                    return dateB - dateA; // Puis par date (descendant)
                });
            }


            // Réorganiser les éléments dans le DOM
            reviewsListContainer.innerHTML = ''; // Vider le conteneur
            if (filteredItems.length > 0) {
                filteredItems.forEach(item => {
                    reviewsListContainer.appendChild(item);
                });
            } else {
                reviewsListContainer.innerHTML = '<p style="text-align: center; color: #666; padding: 1rem;">Aucun avis ne correspond à ce filtre.</p>';
            }
        });
    });

    // Gestion de la galerie de photos (slider)
    const gallerySliderWrapper = document.querySelector('.gallery-slider-wrapper');
    const gallerySlides = document.querySelectorAll('.gallery-slide');
    const galleryDots = document.querySelectorAll('.gallery-dot');
    const galleryPrevBtn = document.querySelector('.gallery-prev-btn');
    const galleryNextBtn = document.querySelector('.gallery-next-btn');

    if (gallerySlides.length > 0 && gallerySliderWrapper) {
        let currentSlide = 0;

        const showSlide = (index) => {
            if (index < 0) {
                index = gallerySlides.length - 1;
            } else if (index >= gallerySlides.length) {
                index = 0;
            }

            gallerySliderWrapper.style.transform = `translateX(-${index * 100}%)`;

            galleryDots.forEach((dot, idx) => {
                dot.classList.toggle('active', idx === index);
            });

            currentSlide = index;
        };

        galleryPrevBtn?.addEventListener('click', () => showSlide(currentSlide - 1));
        galleryNextBtn?.addEventListener('click', () => showSlide(currentSlide + 1));

        galleryDots.forEach((dot, index) => {
            dot.addEventListener('click', () => showSlide(index));
        });

        // Initialiser le slider
        showSlide(0);
    }

    // Gestion du formulaire d'avis (AJAX)
    const reviewForm = document.getElementById('submit-taupier-review');
    const formMessage = reviewForm ? reviewForm.querySelector('.form-message') : null;
    const feedbackSuccess = document.querySelector('.review-submission-feedback');

    if (reviewForm && formMessage && typeof taupier_ajax !== 'undefined') {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Validation côté client
            let isValid = true;
            const formData = new FormData(this);

            // Vérifier les champs requis
            this.querySelectorAll('[required]').forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#e74c3c';
                    isValid = false;
                } else {
                    field.style.borderColor = '';
                }
            });

            // Vérifier la note (radio buttons)
            const ratingInput = this.querySelector('input[name="rating"]:checked');
            if (!ratingInput) {
                // Trouver le conteneur des étoiles pour y appliquer un style d'erreur
                const ratingFieldset = this.querySelector('.rating-field');
                if (ratingFieldset) {
                    ratingFieldset.style.border = '1px solid #e74c3c'; // Appliquer une bordure rouge
                    ratingFieldset.style.padding = '5px';
                    ratingFieldset.style.borderRadius = '5px';
                }
                isValid = false;
            } else {
                const ratingFieldset = this.querySelector('.rating-field');
                if (ratingFieldset) {
                    ratingFieldset.style.border = ''; // Réinitialiser la bordure
                    ratingFieldset.style.padding = '';
                    ratingFieldset.style.borderRadius = '';
                }
            }

            // Vérifier la longueur de l'avis
            if (reviewTextarea && reviewTextarea.value.length > MAX_CHARS) {
                reviewTextarea.style.borderColor = '#e74c3c';
                isValid = false;
                formMessage.innerHTML = '<div style="color: #e74c3c; margin-top: 1rem;">Votre avis dépasse la limite de caractères.</div>';
                return; // Arrêter si la longueur est dépassée
            }


            if (!isValid) {
                formMessage.innerHTML = '<div style="color: #e74c3c; margin-top: 1rem;">Veuillez remplir tous les champs obligatoires correctement.</div>';
                return;
            }

            // Préparer les données pour AJAX
            formData.append('action', 'submit_taupier_review');
            formData.append('nonce', taupier_ajax.nonce);

            // Désactiver le bouton et montrer un état de chargement
            const submitButton = this.querySelector('.submit-review-button');
            submitButton.disabled = true;
            submitButton.textContent = 'Envoi en cours...';
            formMessage.innerHTML = ''; // Nettoyer les messages précédents

            fetch(taupier_ajax.ajax_url, {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    reviewForm.style.display = 'none'; // Cacher le formulaire
                    if (feedbackSuccess) {
                        feedbackSuccess.style.display = 'block'; // Afficher le message de succès
                    }
                    reviewForm.reset(); // Réinitialiser le formulaire
                    // Optionnel: Recharger les avis ou ajouter le nouvel avis dynamiquement
                } else {
                    formMessage.innerHTML = `<div style="color: #e74c3c; margin-top: 1rem;">${data.data.message || 'Une erreur est survenue.'}</div>`;
                    if (data.data.errors) {
                        data.data.errors.forEach(error => {
                            formMessage.innerHTML += `<p style="color: #e74c3c;">- ${error}</p>`;
                        });
                    }
                }
            })
            .catch(error => {
                console.error('Erreur AJAX:', error);
                formMessage.innerHTML = '<div style="color: #e74c3c; margin-top: 1rem;">Une erreur de connexion est survenue. Veuillez réessayer.</div>';
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.textContent = 'Envoyer mon avis';
            });
        });
    }

    // Slider pour les taupiers similaires (scroll horizontal avec boutons)
    const relatedTaupiersWrapper = document.querySelector('.related-taupiers-wrapper');
    const relatedPrevBtn = document.querySelector('.related-prev-btn');
    const relatedNextBtn = document.querySelector('.related-next-btn');

    if (relatedTaupiersWrapper && relatedPrevBtn && relatedNextBtn) {
        const scrollAmount = relatedTaupiersWrapper.offsetWidth * 0.8; // Défile 80% de la largeur du wrapper

        relatedPrevBtn.addEventListener('click', () => {
            relatedTaupiersWrapper.scrollBy({
                left: -scrollAmount,
                behavior: 'smooth'
            });
        });

        relatedNextBtn.addEventListener('click', () => {
            relatedTaupiersWrapper.scrollBy({
                left: scrollAmount,
                behavior: 'smooth'
            });
        });

        const checkButtonVisibility = () => {
            // Petite marge pour le flottement des nombres à virgule
            const tolerance = 5;
            const isAtStart = relatedTaupiersWrapper.scrollLeft <= tolerance;
            const isAtEnd = relatedTaupiersWrapper.scrollLeft + relatedTaupiersWrapper.clientWidth >= relatedTaupiersWrapper.scrollWidth - tolerance;

            relatedPrevBtn.style.opacity = isAtStart ? '0.5' : '1';
            relatedNextBtn.style.opacity = isAtEnd ? '0.5' : '1';

            relatedPrevBtn.style.cursor = isAtStart ? 'not-allowed' : 'pointer';
            relatedNextBtn.style.cursor = isAtEnd ? 'not-allowed' : 'pointer';
        };

        relatedTaupiersWrapper.addEventListener('scroll', checkButtonVisibility);
        window.addEventListener('resize', checkButtonVisibility); // Réévalue à chaque redimensionnement

        // Exécute au chargement pour la première fois
        checkButtonVisibility();
    }

    // Slider pour les produits (comportement simple avec scroll-snap-type)
    const productSlider = document.querySelector('.product-slider');
    const productSliderContainer = document.querySelector('.produits-affiches-slider'); // Le conteneur qui contient les flèches
    
    if (productSlider && productSliderContainer) {
        // Crée les flèches de navigation
        const controlsDiv = document.createElement('div');
        controlsDiv.classList.add('product-slider-controls');
        
        const prevBtn = document.createElement('button');
        prevBtn.classList.add('product-prev-btn');
        prevBtn.innerHTML = '&laquo;'; // Symbole flèche gauche
        prevBtn.setAttribute('aria-label', 'Produit précédent');

        const nextBtn = document.createElement('button');
        nextBtn.classList.add('product-next-btn');
        nextBtn.innerHTML = '&raquo;'; // Symbole flèche droite
        nextBtn.setAttribute('aria-label', 'Produit suivant');

        controlsDiv.appendChild(prevBtn);
        controlsDiv.appendChild(nextBtn);
        productSliderContainer.appendChild(controlsDiv);

        // Récupérer les items du slider
        const productItems = Array.from(productSlider.querySelectorAll('.product-item'));

        // Fonctions de défilement
        const scrollByItem = (direction) => {
            if (productItems.length === 0) return;

            const currentScrollLeft = productSlider.scrollLeft;
            let targetScrollLeft = currentScrollLeft;
            let closestItemIndex = 0;

            // Trouver l'item le plus proche du bord gauche
            for (let i = 0; i < productItems.length; i++) {
                if (productItems[i].offsetLeft <= currentScrollLeft + 5) { // +5 pour tolérance
                    closestItemIndex = i;
                } else {
                    break;
                }
            }

            if (direction === 'next') {
                if (closestItemIndex < productItems.length - 1) {
                    targetScrollLeft = productItems[closestItemIndex + 1].offsetLeft;
                } else {
                    // Si on est au dernier, revenir au début (pour un effet de boucle non-clones)
                    targetScrollLeft = 0;
                }
            } else { // 'prev'
                if (closestItemIndex > 0) {
                    targetScrollLeft = productItems[closestItemIndex - 1].offsetLeft;
                } else {
                    // Si on est au premier, aller au dernier
                    targetScrollLeft = productSlider.scrollWidth - productSlider.clientWidth;
                }
            }
            
            productSlider.scrollTo({
                left: targetScrollLeft,
                behavior: 'smooth'
            });
        };

        // Ajout des écouteurs d'événements aux flèches
        prevBtn.addEventListener('click', () => scrollByItem('prev'));
        nextBtn.addEventListener('click', () => scrollByItem('next'));

        // Mettre à jour la visibilité des flèches au chargement et au défilement
        const updateArrowVisibility = () => {
            const scrollLeft = productSlider.scrollLeft;
            const scrollWidth = productSlider.scrollWidth;
            const clientWidth = productSlider.clientWidth;
            const tolerance = 5;

            // Masque la flèche "précédent" si au début
            if (scrollLeft <= tolerance) {
                prevBtn.style.display = 'none';
            } else {
                prevBtn.style.display = 'block';
            }

            // Masque la flèche "suivant" si à la fin
            if (scrollLeft + clientWidth >= scrollWidth - tolerance) {
                nextBtn.style.display = 'none';
            } else {
                nextBtn.style.display = 'block';
            }
        };

        // Appeler la fonction au chargement et lors du défilement
        window.addEventListener('load', updateArrowVisibility);
        productSlider.addEventListener('scroll', updateArrowVisibility);
        window.addEventListener('resize', updateArrowVisibility); // Pour ajuster en cas de redimensionnement

        // Initialiser la visibilité des flèches au démarrage
        updateArrowVisibility();
    }
});