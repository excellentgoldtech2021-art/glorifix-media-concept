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
    const revDt = document.getElementById('review-book-datetime');
    if (revDt) {
      revDt.textContent = `${selectedDate} at ${selectedSlot}`;
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

  // --- Multi-Step Controller for Booking Form ---
  let currentBookStep = 1;
  const totalBookSteps = 3;

  function setBookingStep(step) {
    if (step < 1 || step > totalBookSteps) return;
    currentBookStep = step;

    for (let i = 1; i <= totalBookSteps; i++) {
      const stepEl = document.getElementById(`book-step-${i}`);
      if (stepEl) {
        stepEl.classList.toggle('active', i === step);
      }
    }

    const stepper = document.getElementById('booking-stepper');
    if (stepper) {
      const indicators = stepper.querySelectorAll('.step-indicator');
      indicators.forEach(ind => {
        const sNum = parseInt(ind.getAttribute('data-step') || '1', 10);
        ind.classList.remove('active', 'completed');
        if (sNum === step) {
          ind.classList.add('active');
        } else if (sNum < step) {
          ind.classList.add('completed');
        }
      });
      const line1 = document.getElementById('book-line-1');
      const line2 = document.getElementById('book-line-2');
      if (line1) line1.classList.toggle('completed', step > 1);
      if (line2) line2.classList.toggle('completed', step > 2);
    }

    if (step === 3) {
      const revDt = document.getElementById('review-book-datetime');
      const revSvc = document.getElementById('review-book-service');
      const revCont = document.getElementById('review-book-contact');
      const bName = (document.getElementById('book-name')?.value || '').trim();
      const bEmail = (document.getElementById('book-email')?.value || '').trim();
      const bPhone = (document.getElementById('book-phone')?.value || '').trim();
      const bService = document.getElementById('book-service')?.value || 'Website Design & Development';

      if (revDt) revDt.textContent = `${selectedDate} at ${selectedSlot}`;
      if (revSvc) revSvc.textContent = bService;
      if (revCont) revCont.textContent = `${bName || 'Not specified'} (${bPhone || 'No phone'} • ${bEmail || 'No email'})`;
    }
  }

  const bookNext1 = document.getElementById('book-next-1');
  if (bookNext1) {
    bookNext1.addEventListener('click', () => {
      setBookingStep(2);
    });
  }

  const bookPrev2 = document.getElementById('book-prev-2');
  if (bookPrev2) {
    bookPrev2.addEventListener('click', () => {
      setBookingStep(1);
    });
  }

  const bookNext2 = document.getElementById('book-next-2');
  if (bookNext2) {
    bookNext2.addEventListener('click', () => {
      const bNameInput = document.getElementById('book-name');
      const bEmailInput = document.getElementById('book-email');
      const bPhoneInput = document.getElementById('book-phone');

      if (!bNameInput?.value.trim()) {
        bNameInput?.focus();
        bNameInput?.reportValidity();
        return;
      }
      if (!bEmailInput?.value.trim() || !bEmailInput?.checkValidity()) {
        bEmailInput?.focus();
        bEmailInput?.reportValidity();
        return;
      }
      if (!bPhoneInput?.value.trim()) {
        bPhoneInput?.focus();
        bPhoneInput?.reportValidity();
        return;
      }
      setBookingStep(3);
    });
  }

  const bookPrev3 = document.getElementById('book-prev-3');
  if (bookPrev3) {
    bookPrev3.addEventListener('click', () => {
      setBookingStep(2);
    });
  }

  const bookingStepper = document.getElementById('booking-stepper');
  if (bookingStepper) {
    bookingStepper.querySelectorAll('.step-indicator').forEach(ind => {
      ind.addEventListener('click', () => {
        const targetStep = parseInt(ind.getAttribute('data-step') || '1', 10);
        if (targetStep < currentBookStep) {
          setBookingStep(targetStep);
        } else if (targetStep === 2) {
          setBookingStep(2);
        } else if (targetStep === 3) {
          const bNameInput = document.getElementById('book-name');
          const bEmailInput = document.getElementById('book-email');
          if (bNameInput?.value.trim() && bEmailInput?.value.trim()) {
            setBookingStep(3);
          }
        }
      });
    });
  }

  // --- Multi-Step Controller for Direct Contact Form ---
  let currentContactStep = 1;
  const totalContactSteps = 2;

  function setContactStep(step) {
    if (step < 1 || step > totalContactSteps) return;
    currentContactStep = step;

    for (let i = 1; i <= totalContactSteps; i++) {
      const stepEl = document.getElementById(`contact-step-${i}`);
      if (stepEl) {
        stepEl.classList.toggle('active', i === step);
      }
    }

    const stepper = document.getElementById('contact-stepper');
    if (stepper) {
      const indicators = stepper.querySelectorAll('.step-indicator');
      indicators.forEach(ind => {
        const sNum = parseInt(ind.getAttribute('data-step') || '1', 10);
        ind.classList.remove('active', 'completed');
        if (sNum === step) {
          ind.classList.add('active');
        } else if (sNum < step) {
          ind.classList.add('completed');
        }
      });
      const line1 = document.getElementById('contact-line-1');
      if (line1) line1.classList.toggle('completed', step > 1);
    }

    if (step === 2) {
      const revSender = document.getElementById('review-contact-sender');
      const cName = (document.getElementById('contact-name')?.value || '').trim();
      const cEmail = (document.getElementById('contact-email')?.value || '').trim();
      const cPhone = (document.getElementById('contact-phone')?.value || '').trim();
      if (revSender) {
        revSender.textContent = `${cName || 'Valued Client'} (${cEmail || 'No email'}${cPhone ? ' • ' + cPhone : ''})`;
      }
    }
  }

  const contactNext1 = document.getElementById('contact-next-1');
  if (contactNext1) {
    contactNext1.addEventListener('click', () => {
      const cNameInput = document.getElementById('contact-name');
      const cEmailInput = document.getElementById('contact-email');
      if (!cNameInput?.value.trim()) {
        cNameInput?.focus();
        cNameInput?.reportValidity();
        return;
      }
      if (!cEmailInput?.value.trim() || !cEmailInput?.checkValidity()) {
        cEmailInput?.focus();
        cEmailInput?.reportValidity();
        return;
      }
      setContactStep(2);
    });
  }

  const contactPrev2 = document.getElementById('contact-prev-2');
  if (contactPrev2) {
    contactPrev2.addEventListener('click', () => {
      setContactStep(1);
    });
  }

  const contactStepper = document.getElementById('contact-stepper');
  if (contactStepper) {
    contactStepper.querySelectorAll('.step-indicator').forEach(ind => {
      ind.addEventListener('click', () => {
        const targetStep = parseInt(ind.getAttribute('data-step') || '1', 10);
        if (targetStep === 1) {
          setContactStep(1);
        } else if (targetStep === 2) {
          const cNameInput = document.getElementById('contact-name');
          const cEmailInput = document.getElementById('contact-email');
          if (cNameInput?.value.trim() && cEmailInput?.value.trim()) {
            setContactStep(2);
          }
        }
      });
    });
  }

  // --- Interactive Office Map & Branch Switcher ---
  const mapTabHq = document.getElementById('map-tab-hq');
  const mapTabAbuja = document.getElementById('map-tab-abuja');
  const mapIframe = document.getElementById('office-map-iframe');
  const mapDetailAddress = document.getElementById('map-detail-address');
  const mapDetailLandmark = document.getElementById('map-detail-landmark');
  const mapDetailHours = document.getElementById('map-detail-hours');
  const mapActionOpen = document.getElementById('map-action-open');
  const mapActionDirections = document.getElementById('map-action-directions');
  const mapActionWhatsapp = document.getElementById('map-action-whatsapp');

  const branches = {
    hq: {
      name: "Ikare-Akoko HQ Studio",
      iframeSrc: "https://maps.google.com/maps?q=General+Hospital+Ikare-Akoko+Ondo+State+Nigeria&t=&z=15&ie=UTF8&iwloc=&output=embed",
      address: "Opposite General Hospital, Behind G-Delight Hotel, Ikare-Akoko, Ondo State, Nigeria",
      landmark: "Opposite General Hospital / Behind G-Delight Hotel",
      hours: "Monday – Saturday: 8:00 AM – 8:00 PM (Sunday by Appointment)",
      openUrl: "https://www.google.com/maps/search/?api=1&query=Opposite+General+Hospital+Ikare+Akoko+Ondo+State",
      dirUrl: "https://www.google.com/maps/dir/?api=1&destination=Opposite+General+Hospital+Ikare+Akoko+Ondo+State",
      waText: "Hello Glorifix Media Concept! I am visiting your Ikare-Akoko HQ Studio."
    },
    abuja: {
      name: "Abuja Office Hub",
      iframeSrc: "https://maps.google.com/maps?q=University+of+Abuja+Mini+Campus+Gwagwalada+Abuja&t=&z=15&ie=UTF8&iwloc=&output=embed",
      address: "Phase 2, Shop 7, Opp. UniAbuja Mini-Campus, Gwagwalada, Abuja FCT, Nigeria",
      landmark: "Opposite UniAbuja Mini-Campus Main Gate, Phase 2 Hub",
      hours: "Monday – Saturday: 8:00 AM – 8:00 PM (Sunday by Appointment)",
      openUrl: "https://www.google.com/maps/search/?api=1&query=University+of+Abuja+Mini+Campus+Gwagwalada+Abuja",
      dirUrl: "https://www.google.com/maps/dir/?api=1&destination=University+of+Abuja+Mini+Campus+Gwagwalada+Abuja",
      waText: "Hello Glorifix Media Concept! I am visiting your Abuja Studio Hub in Gwagwalada."
    }
  };

  const branchCardHq = document.getElementById('branch-card-hq');
  const branchCardAbuja = document.getElementById('branch-card-abuja');
  const branchStatusHq = document.getElementById('branch-status-hq');
  const branchStatusAbuja = document.getElementById('branch-status-abuja');

  function switchMapBranch(branchKey) {
    const data = branches[branchKey];
    if (!data) return;

    if (mapTabHq) {
      mapTabHq.classList.toggle('active', branchKey === 'hq');
      mapTabHq.setAttribute('aria-selected', branchKey === 'hq');
    }
    if (mapTabAbuja) {
      mapTabAbuja.classList.toggle('active', branchKey === 'abuja');
      mapTabAbuja.setAttribute('aria-selected', branchKey === 'abuja');
    }
    if (branchCardHq) {
      branchCardHq.classList.toggle('active', branchKey === 'hq');
    }
    if (branchCardAbuja) {
      branchCardAbuja.classList.toggle('active', branchKey === 'abuja');
    }
    if (branchStatusHq) {
      branchStatusHq.innerHTML = branchKey === 'hq' ? '<span>✓</span> Currently Active on Map' : '<span>🗺️</span> Click to View Location on Map';
    }
    if (branchStatusAbuja) {
      branchStatusAbuja.innerHTML = branchKey === 'abuja' ? '<span>✓</span> Currently Active on Map' : '<span>🗺️</span> Click to View Location on Map';
    }

    if (mapIframe) mapIframe.src = data.iframeSrc;
    if (mapDetailAddress) mapDetailAddress.textContent = data.address;
    if (mapDetailLandmark) mapDetailLandmark.textContent = data.landmark;
    if (mapDetailHours) mapDetailHours.textContent = data.hours;
    if (mapActionOpen) mapActionOpen.href = data.openUrl;
    if (mapActionDirections) mapActionDirections.href = data.dirUrl;
    if (mapActionWhatsapp) {
      mapActionWhatsapp.href = `https://wa.me/2349050278999?text=${encodeURIComponent(data.waText)}`;
    }
  }

  if (mapTabHq) {
    mapTabHq.addEventListener('click', () => switchMapBranch('hq'));
  }
  if (mapTabAbuja) {
    mapTabAbuja.addEventListener('click', () => switchMapBranch('abuja'));
  }
  if (branchCardHq) {
    branchCardHq.addEventListener('click', () => switchMapBranch('hq'));
  }
  if (branchCardAbuja) {
    branchCardAbuja.addEventListener('click', () => switchMapBranch('abuja'));
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

      // Priority 1: Native cPanel PHP API (Dual email delivery to Ezekiel's Primary Inbox + Client Confirmation)
      fetch('api/contact.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload)
      })
      .then(async (response) => {
        let result = null;
        try {
          result = await response.json();
        } catch (e) {
          result = null;
        }

        if (response.ok && result && result.success) {
          return result;
        }

        // Fallback to FormSubmit if PHP endpoint is unavailable
        const fsResp = await fetch('https://formsubmit.co/ajax/glorifixmediaconcept@gmail.com', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify(payload)
        });
        return {
          success: fsResp.ok,
          id: 'GLF-' + Math.random().toString(36).substr(2, 6).toUpperCase(),
          client_name: name,
          client_email: email
        };
      })
      .then(result => {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = origText;
        }

        const refId = result.id || ('GLF-' + Math.random().toString(36).substr(2, 6).toUpperCase());
        const whatsappMsg = `Hello Glorifix Media Concept! I just booked an appointment on your website:\n• Name: ${name}\n• Email: ${email}\n• Phone: ${phone}\n• Service: ${service}\n• Date: ${selectedDate} at ${selectedSlot}\n• Notes: ${notes || 'None'}\n• Ref: ${refId}`;
        const whatsappUrl = `https://wa.me/2349050278999?text=${encodeURIComponent(whatsappMsg)}`;

        if (statusDiv) {
          statusDiv.innerHTML = `
            <div style="background: #E8F5E9; color: #1B5E20; border: 1.5px solid #81C784; padding: 18px; border-radius: 10px; text-align: left; line-height: 1.6; box-shadow: 0 4px 14px rgba(0,0,0,0.04);">
              <strong style="font-size: 1.1rem; color: #1B5E20; display: block; margin-bottom: 6px;">🎉 Consultation Confirmed!</strong>
              Thank you, <strong>${escapeHtml(name)}</strong>! Your appointment has been registered with Reference ID: <strong>#${refId}</strong>.<br>
              <div style="margin: 10px 0; padding: 10px 14px; background: rgba(255,255,255,0.7); border-radius: 6px; border-left: 3px solid #2E7D32; font-size: 0.92rem;">
                ✉️ <strong>Confirmation Sent:</strong> A personalized confirmation receipt with your scheduled date (<strong>${escapeHtml(selectedDate)} at ${escapeHtml(selectedSlot)}</strong>) has been dispatched to <strong>${escapeHtml(email)}</strong>.<br>
                📥 <strong>Priority Inbox:</strong> Lead Consultant Ezekiel has received your brief directly in his primary inbox.
              </div>
              <div style="margin-top: 12px; font-size: 0.9rem;">
                Need instant WhatsApp confirmation? <a href="${whatsappUrl}" target="_blank" rel="noopener noreferrer" style="color: #00796B; font-weight: 700; text-decoration: underline;">Click here to chat directly with Ezekiel &rarr;</a>
              </div>
            </div>
          `;
        }
        bookingForm.reset();
        updateBookingSummary();
      })
      .catch(() => {
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

      // Priority 1: Native cPanel PHP API (Dual email delivery to Ezekiel's Primary Inbox + Client Confirmation)
      fetch('api/contact.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload)
      })
      .then(async (response) => {
        let result = null;
        try {
          result = await response.json();
        } catch (e) {
          result = null;
        }

        if (response.ok && result && result.success) {
          return result;
        }

        // Fallback to FormSubmit if PHP endpoint is unavailable
        const fsResp = await fetch('https://formsubmit.co/ajax/glorifixmediaconcept@gmail.com', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify(payload)
        });
        return {
          success: fsResp.ok,
          id: 'GLF-' + Math.random().toString(36).substr(2, 6).toUpperCase(),
          client_name: name,
          client_email: email
        };
      })
      .then(result => {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = origText;
        }

        const refId = result.id || ('GLF-' + Math.random().toString(36).substr(2, 6).toUpperCase());

        if (statusDiv) {
          statusDiv.innerHTML = `
            <div style="background: #E8F5E9; color: #1B5E20; border: 1.5px solid #81C784; padding: 18px; border-radius: 10px; text-align: left; line-height: 1.6; box-shadow: 0 4px 14px rgba(0,0,0,0.04);">
              <strong style="font-size: 1.1rem; color: #1B5E20; display: block; margin-bottom: 6px;">✅ Message Sent Successfully!</strong>
              Thank you, <strong>${escapeHtml(name)}</strong>! Your inquiry has been registered with Reference ID: <strong>#${refId}</strong>.<br>
              <div style="margin: 10px 0; padding: 10px 14px; background: rgba(255,255,255,0.7); border-radius: 6px; border-left: 3px solid #2E7D32; font-size: 0.92rem;">
                ✉️ <strong>Confirmation Sent:</strong> A personalized confirmation email has been dispatched to <strong>${escapeHtml(email)}</strong>.<br>
                📥 <strong>Priority Inbox:</strong> Ezekiel has received your message directly in his primary inbox and will review it promptly within 24 hours.
              </div>
            </div>
          `;
        }
        contactForm.reset();
      })
      .catch(() => {
        if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origText; }
        if (statusDiv) {
          statusDiv.innerHTML = '<div style="color: #C2185B; font-weight: 600;">⚠️ There was a temporary issue sending your message. Please reach out to <strong>glorifixmediaconcept@gmail.com</strong> or call <strong>09050278999</strong> directly.</div>';
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
      '.statement-cards-grid',
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
      '.statement-card',
      '.contact-card-item',
      '.contact-map-card',
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
      const isAtTop = scrollTop <= 50;
      const isAtBottom = (scrollTop + clientHeight) >= (scrollHeight - 60);

      // Show floating scroll buttons when scrolling past 70px
      if (scrollTop > 70) {
        dock.classList.add('is-visible');
      } else {
        dock.classList.remove('is-visible');
      }

      if (upBtn) {
        if (isAtTop) {
          upBtn.classList.add('is-disabled');
          upBtn.setAttribute('aria-disabled', 'true');
        } else {
          upBtn.classList.remove('is-disabled');
          upBtn.removeAttribute('aria-disabled');
        }
      }

      if (downBtn) {
        if (isAtBottom) {
          downBtn.classList.add('is-disabled');
          downBtn.setAttribute('aria-disabled', 'true');
        } else {
          downBtn.classList.remove('is-disabled');
          downBtn.removeAttribute('aria-disabled');
        }
      }
    };

    window.addEventListener('scroll', updateScrollNavState, { passive: true });
    updateScrollNavState();
  }

  /* =========================================================================
     6. Interactive Office Map & Branch Switcher
     ========================================================================= */
  function initOfficeMapSwitcher() {
    const mapCard = document.getElementById('office-map-section');
    if (!mapCard) return;

    const iframe = document.getElementById('office-map-iframe');
    const tabHq = document.getElementById('map-tab-hq');
    const tabAbuja = document.getElementById('map-tab-abuja');
    const branchCardHq = document.getElementById('branch-card-hq');
    const branchCardAbuja = document.getElementById('branch-card-abuja');
    const branchStatusHq = document.getElementById('branch-status-hq');
    const branchStatusAbuja = document.getElementById('branch-status-abuja');
    const addressEl = document.getElementById('map-detail-address');
    const landmarkEl = document.getElementById('map-detail-landmark');
    const hoursEl = document.getElementById('map-detail-hours');
    const actionOpen = document.getElementById('map-action-open');
    const actionDirections = document.getElementById('map-action-directions');
    const actionWhatsapp = document.getElementById('map-action-whatsapp');

    const branches = {
      hq: {
        name: 'Ikare-Akoko Headquarters',
        address: 'Opposite General Hospital, Behind G-Delight Hotel, Ikare-Akoko, Ondo State, Nigeria',
        landmark: 'Opposite General Hospital / Behind G-Delight Hotel',
        hours: 'Monday – Saturday: 8:00 AM – 8:00 PM (Sunday by Appointment)',
        embedSrc: 'https://maps.google.com/maps?q=General+Hospital+Ikare-Akoko+Ondo+State+Nigeria&t=&z=15&ie=UTF8&iwloc=&output=embed',
        searchUrl: 'https://www.google.com/maps/search/?api=1&query=Opposite+General+Hospital+Ikare+Akoko+Ondo+State',
        dirUrl: 'https://www.google.com/maps/dir/?api=1&destination=Opposite+General+Hospital+Ikare+Akoko+Ondo+State',
        waText: 'Hello Glorifix Media, I am visiting your Ikare-Akoko Office'
      },
      abuja: {
        name: 'Abuja Operations Hub',
        address: 'Phase 2, Shop 7, Opp. University of Abuja Mini-Campus, Gwagwalada, Abuja FCT, Nigeria',
        landmark: 'Opp. University of Abuja Mini-Campus / Phase 2 Commercial Strip',
        hours: 'Monday – Saturday: 8:30 AM – 7:30 PM (Sunday by Appointment)',
        embedSrc: 'https://maps.google.com/maps?q=University+of+Abuja+Mini-Campus+Gwagwalada+Abuja+Nigeria&t=&z=15&ie=UTF8&iwloc=&output=embed',
        searchUrl: 'https://www.google.com/maps/search/?api=1&query=University+of+Abuja+Mini-Campus+Gwagwalada+Abuja',
        dirUrl: 'https://www.google.com/maps/dir/?api=1&destination=University+of+Abuja+Mini-Campus+Gwagwalada+Abuja',
        waText: 'Hello Glorifix Media, I am visiting your Abuja Office'
      }
    };

    function switchBranch(key) {
      const data = branches[key];
      if (!data) return;

      if (tabHq) {
        tabHq.classList.toggle('active', key === 'hq');
        tabHq.setAttribute('aria-selected', key === 'hq');
      }
      if (tabAbuja) {
        tabAbuja.classList.toggle('active', key === 'abuja');
        tabAbuja.setAttribute('aria-selected', key === 'abuja');
      }
      if (branchCardHq) {
        branchCardHq.classList.toggle('active', key === 'hq');
      }
      if (branchCardAbuja) {
        branchCardAbuja.classList.toggle('active', key === 'abuja');
      }
      if (branchStatusHq) {
        branchStatusHq.innerHTML = key === 'hq' ? '<span>✓</span> Currently Active on Map' : '<span>🗺️</span> Click to View Location on Map';
      }
      if (branchStatusAbuja) {
        branchStatusAbuja.innerHTML = key === 'abuja' ? '<span>✓</span> Currently Active on Map' : '<span>🗺️</span> Click to View Location on Map';
      }

      if (iframe && iframe.src !== data.embedSrc) {
        iframe.src = data.embedSrc;
      }
      if (addressEl) addressEl.textContent = data.address;
      if (landmarkEl) landmarkEl.textContent = data.landmark;
      if (hoursEl) hoursEl.textContent = data.hours;

      if (actionOpen) actionOpen.href = data.searchUrl;
      if (actionDirections) actionDirections.href = data.dirUrl;
      if (actionWhatsapp) {
        actionWhatsapp.href = `https://wa.me/2349050278999?text=${encodeURIComponent(data.waText)}`;
      }
    }

    if (tabHq) {
      tabHq.addEventListener('click', () => switchBranch('hq'));
    }
    if (tabAbuja) {
      tabAbuja.addEventListener('click', () => switchBranch('abuja'));
    }
    if (branchCardHq) {
      branchCardHq.addEventListener('click', () => switchBranch('hq'));
    }
    if (branchCardAbuja) {
      branchCardAbuja.addEventListener('click', () => switchBranch('abuja'));
    }
  }

  // Initialize features
  initAestheticAnimations();
  initScrollNavDock();
  initOfficeMapSwitcher();
});


