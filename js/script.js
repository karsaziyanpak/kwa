// Mobile Menu Toggle Functionality
const mobileMenuToggle = () => {
    const menuButton = document.getElementById('menu-button');
    const menu = document.getElementById('mobile-menu');

    menuButton.addEventListener('click', () => {
        menu.classList.toggle('open');
    });
};

// Image Slider for Activities Section
const imageSlider = () => {
    const slides = document.querySelectorAll('.slide');
    let currentSlide = 0;

    const showSlide = (index) => {
        slides.forEach((slide, i) => {
            slide.style.display = (i === index) ? 'block' : 'none';
        });
    };

    const nextSlide = () => {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    };

    const prevSlide = () => {
        currentSlide = (currentSlide - 1 + slides.length) % slides.length;
        showSlide(currentSlide);
    };

    // Auto-play functionality
    setInterval(nextSlide, 3000);

    // Manual Controls
    document.getElementById('next-button').addEventListener('click', nextSlide);
    document.getElementById('prev-button').addEventListener('click', prevSlide);

    showSlide(currentSlide);
};

// Contact Form Submission
const contactFormSubmission = () => {
    const form = document.getElementById('contact-form');
    const successMessage = document.getElementById('success-message');
    const errorMessage = document.getElementById('error-message');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const formData = new FormData(form);

        // Basic Validation
        if (!formData.get('name') || !formData.get('email')) {
            errorMessage.textContent = 'Name and email are required!';
            return;
        }

        try {
            const response = await fetch('save_message.php', {
                method: 'POST',
                body: formData,
            });
            if (!response.ok) throw new Error('Server responded with a problem.');

            successMessage.textContent = 'Message sent successfully!';
            form.reset();
        } catch (error) {
            errorMessage.textContent = 'Error sending message: ' + error.message;
        }
    });
};

window.onload = () => {
    mobileMenuToggle();
    imageSlider();
    contactFormSubmission();
};
