document.addEventListener('DOMContentLoaded', () => {
  // 1. Hero Banner Carousel & Slideshow
  const track = document.querySelector('.slides-track');
  const slides = document.querySelectorAll('.slide-item');
  const nextBtn = document.querySelector('.slider-btn.next');
  const prevBtn = document.querySelector('.slider-btn.prev');
  const dots = document.querySelectorAll('.slider-dots .dot');

  if (track && slides.length > 0) {
    let currentIndex = 0;
    const totalSlides = slides.length;
    let autoSlideInterval = null;

    function goToSlide(index) {
      if (index < 0) {
        currentIndex = totalSlides - 1;
      } else if (index >= totalSlides) {
        currentIndex = 0;
      } else {
        currentIndex = index;
      }

      track.style.transform = `translateX(-${currentIndex * 100}%)`;

      dots.forEach((dot, idx) => {
        if (idx === currentIndex) {
          dot.classList.add('active');
        } else {
          dot.classList.remove('active');
        }
      });
    }

    function startAutoSlide() {
      stopAutoSlide();
      autoSlideInterval = setInterval(() => {
        goToSlide(currentIndex + 1);
      }, 4000);
    }

    function stopAutoSlide() {
      if (autoSlideInterval) {
        clearInterval(autoSlideInterval);
        autoSlideInterval = null;
      }
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', () => {
        goToSlide(currentIndex + 1);
        startAutoSlide();
      });
    }

    if (prevBtn) {
      prevBtn.addEventListener('click', () => {
        goToSlide(currentIndex - 1);
        startAutoSlide();
      });
    }

    dots.forEach((dot, idx) => {
      dot.addEventListener('click', () => {
        goToSlide(idx);
        startAutoSlide();
      });
    });

    // Touch Swipe Support for Mobile Screens
    let startX = 0;
    let endX = 0;
    const sliderWrap = document.querySelector('.hero-slider-wrap');

    if (sliderWrap) {
      sliderWrap.addEventListener('touchstart', (e) => {
        startX = e.touches[0].clientX;
        stopAutoSlide();
      }, { passive: true });

      sliderWrap.addEventListener('touchend', (e) => {
        endX = e.changedTouches[0].clientX;
        if (startX - endX > 40) {
          goToSlide(currentIndex + 1);
        } else if (endX - startX > 40) {
          goToSlide(currentIndex - 1);
        }
        startAutoSlide();
      }, { passive: true });

      sliderWrap.addEventListener('mouseenter', stopAutoSlide);
      sliderWrap.addEventListener('mouseleave', startAutoSlide);
    }

    // Initialize first slide and start autoplay
    goToSlide(0);
    startAutoSlide();
  }

  // 2. Portfolio Filter Tabs & Lightbox Modal
  const filterBtns = document.querySelectorAll('.filter-btn');
  const projectCards = document.querySelectorAll('.project-card');

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const filter = btn.getAttribute('data-filter');

      projectCards.forEach(card => {
        if (filter === 'all' || card.getAttribute('data-category') === filter) {
          card.style.display = 'flex';
          card.style.animation = 'fadeIn 0.4s ease forwards';
        } else {
          card.style.display = 'none';
        }
      });
    });
  });

  // Lightbox Modal for Project Zoom
  const lightboxModal = document.getElementById('portfolioLightbox');
  const lightboxImg = document.getElementById('lightboxImg');
  const lightboxCaption = document.getElementById('lightboxCaption');
  const lightboxClose = document.getElementById('lightboxClose');
  const lightboxOverlay = document.getElementById('lightboxOverlay');

  if (lightboxModal && lightboxImg && lightboxClose) {
    projectCards.forEach(card => {
      card.addEventListener('click', () => {
        const img = card.querySelector('.project-thumb img');
        const title = card.querySelector('.project-title');
        if (img) {
          lightboxImg.src = img.src;
          lightboxImg.alt = img.alt || 'Portfolio Item';
          if (lightboxCaption && title) {
            lightboxCaption.textContent = title.textContent;
          }
          lightboxModal.classList.add('active');
          document.body.style.overflow = 'hidden';
        }
      });
    });

    const closeLightbox = () => {
      lightboxModal.classList.remove('active');
      document.body.style.overflow = '';
      lightboxImg.src = '';
    };

    lightboxClose.addEventListener('click', closeLightbox);
    if (lightboxOverlay) lightboxOverlay.addEventListener('click', closeLightbox);

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && lightboxModal.classList.contains('active')) {
        closeLightbox();
      }
    });
  }

  // 3. Interactive Calendar Date & Time Slot Selection
  const calDates = document.querySelectorAll('.cal-date:not(.disabled)');
  let selectedDate = 'Nov 11, 2026';
  let selectedSlot = '10:00 AM';

  calDates.forEach(date => {
    date.addEventListener('click', () => {
      calDates.forEach(d => d.classList.remove('active'));
      date.classList.add('active');
      selectedDate = date.getAttribute('data-date') || `Nov ${date.textContent.trim()}, 2026`;
      updateBookingSummary();
    });
  });

  const slotBtns = document.querySelectorAll('.slot-btn');
  slotBtns.forEach(slot => {
    slot.addEventListener('click', () => {
      slotBtns.forEach(s => s.classList.remove('active'));
      slot.classList.add('active');
      selectedSlot = slot.textContent.trim();
      updateBookingSummary();
    });
  });

  function updateBookingSummary() {
    const summaryField = document.getElementById('selected-datetime-display');
    if (summaryField) {
      summaryField.textContent = `${selectedDate} at ${selectedSlot}`;
    }
    const dateInput = document.getElementById('book-date-input');
    const timeInput = document.getElementById('book-time-input');
    if (dateInput) dateInput.value = selectedDate;
    if (timeInput) timeInput.value = selectedSlot;
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // 4. Booking Form Submission — Delivered Directly to glorifixmediaconcept@gmail.com
  const bookingForm = document.getElementById('booking-form');
  if (bookingForm) {
    bookingForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const submitBtn = document.getElementById('book-submit-btn') || bookingForm.querySelector('button[type="submit"]');
      const statusDiv = document.getElementById('booking-status');
      
      const name = (document.getElementById('book-name')?.value || '').trim();
      const email = (document.getElementById('book-email')?.value || '').trim();
      const phone = (document.getElementById('book-phone')?.value || '').trim();
      const service = (document.getElementById('book-service')?.value || '').trim();
      const notes = (document.getElementById('book-notes')?.value || '').trim();

      if (!name || !email || !phone) {
        if (statusDiv) {
          statusDiv.style.display = 'block';
          statusDiv.innerHTML = '<div style="color: #C2185B; font-weight: 600;">⚠️ Please provide your full name, email address, and phone number so we can confirm your appointment.</div>';
        }
        return;
      }

      const origText = submitBtn ? submitBtn.innerHTML : 'Confirm &amp; Book a Consultation';
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Submitting to Email... ⏳';
      }
      if (statusDiv) {
        statusDiv.style.display = 'block';
        statusDiv.innerHTML = '<div style="color: #6A3040; font-size: 0.9rem;">Transmitting appointment request to glorifixmediaconcept@gmail.com...</div>';
      }

      const payload = {
        _subject: `📅 New Appointment Booking: ${name} - ${service}`,
        _replyto: email,
        _template: 'table',
        _captcha: 'false',
        "Client Name": name,
        "Email Address": email,
        "Phone / WhatsApp": phone,
        "Service Requested": service,
        "Scheduled Date": selectedDate,
        "Scheduled Time": selectedSlot,
        "Project Goals / Notes": notes || "None provided",
        "Submitted At": new Date().toLocaleString()
      };

      fetch('https://formsubmit.co/ajax/glorifixmediaconcept@gmail.com', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      })
      .then(res => res.json())
      .then(data => {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = origText;
        }

        const whatsappMsg = `Hello Glorifix Media Concept! I just booked an appointment on your website:\n• Name: ${name}\n• Email: ${email}\n• Phone: ${phone}\n• Service: ${service}\n• Date: ${selectedDate} at ${selectedSlot}\n• Notes: ${notes || 'None'}`;
        const whatsappUrl = `https://wa.me/2349050278999?text=${encodeURIComponent(whatsappMsg)}`;

        if (statusDiv) {
          statusDiv.innerHTML = `
            <div style="background: #E8F5E9; color: #1B5E20; border: 1.5px solid #81C784; padding: 14px 16px; border-radius: 8px; text-align: left; line-height: 1.55;">
              <strong style="font-size: 1rem; color: #1B5E20; display: block; margin-bottom: 4px;">🎉 Appointment Sent to Email!</strong>
              Thank you, <strong>${escapeHtml(name)}</strong>! Your appointment request for <strong>${escapeHtml(selectedDate)} at ${escapeHtml(selectedSlot)}</strong> has been delivered directly to <strong>glorifixmediaconcept@gmail.com</strong>.<br>
              <span style="font-size: 0.88rem; color: #2E7D32;">We will review your project brief and reply to <strong>${escapeHtml(email)}</strong> or <strong>${escapeHtml(phone)}</strong> promptly.</span>
              <div style="margin-top: 10px; padding-top: 8px; border-top: 1px dashed #A5D6A7; font-size: 0.88rem;">
                Prefer instant confirmation? <a href="${whatsappUrl}" target="_blank" rel="noopener noreferrer" style="color: #00796B; font-weight: 700; text-decoration: underline;">Click here to also notify us on WhatsApp &rarr;</a>
              </div>
            </div>
          `;
        }
        bookingForm.reset();
        updateBookingSummary();
      })
      .catch(err => {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = origText;
        }

        const mailtoUrl = `mailto:glorifixmediaconcept@gmail.com?subject=${encodeURIComponent('Appointment Booking: ' + name)}&body=${encodeURIComponent('Client Name: ' + name + '\nEmail: ' + email + '\nPhone: ' + phone + '\nService: ' + service + '\nDate: ' + selectedDate + ' at ' + selectedSlot + '\nNotes: ' + notes)}`;
        const whatsappMsg = `Hello Glorifix Media Concept! I want to book an appointment:\n• Name: ${name}\n• Email: ${email}\n• Phone: ${phone}\n• Service: ${service}\n• Date: ${selectedDate} at ${selectedSlot}\n• Notes: ${notes || 'None'}`;
        const whatsappUrl = `https://wa.me/2349050278999?text=${encodeURIComponent(whatsappMsg)}`;

        if (statusDiv) {
          statusDiv.innerHTML = `
            <div style="background: #FFF3E0; color: #E65100; border: 1.5px solid #FFB74D; padding: 14px 16px; border-radius: 8px; text-align: left; line-height: 1.55;">
              <strong style="font-size: 1rem; color: #E65100; display: block; margin-bottom: 4px;">Direct Delivery Backup</strong>
              Your device network took longer to connect. Please click below to send your appointment details directly via your email client or WhatsApp:<br>
              <div style="margin-top: 10px; display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="${mailtoUrl}" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.85rem; text-decoration: none;">Send via Email App</a>
                <a href="${whatsappUrl}" target="_blank" rel="noopener noreferrer" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.85rem; text-decoration: none;">Confirm on WhatsApp</a>
              </div>
            </div>
          `;
        }
      });
    });
  }

  // 5. Contact Form Submission — Delivered Directly to glorifixmediaconcept@gmail.com
  const contactForm = document.getElementById('contact-form');
  if (contactForm) {
    contactForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const submitBtn = document.getElementById('contact-submit-btn') || contactForm.querySelector('button[type="submit"]');
      const statusDiv = document.getElementById('contact-status');

      const name = (document.getElementById('contact-name')?.value || '').trim();
      const email = (document.getElementById('contact-email')?.value || '').trim();
      const phone = (document.getElementById('contact-phone')?.value || '').trim();
      const message = (document.getElementById('contact-msg')?.value || '').trim();

      if (!name || !email || !message) {
        if (statusDiv) {
          statusDiv.style.display = 'block';
          statusDiv.innerHTML = '<div style="color: #C2185B; font-weight: 600;">⚠️ Please provide your name, email address, and message.</div>';
        }
        return;
      }

      const origText = submitBtn ? submitBtn.innerHTML : 'Send Direct Message to Email';
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Sending to Email... ⏳';
      }
      if (statusDiv) {
        statusDiv.style.display = 'block';
        statusDiv.innerHTML = '<div style="color: #6A3040; font-size: 0.9rem;">Transmitting inquiry to glorifixmediaconcept@gmail.com...</div>';
      }

      const payload = {
        _subject: `📩 New Website Inquiry from: ${name}`,
        _replyto: email,
        _template: 'table',
        _captcha: 'false',
        "Sender Name": name,
        "Email Address": email,
        "Phone / WhatsApp": phone || "Not provided",
        "Inquiry / Message": message,
        "Submitted At": new Date().toLocaleString()
      };

      fetch('https://formsubmit.co/ajax/glorifixmediaconcept@gmail.com', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      })
      .then(res => res.json())
      .then(data => {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = origText;
        }

        if (statusDiv) {
          statusDiv.innerHTML = `
            <div style="background: #E8F5E9; color: #1B5E20; border: 1.5px solid #81C784; padding: 14px 16px; border-radius: 8px; text-align: left; line-height: 1.55;">
              <strong style="font-size: 1rem; color: #1B5E20; display: block; margin-bottom: 4px;">✅ Message Delivered to Email!</strong>
              Thank you, <strong>${escapeHtml(name)}</strong>! Your note has been delivered directly to <strong>glorifixmediaconcept@gmail.com</strong>.<br>
              <span style="font-size: 0.88rem; color: #2E7D32;">We will review your inquiry and reply to <strong>${escapeHtml(email)}</strong> promptly.</span>
            </div>
          `;
        }
        contactForm.reset();
      })
      .catch(err => {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = origText;
        }

        const mailtoUrl = `mailto:glorifixmediaconcept@gmail.com?subject=${encodeURIComponent('Inquiry from: ' + name)}&body=${encodeURIComponent('Name: ' + name + '\nEmail: ' + email + '\nPhone: ' + (phone || 'N/A') + '\n\nMessage:\n' + message)}`;
        const whatsappMsg = `Hello Glorifix Media Concept! Inquiry from ${name} (${email}):\n${message}`;
        const whatsappUrl = `https://wa.me/2349050278999?text=${encodeURIComponent(whatsappMsg)}`;

        if (statusDiv) {
          statusDiv.innerHTML = `
            <div style="background: #FFF3E0; color: #E65100; border: 1.5px solid #FFB74D; padding: 14px 16px; border-radius: 8px; text-align: left; line-height: 1.55;">
              <strong style="font-size: 1rem; color: #E65100; display: block; margin-bottom: 4px;">Direct Delivery Backup</strong>
              If direct dispatch took longer than expected, please send your message directly via your email client or WhatsApp:<br>
              <div style="margin-top: 10px; display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="${mailtoUrl}" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.85rem; text-decoration: none;">Send via Email App</a>
                <a href="${whatsappUrl}" target="_blank" rel="noopener noreferrer" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.85rem; text-decoration: none;">Chat on WhatsApp</a>
              </div>
            </div>
          `;
        }
      });
    });
  }

  // 6. Mobile Hamburger Menu Toggle & Vertical Dropdown
  const menuToggle = document.getElementById('menu-toggle');
  const menuCollapse = document.getElementById('menu-collapse');

  if (menuToggle && menuCollapse) {
    menuToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = menuCollapse.classList.toggle('is-open');
      menuToggle.classList.toggle('is-open');
      menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    // Close when tapping any link inside the menu
    const navLinks = menuCollapse.querySelectorAll('a');
    navLinks.forEach(link => {
      link.addEventListener('click', () => {
        menuCollapse.classList.remove('is-open');
        menuToggle.classList.remove('is-open');
        menuToggle.setAttribute('aria-expanded', 'false');
      });
    });

    // Close when clicking outside the menu bar
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.menu-bar')) {
        menuCollapse.classList.remove('is-open');
        menuToggle.classList.remove('is-open');
        menuToggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // 7. Pricing Category Tabs Switcher
  const pricingTabs = document.querySelectorAll('.pricing-tab-btn');
  const pricingPanels = document.querySelectorAll('.pricing-category-panel');

  if (pricingTabs.length > 0 && pricingPanels.length > 0) {
    pricingTabs.forEach(tab => {
      tab.addEventListener('click', () => {
        pricingTabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');

        const targetId = tab.getAttribute('data-target');
        pricingPanels.forEach(panel => {
          if (panel.id === targetId) {
            panel.classList.add('active');
          } else {
            panel.classList.remove('active');
          }
        });
      });
    });

    // Support URL Hash Deep-linking (e.g. #cac, #panel-cac, #media)
    const rawHash = window.location.hash.replace('#', '').toLowerCase();
    if (rawHash) {
      let targetId = rawHash.startsWith('panel-') ? rawHash : 'panel-' + rawHash;
      const matchingTab = document.querySelector(`.pricing-tab-btn[data-target="${targetId}"]`) || document.querySelector(`.pricing-tab-btn[data-target="${rawHash}"]`);
      if (matchingTab) {
        matchingTab.click();
        setTimeout(() => {
          const p = document.getElementById(targetId);
          if (p) p.scrollIntoView({ behavior: 'smooth' });
        }, 150);
      }
    }
  }
});

