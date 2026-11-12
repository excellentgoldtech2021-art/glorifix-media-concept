/**
 * Glorifix Media Concept Limited - Interactive Customer Voice Agent
 * Powered by Web Speech Synthesis & Recognition API
 */

(function() {
  'use strict';

  // Knowledge base of Glorifix services & accurate pricing
  const KB = [
    {
      keywords: ['cac', 'business name', 'register business', 'registration', 'name registration'],
      answer: "CAC Business Name registration is ₦35,000. It includes official CAC Certificate, Status Report, and name reservation in 3 to 5 working days.",
      whatsappText: "Hello Glorifix! I want to register a Business Name (₦35,000)"
    },
    {
      keywords: ['trustee', 'ngo', 'church', 'foundation', 'incorporated trustee', '150'],
      answer: "CAC Incorporated Trustee and NGO Foundation registration is ₦150,000. This includes constitution drafting, full legal documentation, and mandatory newspaper publication in 7 to 14 days.",
      whatsappText: "Hello Glorifix! I want to register an Incorporated Trustee/NGO (₦150,000)"
    },
    {
      keywords: ['limited', 'company', 'llc', 'ltd', 'share'],
      answer: "Limited Liability Company (LLC) registration is ₦75,000 for up to 2 directors. It includes CAC Certificate, MEMART, and Status Report in 5 to 7 days.",
      whatsappText: "Hello Glorifix! I want to register a Limited Company LLC (₦75,000)"
    },
    {
      keywords: ['website', 'web design', 'web development', 'site', 'developer', 'portal'],
      answer: "Our website packages start at ₦100,000 for Basic, ₦150,000 for Business, and ₦300,000 for Premium. All include 1-year free hosting, domain, and payment on delivery is available!",
      whatsappText: "Hello Glorifix! I am interested in Website Design"
    },
    {
      keywords: ['ad', 'ads', 'meta', 'facebook', 'instagram', 'media buying', 'campaign'],
      answer: "Our Meta Ads management packages start at ₦60,000 for 7 days, ₦120,000 for 14 days, and ₦180,000 for a full 30-day growth campaign with daily optimization.",
      whatsappText: "Hello Glorifix! I want to run Facebook and Instagram Ads"
    },
    {
      keywords: ['social media', 'social', 'starter', 'growth', 'page management', 'instagram management', 'posts'],
      answer: "Our monthly Social Media Management packages are: Starter Package at ₦50,000 for 2 platforms and 12 posts, Growth Package at ₦120,000 for 3 platforms and 20 posts, and Premium Package at ₦240,000 for 5 platforms with full digital dominance.",
      whatsappText: "Hello Glorifix! I am interested in Social Media Management packages"
    },
    {
      keywords: ['phone', 'call', 'contact', 'whatsapp', 'number', 'address', 'office', 'where'],
      answer: "You can reach Glorifix directly on 09050278999 or email glorifixmediaconcept@gmail.com. Our office is located Opposite General Hospital, Behind G-Delight Hotel, Ikare-Akoko, with client support in Abuja.",
      whatsappText: "Hello Glorifix! I would like to speak with a consultant"
    },
    {
      keywords: ['ezekiel', 'founder', 'ceo', 'owner'],
      answer: "Glorifix Media Concept Limited is led by Ezekiel, specializing in strategic digital marketing, brand identity, and CAC legal business formation.",
      whatsappText: "Hello Ezekiel! I would like to speak directly with you"
    },
    {
      keywords: ['pricing', 'price', 'cost', 'how much', 'packages'],
      answer: "Our core services: CAC Business Name is ₦35,000, Limited Company is ₦75,000, NGO Trustee is ₦150,000, Social Media packages are ₦50,000 (Starter), ₦120,000 (Growth), ₦240,000 (Premium), Website design starts from ₦100,000, and Meta Ads from ₦60,000.",
      whatsappText: "Hello Glorifix! Please send me your complete pricing brochure"
    }
  ];

  let speechEnabled = true;
  let recognition = null;
  let isListening = false;

  // Initialize Speech Recognition if supported
  if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
    const SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;
    recognition = new SpeechRec();
    recognition.continuous = false;
    recognition.interimResults = false;
    recognition.lang = 'en-US';
  }

  function initVoiceAgent() {
    // 1. Inject Stylesheet link if not present
    if (!document.querySelector('link[href*="voice-agent.css"]')) {
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = 'css/voice-agent.css';
      document.head.appendChild(link);
    }

    // 2. Inject HTML Elements
    const html = `
      <!-- Floating AI Voice Agent Button -->
      <button type="button" class="voice-agent-float" id="va-launcher" aria-label="Open AI Voice Assistant">
        <div class="voice-agent-badge">
          <span class="voice-badge-dot"></span>
          <span>Voice Agent</span>
        </div>
        <svg viewBox="0 0 24 24"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/><path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/></svg>
      </button>

      <!-- Voice Agent Interactive Modal -->
      <div class="voice-agent-modal" id="va-modal" role="dialog" aria-label="Glorifix Customer Voice Assistant">
        <div class="va-header">
          <div class="va-header-info">
            <div class="va-avatar">G</div>
            <div class="va-title-box">
              <h4>Glorifix AI Assistant</h4>
              <span>
                <div class="va-equalizer" id="va-eq">
                  <div class="va-eq-bar"></div>
                  <div class="va-eq-bar"></div>
                  <div class="va-eq-bar"></div>
                  <div class="va-eq-bar"></div>
                </div>
                Online &bull; Ready to speak
              </span>
            </div>
          </div>
          <div class="va-header-actions">
            <button type="button" class="va-btn-icon" id="va-toggle-speech" title="Mute/Unmute voice speech" aria-label="Toggle voice output">
              <svg id="va-speech-icon" width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/></svg>
            </button>
            <button type="button" class="va-btn-icon" id="va-close-modal" aria-label="Close Voice Assistant">
              <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
        </div>

        <div class="va-messages" id="va-messages">
          <div class="va-msg va-msg-agent">
            Hello! I am your Glorifix AI Voice Assistant. Ask me anything about our CAC registration, website design, or advertising packages &mdash; or tap the microphone to speak!
          </div>
        </div>

        <div class="va-chips-wrapper">
          <button type="button" class="va-chip" data-q="How much is CAC Business Name?">💰 CAC Business Name (₦35k)</button>
          <button type="button" class="va-chip" data-q="How much is CAC NGO Trustee?">🏛️ CAC NGO Trustee (₦150k)</button>
          <button type="button" class="va-chip" data-q="Tell me website prices">🌐 Website Design</button>
          <button type="button" class="va-chip" data-q="Meta Ads packages">📈 Meta Ads Pricing</button>
        </div>

        <div class="va-input-area">
          <button type="button" class="va-mic-btn" id="va-mic-btn" title="Tap to speak" aria-label="Voice input">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/><path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/></svg>
          </button>
          <input type="text" class="va-input" id="va-input" placeholder="Type or tap mic to talk..." autocomplete="off">
          <button type="button" class="va-btn-icon" id="va-send-btn" aria-label="Send message">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
          </button>
        </div>
      </div>
    `;

    const container = document.createElement('div');
    container.id = 'glorifix-voice-agent-container';
    container.innerHTML = html;
    document.body.appendChild(container);

    attachEvents();
  }

  function attachEvents() {
    const launcher = document.getElementById('va-launcher');
    const modal = document.getElementById('va-modal');
    const closeBtn = document.getElementById('va-close-modal');
    const toggleSpeechBtn = document.getElementById('va-toggle-speech');
    const micBtn = document.getElementById('va-mic-btn');
    const input = document.getElementById('va-input');
    const sendBtn = document.getElementById('va-send-btn');
    const chips = document.querySelectorAll('.va-chip');

    // Toggle Modal
    launcher.addEventListener('click', () => {
      modal.classList.toggle('is-open');
      if (modal.classList.contains('is-open')) {
        speakText("Hello! How can Glorifix Media Concept help your business grow today?");
        setTimeout(() => input.focus(), 300);
      } else {
        stopSpeech();
      }
    });

    closeBtn.addEventListener('click', () => {
      modal.classList.remove('is-open');
      stopSpeech();
    });

    // Toggle Voice Output
    toggleSpeechBtn.addEventListener('click', () => {
      speechEnabled = !speechEnabled;
      const icon = document.getElementById('va-speech-icon');
      if (speechEnabled) {
        icon.innerHTML = '<path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>';
        showAgentMessage("Voice response turned on.");
      } else {
        icon.innerHTML = '<path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73 4.27 3zM12 4L9.91 6.09 12 8.18V4z"/>';
        stopSpeech();
        showAgentMessage("Voice response muted.");
      }
    });

    // Send on click or Enter
    sendBtn.addEventListener('click', handleUserSubmit);
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') handleUserSubmit();
    });

    // Suggestion chips
    chips.forEach(chip => {
      chip.addEventListener('click', () => {
        const q = chip.getAttribute('data-q');
        processUserQuery(q);
      });
    });

    // Speech Recognition
    if (recognition) {
      recognition.onstart = () => {
        isListening = true;
        micBtn.classList.add('listening');
        input.placeholder = "Listening... Speak now...";
      };

      recognition.onresult = (event) => {
        const text = event.results[0][0].transcript;
        processUserQuery(text);
      };

      recognition.onerror = () => {
        isListening = false;
        micBtn.classList.remove('listening');
        input.placeholder = "Type or tap mic to talk...";
      };

      recognition.onend = () => {
        isListening = false;
        micBtn.classList.remove('listening');
        input.placeholder = "Type or tap mic to talk...";
      };

      micBtn.addEventListener('click', () => {
        if (isListening) {
          recognition.stop();
        } else {
          stopSpeech();
          try {
            recognition.start();
          } catch(e) {}
        }
      });
    } else {
      micBtn.style.opacity = '0.7';
      micBtn.title = 'Speech recognition not available on this browser; type below!';
    }
  }

  function handleUserSubmit() {
    const input = document.getElementById('va-input');
    const val = input.value.trim();
    if (val) {
      processUserQuery(val);
      input.value = '';
    }
  }

  function processUserQuery(text) {
    showUserMessage(text);
    const answerObj = findBestAnswer(text);
    setTimeout(() => {
      showAgentMessage(answerObj.answer, answerObj.whatsappText);
      speakText(answerObj.answer);
    }, 400);
  }

  function findBestAnswer(query) {
    const q = query.toLowerCase();
    for (const item of KB) {
      for (const kw of item.keywords) {
        if (q.includes(kw)) {
          return item;
        }
      }
    }
    return {
      answer: "I can help connect you with our lead consultant Ezekiel on WhatsApp or phone at 09050278999 for full custom strategy and quote.",
      whatsappText: "Hello Glorifix! I have an inquiry: " + query
    };
  }

  function showUserMessage(text) {
    const msgBox = document.getElementById('va-messages');
    const div = document.createElement('div');
    div.className = 'va-msg va-msg-user';
    div.textContent = text;
    msgBox.appendChild(div);
    msgBox.scrollTop = msgBox.scrollHeight;
  }

  function showAgentMessage(text, whatsappText) {
    const msgBox = document.getElementById('va-messages');
    const div = document.createElement('div');
    div.className = 'va-msg va-msg-agent';
    
    let html = `<div>${text}</div>`;
    if (whatsappText) {
      const waUrl = 'https://wa.me/2349050278999?text=' + encodeURIComponent(whatsappText);
      html += `<div style="margin-top: 8px;">
        <a href="${waUrl}" target="_blank" style="display: inline-flex; align-items: center; gap: 5px; color: #10b981; font-weight: 700; font-size: 0.8rem; text-decoration: underline;">
          💬 Chat on WhatsApp &rarr;
        </a>
      </div>`;
    }
    div.innerHTML = html;
    msgBox.appendChild(div);
    msgBox.scrollTop = msgBox.scrollHeight;
  }

  function speakText(text) {
    if (!speechEnabled || !('speechSynthesis' in window)) return;
    stopSpeech();

    const eq = document.getElementById('va-eq');
    const cleanText = text.replace(/₦/g, ' Naira ').replace(/&bull;/g, '').replace(/&mdash;/g, '');
    const utter = new SpeechSynthesisUtterance(cleanText);
    utter.rate = 1.0;
    utter.pitch = 1.0;

    utter.onstart = () => {
      if (eq) eq.classList.add('active');
    };
    utter.onend = () => {
      if (eq) eq.classList.remove('active');
    };
    utter.onerror = () => {
      if (eq) eq.classList.remove('active');
    };

    window.speechSynthesis.speak(utter);
  }

  function stopSpeech() {
    if ('speechSynthesis' in window) {
      window.speechSynthesis.cancel();
    }
    const eq = document.getElementById('va-eq');
    if (eq) eq.classList.remove('active');
  }

  // Load when document is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initVoiceAgent);
  } else {
    initVoiceAgent();
  }
})();
