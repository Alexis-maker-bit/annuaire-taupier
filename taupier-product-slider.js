document.addEventListener('DOMContentLoaded', function() {
    const sliderContainer = document.querySelector('.product-slider-container');

    if (sliderContainer) {
        const sliderWrapper = sliderContainer.querySelector('.product-slider-wrapper');
        const productItems = sliderContainer.querySelectorAll('.product-item');
        const prevBtn = document.createElement('button');
        const nextBtn = document.createElement('button');

        prevBtn.innerHTML = '&lsaquo;'; // Caractère '<'
        prevBtn.classList.add('product-slider-btn', 'prev-btn');
        prevBtn.setAttribute('aria-label', 'Produits précédents');
        nextBtn.innerHTML = '&rsaquo;'; // Caractère '>'
        nextBtn.classList.add('product-slider-btn', 'next-btn');
        nextBtn.setAttribute('aria-label', 'Produits suivants');

        const controlsDiv = document.createElement('div');
        controlsDiv.classList.add('product-slider-controls');
        controlsDiv.appendChild(prevBtn);
        controlsDiv.appendChild(nextBtn);
        sliderContainer.appendChild(controlsDiv); // Ajoute les boutons sous le slider

        let currentIndex = 0;
        const itemsPerPage = Math.floor(sliderContainer.offsetWidth / productItems[0].offsetWidth); // Calculer combien d'éléments peuvent s'afficher

        const updateSlider = () => {
            const itemWidth = productItems[0].offsetWidth + 10; // Largeur de l'élément + marge
            sliderWrapper.style.transform = `translateX(-${currentIndex * itemWidth}px)`;

            prevBtn.disabled = currentIndex === 0;
            nextBtn.disabled = currentIndex >= (productItems.length - itemsPerPage);
        };

        prevBtn.addEventListener('click', () => {
            currentIndex = Math.max(0, currentIndex - itemsPerPage);
            updateSlider();
        });

        nextBtn.addEventListener('click', () => {
            currentIndex = Math.min(productItems.length - itemsPerPage, currentIndex + itemsPerPage);
            updateSlider();
        });

        // Mise à jour du slider lors du redimensionnement de la fenêtre
        window.addEventListener('resize', () => {
            itemsPerPage = Math.floor(sliderContainer.offsetWidth / productItems[0].offsetWidth);
            updateSlider();
        });

        updateSlider(); // Initialisation du slider
    }
});