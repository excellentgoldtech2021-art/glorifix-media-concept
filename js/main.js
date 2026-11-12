/**
 * Glorifix Media Concept — Interactive JavaScript Engine
 * Handles Navigation, Filtering, Modal Lightbox, Booking Widget, Contact Form & WhatsApp Integration
 */

document.addEventListener('DOMContentLoaded', () => {
  // --- 1. Project Case Studies Database ---
  const projectsData = {
    novaflow: {
      title: "NovaFlow SaaS Analytics Platform",
      category: "Web Design & Development",
      categoryTag: "web",
      image: "assets/projects/project-novaflow.svg",
      metric: "+185% Engagement",
      client: "NovaFlow Cloud Corp",
      timeline: "3 Weeks",
      tools: "Next.js 14, Tailwind CSS, TypeScript, Chart.js, Node.js",
      challenge: "The client needed an enterprise-grade cloud analytics interface that could process over 200k data points while maintaining sub-second render speeds and mobile usability.",
      solution: "Engineered a modular responsive dashboard with custom SVG charting, glassmorphic UI cards, automated dark/light switching, and frictionless data export.",
      results: "Drove a 185% increase in daily active user engagement and reduced page load times by 62% across all mobile devices."
    },
    lumina: {
      title: "Lumina Botanics Luxury Identity & Packaging",
      category: "Graphic Design",
      categoryTag: "graphic",
      image: "assets/projects/project-lumina.svg",
      metric: "Complete Rebrand",
      client: "Lumina Organic Cosmetics",
      timeline: "2 Weeks",
      tools: "Adobe Illustrator, Photoshop, Blender 3D, InDesign",
      challenge: "An artisanal botanical cosmetics brand was getting lost on store shelves due to generic packaging and disjointed typography.",
      solution: "Developed a luxury brand identity system featuring gold-foil accents, botanical line work, bespoke serifs, and 3D photorealistic packaging mockups.",
      results: "Secured distribution in 18 premium retail boutiques within 60 days of packaging rollout."
    },
    hyperpulse: {
      title: "HyperPulse 30-Day Viral Social Campaign",
      category: "Social Media",
      categoryTag: "social",
      image: "assets/projects/project-hyperpulse.svg",
      metric: "3.4M+ Organic Views",
      client: "HyperPulse Tech",
      timeline: "4 Weeks",
      tools: "Premiere Pro, After Effects, CapCut, Meta Ads Manager",
      challenge: "Scaling an early-stage consumer tech brand with zero paid ad budget in a crowded social marketplace.",
      solution: "Crafted 24 high-retention short-form video reels with custom animated kinetic typography, viral audio pacing, and educational hooks.",
      results: "Generated 3.4M+ organic views, 12,480 qualified inbound leads, and 45k new engaged followers in under one month."
    },
    apex: {
      title: "Apex Kinetic Fitness Tracker App UI/UX",
      category: "Web Design & Development",
      categoryTag: "web",
      image: "assets/projects/project-apex.svg",
      metric: "4.9 ★ Rating",
      client: "Apex Health Innovations",
      timeline: "3.5 Weeks",
      tools: "Figma, React Native, CSS Animation, Lottie",
      challenge: "Users were abandoning existing fitness apps due to cluttered menus, boring monochrome statistics, and sluggish navigation.",
      solution: "Designed an energetic, neon-accented UI featuring interactive activity rings, weekly streak badges, and biometric visual feedback.",
      results: "Achieved a 4.9-star average rating and a 42% boost in 30-day user retention."
    },
    aura: {
      title: "Aura Specialty Coffee Roasters Brand Identity",
      category: "Graphic Design",
      categoryTag: "graphic",
      image: "assets/projects/project-aura.svg",
      metric: "Bespoke Packaging",
      client: "Aura Roastery Ltd",
      timeline: "2 Weeks",
      tools: "Illustrator, Figma, Print Production Standards",
      challenge: "A premium specialty roastery required brand assets that communicated sustainable sourcing and artisanal craftsmanship.",
      solution: "Created an earthy, tactile brand identity with gold-foiled craft bags, custom origin badges, stationery suites, and cafe signage.",
      results: "Direct-to-consumer bag sales increased by 210% within the first quarter of relaunch."
    },
    zenith: {
      title: "Zenith FinTech Global Wire Portal",
      category: "Web Design & Development",
      categoryTag: "web",
      image: "assets/projects/project-zenith.svg",
      metric: "High Security UI",
      client: "Zenith Capital Group",
      timeline: "4 Weeks",
      tools: "TypeScript, React, Tailwind CSS, WebSockets",
      challenge: "Complex cross-border cryptocurrency and SWIFT transfers required a secure, intuitive user experience that minimized transaction friction.",
      solution: "Built a high-contrast web app with real-time candlestick charts, multi-currency wallet views, and 2-step verification workflows.",
      results: "Handled over $14M in transaction volume without a single UI-related support ticket."
    }
  };

  // --- 2. Header Scroll Effect & Mobile Navigation ---
  const header = document.querySelector('.site-header');
  const mobileToggle = document.getElementById('mobileToggle');
  const mobileDrawer = document.getElementById('mobileDrawer');
  const mobileNavLinks = document.querySelectorAll('.mobile-nav-link');

  window.addEventListener('scroll', () => {
    if (window.scrollY > 30) {
      header?.classList.add('header-scrolled');
    } else {
      header?.classList.remove('header-scrolled');
    }
  });

  if (mobileToggle && mobileDrawer) {
    mobileToggle.addEventListener('click', () => {
      mobileToggle.classList.toggle('active');
      mobileDrawer.classList.toggle('open');
      document.body.style.overflow = mobileDrawer.classList.contains('open') ? 'hidden' : '';
    });

    mobileNavLinks.forEach(link => {
      link.addEventListener('click', () => {
        mobileToggle.classList.remove('active');
        mobileDrawer.classList.remove('open');
        document.body.style.overflow = '';
      });
    });
  }

  // --- 3. Theme Toggle (Bright Mode / Warm Glow Mode) ---
  const themeToggle = document.getElementById('themeToggle');
  if (themeToggle) {
    const savedTheme = localStorage.getItem('gfm_theme');
    if (savedTheme === 'warm') {
      document.documentElement.setAttribute('data-theme', 'warm');
    }

    themeToggle.addEventListener('click', () => {
      const currentTheme = document.documentElement.getAttribute('data-theme');
      if (currentTheme === 'warm') {
        document.documentElement.removeAttribute('data-theme');
        localStorage.setItem('gfm_theme', 'default');
        showToast('☀️ Switched to Sunshine Bright mode');
      } else {
        document.documentElement.setAttribute('data-theme', 'warm');
        localStorage.setItem('gfm_theme', 'warm');
        showToast('✨ Switched to Warm Glow mode');
      }
    });
  }

  // --- 4. Portfolio Filter Tabs ---
  const filterBtns = document.querySelectorAll('.filter-btn');
  const portfolioCards = document.querySelectorAll('.portfolio-card');

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const filterValue = btn.getAttribute('data-filter');

      portfolioCards.forEach(card => {
        const cardCategory = card.getAttribute('data-category');
        if (filterValue === 'all' || cardCategory === filterValue) {
          card.style.display = 'flex';
          card.style.animation = 'fadeInCard 0.4s ease forwards';
        } else {
          card.style.display = 'none';
        }
      });
    });
  });

  // --- 5. Portfolio Lightbox / Case Study Modal ---
  const modalBackdrop = document.getElementById('portfolioModal');
  const modalCloseBtn = document.getElementById('modalCloseBtn');
  const modalImg = document.getElementById('modalImg');
  const modalCategory = document.getElementById('modalCategory');
  const modalTitle = document.getElementById('modalTitle');
  const modalClient = document.getElementById('modalClient');
  const modalTimeline = document.getElementById('modalTimeline');
  const modalTools = document.getElementById('modalTools');
  const modalMetric = document.getElementById('modalMetric');
  const modalChallenge = document.getElementById('modalChallenge');
  const modalSolution = document.getElementById('modalSolution');
  const modalResults = document.getElementById('modalResults');
  const modalWaAction = document.getElementById('modalWaAction');

  function openProjectModal(projectId) {
    const project = projectsData[projectId];
    if (!project || !modalBackdrop) return;

    modalImg.src = project.image;
    modalImg.alt = project.title;
    modalCategory.textContent = project.category;
    modalTitle.textContent = project.title;
    modalClient.textContent = project.client;
    modalTimeline.textContent = project.timeline;
    modalTools.textContent = project.tools;
    modalMetric.textContent = project.metric;
    modalChallenge.textContent = project.challenge;
    modalSolution.textContent = project.solution;
    modalResults.textContent = project.results;

    const waMsg = encodeURIComponent(`Hello Glorifix Media Concept! I was reviewing your case study "${project.title}" on your portfolio and I would love to discuss a similar project.`);
    modalWaAction.href = `https://wa.me/2349050278999?text=${waMsg}`;

    modalBackdrop.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeProjectModal() {
    if (!modalBackdrop) return;
    modalBackdrop.classList.remove('active');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('[data-project-trigger]').forEach(trigger => {
    trigger.addEventListener('click', (e) => {
      e.preventDefault();
      const projectId = trigger.getAttribute('data-project-trigger');
      openProjectModal(projectId);
    });
  });

  modalCloseBtn?.addEventListener('click', closeProjectModal);

  modalBackdrop?.addEventListener('click', (e) => {
    if (e.target === modalBackdrop) {
      closeProjectModal();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modalBackdrop?.classList.contains('active')) {
      closeProjectModal();
    }
  });

  // --- 6. Interactive Consultation & Booking Engine ---
  let selectedSession = "Quick 15-min Discovery Call";
  let selectedDay = "Mon, 16 Nov";
  let selectedTime = "10:00 AM";
  let selectedPlatform = "WhatsApp Audio/Video Call";

  // Session options
  const sessionOptions = document.querySelectorAll('.session-option');
  sessionOptions.forEach(opt => {
    opt.addEventListener('click', () => {
      sessionOptions.forEach(o => o.classList.remove('selected'));
      opt.classList.add('selected');
      selectedSession = opt.getAttribute('data-session-title') || opt.querySelector('h6')?.textContent;
    });
  });

  // Days chips
  const dayChips = document.querySelectorAll('.day-chip');
  dayChips.forEach(chip => {
    chip.addEventListener('click', () => {
      dayChips.forEach(c => c.classList.remove('selected'));
      chip.classList.add('selected');
      selectedDay = `${chip.querySelector('.day-name')?.textContent}, ${chip.querySelector('.day-num')?.textContent} Nov`;
    });
  });

  // Time slots
  const timeSlots = document.querySelectorAll('.time-slot');
  timeSlots.forEach(slot => {
    slot.addEventListener('click', () => {
      timeSlots.forEach(s => s.classList.remove('selected'));
      slot.classList.add('selected');
      selectedTime = slot.textContent.trim();
    });
  });

  // Platform choice
  const platformBtns = document.querySelectorAll('.platform-btn');
  platformBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      platformBtns.forEach(b => b.classList.remove('selected'));
      btn.classList.add('selected');
      selectedPlatform = btn.textContent.trim();
    });
  });

  // Booking Submit
  const bookingForm = document.getElementById('bookingWidgetForm');
  bookingForm?.addEventListener('submit', (e) => {
    e.preventDefault();
    const name = document.getElementById('bookingName')?.value.trim();
    const contact = document.getElementById('bookingContact')?.value.trim();

    if (!name || !contact) {
      showToast('⚠️ Please enter your name and phone/WhatsApp number');
      return;
    }

    const bookingMessage = `Hello Glorifix Media Concept! I would like to schedule a consultation:
• Name: ${name}
• Contact: ${contact}
• Session Type: ${selectedSession}
• Scheduled Date: ${selectedDay}
• Scheduled Time: ${selectedTime}
• Preferred Channel: ${selectedPlatform}
Looking forward to speaking with you!`;

    const waUrl = `https://wa.me/2349050278999?text=${encodeURIComponent(bookingMessage)}`;
    
    showToast('🎉 Booking Confirmed! Opening WhatsApp...');
    setTimeout(() => {
      window.open(waUrl, '_blank');
    }, 800);
  });

  // --- 7. Interactive Contact Form & Direct WhatsApp Dispatch ---
  const servicePills = document.querySelectorAll('.service-pill-select');
  const budgetPresets = document.querySelectorAll('.budget-preset-btn');
  let selectedServices = ['Web Design & Development'];
  let selectedBudget = '$500 - $1,500';

  servicePills.forEach(pill => {
    pill.addEventListener('click', () => {
      pill.classList.toggle('selected');
      const val = pill.textContent.trim();
      if (pill.classList.contains('selected')) {
        if (!selectedServices.includes(val)) selectedServices.push(val);
      } else {
        selectedServices = selectedServices.filter(s => s !== val);
      }
    });
  });

  budgetPresets.forEach(bBtn => {
    bBtn.addEventListener('click', () => {
      budgetPresets.forEach(b => b.classList.remove('selected'));
      bBtn.classList.add('selected');
      selectedBudget = bBtn.textContent.trim();
    });
  });

  const contactForm = document.getElementById('contactForm');
  const sendViaWhatsAppBtn = document.getElementById('sendViaWhatsAppBtn');

  contactForm?.addEventListener('submit', (e) => {
    e.preventDefault();
    const name = document.getElementById('contactName')?.value.trim();
    const email = document.getElementById('contactEmail')?.value.trim();
    const phone = document.getElementById('contactPhone')?.value.trim();
    const message = document.getElementById('contactMessage')?.value.trim();

    if (!name || !email || !message) {
      showToast('⚠️ Please provide your name, email, and project message');
      return;
    }

    const submitBtn = contactForm.querySelector('button[type="submit"]');
    if (submitBtn) {
      const origText = submitBtn.innerHTML;
      submitBtn.innerHTML = 'Sending... ⏳';
      submitBtn.disabled = true;

      setTimeout(() => {
        submitBtn.innerHTML = 'Message Sent! ✅';
        showToast('🚀 Thank you! Glorifix Media Concept will reach out within 30 minutes.');
        contactForm.reset();
        setTimeout(() => {
          submitBtn.innerHTML = origText;
          submitBtn.disabled = false;
        }, 3000);
      }, 1000);
    }
  });

  sendViaWhatsAppBtn?.addEventListener('click', () => {
    const name = document.getElementById('contactName')?.value.trim() || 'A Potential Client';
    const email = document.getElementById('contactEmail')?.value.trim() || 'N/A';
    const phone = document.getElementById('contactPhone')?.value.trim() || 'N/A';
    const message = document.getElementById('contactMessage')?.value.trim() || 'Inquiring about your services';

    const text = `Hello Glorifix Media Concept!
• Client Name: ${name}
• Email: ${email}
• Phone: ${phone}
• Services of Interest: ${selectedServices.join(', ')}
• Estimated Budget: ${selectedBudget}
• Project Notes: ${message}`;

    const waUrl = `https://wa.me/2349050278999?text=${encodeURIComponent(text)}`;
    window.open(waUrl, '_blank');
  });

  // --- 8. FAQ Accordion ---
  const faqItems = document.querySelectorAll('.faq-item');
  faqItems.forEach(item => {
    const questionBtn = item.querySelector('.faq-question-btn');
    questionBtn?.addEventListener('click', () => {
      const isActive = item.classList.contains('active');
      faqItems.forEach(i => i.classList.remove('active'));
      if (!isActive) {
        item.classList.add('active');
      }
    });
  });

  // --- 9. Toast Notification Helper ---
  function showToast(msg) {
    let toast = document.getElementById('globalToast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'globalToast';
      toast.className = 'toast-container';
      document.body.appendChild(toast);
    }
    toast.textContent = msg;
    toast.classList.add('active');
    setTimeout(() => {
      toast.classList.remove('active');
    }, 4000);
  }

  // --- 10. Scroll Spy for Navigation ---
  const sections = document.querySelectorAll('section[id]');
  const navLinks = document.querySelectorAll('.nav-link');

  window.addEventListener('scroll', () => {
    let current = '';
    const scrollPos = window.pageYOffset + 120;

    sections.forEach(section => {
      const top = section.offsetTop;
      const height = section.offsetHeight;
      if (scrollPos >= top && scrollPos < top + height) {
        current = section.getAttribute('id');
      }
    });

    navLinks.forEach(link => {
      link.classList.remove('active');
      if (link.getAttribute('href') === `#${current}`) {
        link.classList.add('active');
      }
    });
  });
});
