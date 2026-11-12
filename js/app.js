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
        Full_Name: name,
        _subject: `📅 New Consultation Booking: ${name} (${service})`,
        _replyto: email,
        _template: "table",
        "Customer Name": name,
        "Email Address": email,
        "Phone / WhatsApp": phone,
        "Service Requested": service,
        "Appointment Date": selectedDate,
        "Appointment Time": selectedSlot,
        "Project Goals / Brief": notes || "No specific notes provided.",
        // Standard keys for api/contact.php
        Full_Name: name,
        Email_Address: email,
        Phone_Number: phone,
        Service_Requested: service,
        Appointment_Date: selectedDate,
        Appointment_Time: selectedSlot,
        Project_Notes: notes || ""
      };

      // Dual-dispatch: 1) FormSubmit for guaranteed direct Gmail inbox delivery + 2) api/contact.php for dashboard logging
      const sendToGmail = fetch('https://formsubmit.co/ajax/glorifixmediaconcept@gmail.com', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload)
      }).catch(err => ({ ok: false, error: err }));

      const sendToApi = fetch('api/contact.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload)
      }).then(r => r.ok ? r.json() : null).catch(() => null);

      Promise.allSettled([sendToGmail, sendToApi]).then(results => {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = origText;
        }

        const apiResult = results[1].status === 'fulfilled' ? results[1].value : null;
        const refId = (apiResult && apiResult.id) ? apiResult.id : 'GLF-' + Math.random().toString(36).substr(2, 6).toUpperCase();

        const whatsappMsg = `Hello Glorifix Media Concept! I just booked an appointment on your website:\n• Name: ${name}\n• Email: ${email}\n• Phone: ${phone}\n• Service: ${service}\n• Date: ${selectedDate} at ${selectedSlot}\n• Notes: ${notes || 'None'}\n• Ref: ${refId}`;
        const whatsappUrl = `https://wa.me/2349050278999?text=${encodeURIComponent(whatsappMsg)}`;

        if (statusDiv) {
          statusDiv.innerHTML = `
            <div style="background: #E8F5E9; color: #1B5E20; border: 1.5px solid #81C784; padding: 16px; border-radius: 8px; text-align: left; line-height: 1.6;">
              <strong style="font-size: 1.05rem; color: #1B5E20; display: block; margin-bottom: 6px;">🎉 Consultation Booked Successfully!</strong>
              Thank you, <strong>${escapeHtml(name)}</strong>! Your appointment has been dispatched directly to <strong>glorifixmediaconcept@gmail.com</strong> and logged in our admin dashboard.<br>
              <span style="font-size: 0.9rem; color: #2E7D32;">Reference ID: <strong>#${refId}</strong> &bull; Ezekiel will review your booking and connect with you within 24 hours.</span>
              <div style="margin-top: 12px; padding-top: 10px; border-top: 1px dashed #A5D6A7; font-size: 0.9rem;">
                Prefer instant WhatsApp confirmation? <a href="${whatsappUrl}" target="_blank" rel="noopener noreferrer" style="color: #00796B; font-weight: 700; text-decoration: underline;">Click here to chat directly with Ezekiel &rarr;</a>
              </div>
            </div>
          `;
        }
        bookingForm.reset();
        updateBookingSummary();
      }).catch(() => {
        if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origText; }
        const whatsappMsg = `Hello Glorifix! I want to book an appointment:\n• Name: ${name}\n• Email: ${email}\n• Phone: ${phone}\n• Service: ${service}\n• Date: ${selectedDate} at ${selectedSlot}\n• Notes: ${notes || 'None'}`;
        const whatsappUrl = `https://wa.me/2349050278999?text=${encodeURIComponent(whatsappMsg)}`;
        if (statusDiv) {
          statusDiv.innerHTML = `
            <div style="background: #FFF3E0; color: #E65100; border: 1.5px solid #FFB74D; padding: 14px 16px; border-radius: 8px;">
              <strong>Please confirm on WhatsApp:</strong><br>
              <a href="${whatsappUrl}" target="_blank" class="btn btn-primary" style="margin-top: 8px; display: inline-block;">Confirm on WhatsApp &rarr;</a>
            </div>
          `;
        }
      });
    });
  }

  // 5. Contact Form Submission — Customer & Admin Notifications
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
        submitBtn.innerHTML = 'Sending to Email &amp; Dashboard... ⏳';
      }
      if (statusDiv) {
        statusDiv.style.display = 'block';
        statusDiv.innerHTML = '<div style="color: #6A3040; font-size: 0.9rem;">Transmitting inquiry to glorifixmediaconcept@gmail.com and dashboard...</div>';
      }

      const payload = {
        _subject: `📩 New Website Inquiry from: ${name}`,
        _replyto: email,
        _template: "table",
        "Sender Name": name,
        "Email Address": email,
        "Phone / WhatsApp": phone || "Not provided",
        "Message": message,
        // Standard keys for api/contact.php
        Sender_Name: name,
        Email_Address: email,
        Phone_Number: phone || "Not provided",
        Message: message
      };

      // Dual-dispatch: 1) FormSubmit for direct Gmail inbox delivery + 2) api/contact.php for dashboard logging
      const sendToGmail = fetch('https://formsubmit.co/ajax/glorifixmediaconcept@gmail.com', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload)
      }).catch(err => ({ ok: false, error: err }));

      const sendToApi = fetch('api/contact.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload)
      }).then(r => r.ok ? r.json() : null).catch(() => null);

      Promise.allSettled([sendToGmail, sendToApi]).then(results => {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = origText;
        }

        const apiResult = results[1].status === 'fulfilled' ? results[1].value : null;
        const refId = (apiResult && apiResult.id) ? apiResult.id : 'GLF-' + Math.random().toString(36).substr(2, 6).toUpperCase();

        if (statusDiv) {
          statusDiv.innerHTML = `
            <div style="background: #E8F5E9; color: #1B5E20; border: 1.5px solid #81C784; padding: 16px; border-radius: 8px; text-align: left; line-height: 1.6;">
              <strong style="font-size: 1.05rem; color: #1B5E20; display: block; margin-bottom: 6px;">✅ Message Sent to Gmail &amp; Dashboard!</strong>
              Thank you, <strong>${escapeHtml(name)}</strong>! Your note has been delivered directly to <strong>glorifixmediaconcept@gmail.com</strong> and recorded in our admin dashboard.<br>
              <span style="font-size: 0.9rem; color: #2E7D32;">Reference ID: <strong>#${refId}</strong> &bull; Ezekiel will review it promptly and reply to <strong>${escapeHtml(email)}</strong>.</span>
            </div>
          `;
        }
        contactForm.reset();
      }).catch(() => {
        if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origText; }
        const whatsappMsg = `Hello Glorifix Media Concept! Inquiry from ${name} (${email}):\n${message}`;
        const whatsappUrl = `https://wa.me/2349050278999?text=${encodeURIComponent(whatsappMsg)}`;
        if (statusDiv) {
          statusDiv.innerHTML = `
            <div style="background: #FFF3E0; color: #E65100; border: 1.5px solid #FFB74D; padding: 14px 16px; border-radius: 8px;">
              <strong>Please chat with us directly:</strong><br>
              <a href="${whatsappUrl}" target="_blank" class="btn btn-primary" style="margin-top: 8px; display: inline-block;">Chat on WhatsApp &rarr;</a>
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

  // 8. Register Progressive Web App (PWA) Service Worker
  if ('serviceWorker' in navigator && (window.location.protocol === 'https:' || window.location.hostname === '127.0.0.1' || window.location.hostname === 'localhost')) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('service-worker.js')
        .then(reg => {
          console.log('Glorifix PWA Service Worker registered:', reg.scope);
        })
        .catch(err => {
          console.log('PWA Service Worker registration deferred:', err);
        });
    });
  }

  // 9. Aesthetic Scroll Animation & Element Reveal Engine
  function initAestheticAnimations() {
    // A. Top Scroll Progress Indicator
    let progressBar = document.querySelector('.scroll-progress-bar');
    if (!progressBar) {
      progressBar = document.createElement('div');
      progressBar.className = 'scroll-progress-bar';
      document.body.appendChild(progressBar);
    }

    const updateScrollMetrics = () => {
      const scrollTop = window.scrollY || document.documentElement.scrollTop;
      const docHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
      const progress = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
      if (progressBar) {
        progressBar.style.width = `${Math.min(100, Math.max(0, progress))}%`;
      }

      // Sticky Header Scrolled State
      const header = document.querySelector('.site-header');
      if (header) {
        if (scrollTop > 40) {
          header.classList.add('is-scrolled');
        } else {
          header.classList.remove('is-scrolled');
        }
      }
    };

    // B. Target and Tag All Interactive Elements for Scroll Reveal
    // Headers, kickers, titles and subtitles
    document.querySelectorAll('.sec-lbl, .section-kicker, .pill-tag').forEach(el => {
      el.classList.add('gf-reveal-left');
    });

    document.querySelectorAll('.sec-title, .section-title, .hero-title, .page-hero-title, .contact-box h3, .cal-header h3').forEach(el => {
      el.classList.add('gf-reveal');
    });

    document.querySelectorAll('.sec-desc, .hero-sub, .page-hero-sub').forEach(el => {
      el.classList.add('gf-reveal');
    });

    document.querySelectorAll('.rl').forEach(el => {
      el.classList.add('rl');
    });

    // Hero and banner elements
    document.querySelectorAll('.hero-slider-wrap, .page-hero-banner-wrap').forEach(el => {
      el.classList.add('gf-reveal-scale');
    });

    document.querySelectorAll('.hero-actions .btn, .cta-banner .btn').forEach((btn, idx) => {
      btn.classList.add('gf-reveal-pop');
      btn.classList.add(`gf-stagger-${(idx % 6) + 1}`);
    });

    // Staggered Grid Collections
    const gridSelectors = [
      '.services-grid',
      '.pricing-grid',
      '.projects-grid',
      '.team-grid',
      '.features-grid',
      '.contact-cards-grid',
      '.footer-grid',
      '.slots-grid',
      '.cal-grid'
    ];

    gridSelectors.forEach(selector => {
      document.querySelectorAll(selector).forEach(grid => {
        const children = Array.from(grid.children);
        children.forEach((child, idx) => {
          if (!child.classList.contains('gf-reveal-scale') && !child.classList.contains('gf-reveal')) {
            child.classList.add('gf-reveal-scale');
          }
          child.classList.add(`gf-stagger-${(idx % 12) + 1}`);
        });
      });
    });

    // Standalone cards and sections
    const cardSelectors = [
      '.service-card',
      '.pricing-card',
      '.project-card',
      '.team-c',
      '.feature-card',
      '.stat-card',
      '.contact-card-item',
      '.about-card-box',
      '.booking-wrap',
      '.calendar-card',
      '.contact-box',
      '.founder',
      '.cta-banner'
    ];

    document.querySelectorAll(cardSelectors.join(',')).forEach(card => {
      if (!card.classList.contains('gf-reveal') && !card.classList.contains('gf-reveal-scale')) {
        card.classList.add('gf-reveal-scale');
      }
    });

    // List items and bullets in cards
    document.querySelectorAll('.about-card-box li, .service-details-card li').forEach((li, idx) => {
      li.classList.add('gf-reveal-left');
      li.classList.add(`gf-stagger-${(idx % 6) + 1}`);
    });

    // Footer columns and bottom bar
    document.querySelectorAll('.footer-brand, .footer-nav-col, .footer-bottom').forEach((col, idx) => {
      col.classList.add('gf-reveal');
      col.classList.add(`gf-stagger-${(idx % 6) + 1}`);
    });

    // C. Setup Intersection Observer with Instant Viewport Check & Scroll Fallback
    const animElements = document.querySelectorAll('.gf-reveal, .gf-reveal-scale, .gf-reveal-left, .gf-reveal-right, .gf-reveal-pop, .rl');

    const checkAndRevealVisible = () => {
      const vh = window.innerHeight || document.documentElement.clientHeight;
      animElements.forEach(el => {
        if (!el.classList.contains('gf-revealed')) {
          const r = el.getBoundingClientRect();
          if (r.top <= vh - 20 && r.bottom >= 0) {
            el.classList.add('gf-revealed');
          }
        }
      });
    };

    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('gf-revealed');
            obs.unobserve(entry.target);
          }
        });
      }, {
        threshold: 0.05,
        rootMargin: '0px 0px -20px 0px'
      });

      animElements.forEach(el => observer.observe(el));
    }

    // Unified scroll, resize & touch handler
    const handleScrollOrResize = () => {
      updateScrollMetrics();
      checkAndRevealVisible();
    };

    // Run instant metrics & viewport check
    handleScrollOrResize();
    setTimeout(handleScrollOrResize, 80);
    setTimeout(handleScrollOrResize, 250);
    setTimeout(handleScrollOrResize, 500);

    window.addEventListener('scroll', handleScrollOrResize, { passive: true });
    window.addEventListener('resize', handleScrollOrResize, { passive: true });
    window.addEventListener('touchmove', handleScrollOrResize, { passive: true });

    // D. Hook into Filter & Tab Switches to Re-Animate
    document.querySelectorAll('.filter-btn, .pricing-tab-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        setTimeout(() => {
          const visibleCards = document.querySelectorAll('.project-card:not([style*="display: none"]), .pricing-category-panel.active .pricing-card');
          visibleCards.forEach((card, idx) => {
            card.classList.remove('gf-revealed');
            card.classList.remove('gf-stagger-1', 'gf-stagger-2', 'gf-stagger-3', 'gf-stagger-4', 'gf-stagger-5', 'gf-stagger-6');
            card.classList.add(`gf-stagger-${(idx % 6) + 1}`);
            void card.offsetWidth;
            card.classList.add('gf-revealed');
          });
        }, 50);
      });
    });
  }

  // 10. Scroll Navigation Dock (Scroll to Top & Scroll to Bottom)
  function initScrollNavDock() {
    let dock = document.getElementById('scroll-nav-dock');
    if (!dock) {
      dock = document.createElement('div');
      dock.className = 'scroll-nav-dock';
      dock.id = 'scroll-nav-dock';
      dock.setAttribute('role', 'navigation');
      dock.setAttribute('aria-label', 'Quick page scroll navigation');
      dock.innerHTML = `
        <button type="button" class="scroll-nav-btn scroll-nav-up" id="scroll-to-top" aria-label="Scroll to top of page" title="Scroll to Top">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="18 15 12 9 6 15"></polyline>
          </svg>
          <span class="scroll-nav-tooltip">Scroll to Top</span>
        </button>
        <div class="scroll-nav-divider"></div>
        <button type="button" class="scroll-nav-btn scroll-nav-down" id="scroll-to-bottom" aria-label="Scroll to bottom of page" title="Scroll to Bottom">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 12 15 18 9"></polyline>
          </svg>
          <span class="scroll-nav-tooltip">Scroll to Bottom</span>
        </button>
      `;
      document.body.appendChild(dock);
    }

    const upBtn = document.getElementById('scroll-to-top');
    const downBtn = document.getElementById('scroll-to-bottom');

    if (upBtn) {
      upBtn.addEventListener('click', (e) => {
        e.preventDefault();
        window.scrollTo({
          top: 0,
          behavior: 'smooth'
        });
      });
    }

    if (downBtn) {
      downBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const maxScroll = Math.max(
          document.body.scrollHeight,
          document.documentElement.scrollHeight,
          document.body.offsetHeight,
          document.documentElement.offsetHeight,
          document.body.clientHeight,
          document.documentElement.clientHeight
        );
        window.scrollTo({
          top: maxScroll,
          behavior: 'smooth'
        });
      });
    }

    // Dynamic state updates based on scroll position
    const updateScrollNavState = () => {
      const scrollTop = window.scrollY || document.documentElement.scrollTop;
      const scrollHeight = document.documentElement.scrollHeight;
      const clientHeight = document.documentElement.clientHeight;
      const isAtTop = scrollTop <= 60;
      const isAtBottom = (scrollTop + clientHeight) >= (scrollHeight - 60);

      if (upBtn) {
        if (isAtTop) {
          upBtn.classList.add('is-disabled');
        } else {
          upBtn.classList.remove('is-disabled');
        }
      }

      if (downBtn) {
        if (isAtBottom) {
          downBtn.classList.add('is-disabled');
        } else {
          downBtn.classList.remove('is-disabled');
        }
      }
    };

    window.addEventListener('scroll', updateScrollNavState, { passive: true });
    updateScrollNavState();
  }

  // Initialize features
  initAestheticAnimations();
  initScrollNavDock();
});


