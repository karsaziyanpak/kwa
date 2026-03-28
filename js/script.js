// ============================================
// IMAGE SLIDER
// ============================================

const slider = document.getElementById('imageSlider');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');
let currentSlide = 0;
const totalSlides = 10;

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
// SMOOTH SCROLLING FOR NAVIGATION
// ============================================

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
    e.preventDefault();
    const target = document.querySelector(this.getAttribute('href'));
    if (target) {
      target.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    }
  });
});
