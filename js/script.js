// ============================================
// HERO CAROUSEL
// ============================================

const heroSlides = document.querySelectorAll('.hero-slide');
const carouselDots = document.querySelectorAll('.carousel-dot');
let currentHeroSlide = 0;
let heroAutoSlideInterval;

function showHeroSlide(index) {
  if (heroSlides.length === 0) return;
  
  // Remove active class from all slides and dots
  heroSlides.forEach(slide => slide.classList.remove('active'));
  carouselDots.forEach(dot => dot.classList.remove('active'));
  
  // Add active class to current slide and dot
  heroSlides[index].classList.add('active');
  carouselDots[index].classList.add('active');
  currentHeroSlide = index;
}

function nextHeroSlide() {
  if (heroSlides.length === 0) return;
  currentHeroSlide = (currentHeroSlide + 1) % heroSlides.length;
  showHeroSlide(currentHeroSlide);
}

function prevHeroSlide() {
  if (heroSlides.length === 0) return;
  currentHeroSlide = (currentHeroSlide - 1 + heroSlides.length) % heroSlides.length;
  showHeroSlide(currentHeroSlide);
}

// Add click handlers to carousel dots
carouselDots.forEach((dot, index) => {
  dot.addEventListener('click', () => {
    clearInterval(heroAutoSlideInterval);
    showHeroSlide(index);
    startHeroAutoSlide();
  });
});

function startHeroAutoSlide() {
  heroAutoSlideInterval = setInterval(nextHeroSlide, 5000);
}

// Initialize hero carousel
if (heroSlides.length > 0) {
  showHeroSlide(0);
  startHeroAutoSlide();
  
  // Stop auto-slide on hover
  const heroSection = document.querySelector('.hero');
  if (heroSection) {
    heroSection.addEventListener('mouseenter', () => {
      clearInterval(heroAutoSlideInterval);
    });
    
    heroSection.addEventListener('mouseleave', () => {
      startHeroAutoSlide();
    });
  }
}

// ============================================
// IMAGE SLIDER (Activities Section)
// ============================================

const slider = document.getElementById('imageSlider');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');
let currentSlide = 0;
const totalSlides = 4; // Update based on actual number of slides

function updateSlider() {
  if (slider) {
    slider.style.transform = `translateX(-${currentSlide * 100}%)`;
  }
}

function nextSlide() {
  currentSlide = (currentSlide + 1) % totalSlides;
  updateSlider();
}

function prevSlide() {
  currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
  updateSlider();
}

if (nextBtn) {
  nextBtn.addEventListener('click', () => {
    clearInterval(autoSlide);
    nextSlide();
    autoSlide = setInterval(nextSlide, 5000);
  });
}

if (prevBtn) {
  prevBtn.addEventListener('click', () => {
    clearInterval(autoSlide);
    prevSlide();
    autoSlide = setInterval(nextSlide, 5000);
  });
}

let autoSlide = setInterval(nextSlide, 5000);

if (slider) {
  slider.addEventListener('mouseenter', () => {
    clearInterval(autoSlide);
  });

  slider.addEventListener('mouseleave', () => {
    autoSlide = setInterval(nextSlide, 5000);
  });
}

// ============================================
// CONTACT FORM SUBMISSION
// ============================================

const contactForm = document.getElementById('contactForm');
const formMessage = document.getElementById('formMessage');

if (contactForm) {
  contactForm.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(contactForm);
    const data = {
      name: formData.get('name'),
      email: formData.get('email'),
      phone: formData.get('phone'),
      category: formData.get('category'),
      message: formData.get('message'),
      timestamp: new Date().toISOString()
    };

    fetch('save_message.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
      if (result.success) {
        formMessage.textContent = '✓ Thank you! Your message has been sent successfully.';
        formMessage.className = 'form-message success';
        contactForm.reset();
        setTimeout(() => {
          formMessage.textContent = '';
        }, 5000);
      } else {
        throw new Error(result.message || 'An error occurred');
      }
    })
    .catch(error => {
      formMessage.textContent = '✗ Error: ' + error.message;
      formMessage.className = 'form-message error';
    });
  });
}

// ============================================
// MODAL CONTROLS
// ============================================

function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden'; // Prevent background scroll
  }
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.display = 'none';
    document.body.style.overflow = 'auto'; // Restore scroll
  }
}

// Close modal when clicking outside of content
window.onclick = function(event) {
  if (event.target.classList.contains('modal')) {
    event.target.style.display = 'none';
    document.body.style.overflow = 'auto';
  }
};

// ============================================
// SMOOTH SCROLLING FOR NAVIGATION
// ============================================

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
    const href = this.getAttribute('href');
    if (href === '#') return;
    
    e.preventDefault();
    const target = document.querySelector(href);
    if (target) {
      target.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    }
  });
});
