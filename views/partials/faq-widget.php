<?php
/**
 * FAQ Smart Assistant Widget
 * Embed anywhere with: require VIEW_PATH . '/partials/faq-widget.php';
 * The widget renders a search input and handles debounced AJAX FAQ lookup.
 */
?>
<!-- FAQ Widget Container -->
<div id="faqWidget" class="relative mb-2" style="display:none">
  <div class="relative">
    <input type="text"
           id="faqSearchInput"
           placeholder="<?= e(__t('faq_search_placeholder')) ?>"
           autocomplete="off"
           class="w-full border border-indigo-300 rounded-xl px-4 py-3 pr-10 text-sm shadow-sm
                  focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none bg-indigo-50
                  placeholder-slate-400">
    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-indigo-400">
      <i id="faqSearchIcon" class="fas fa-magnifying-glass"></i>
    </span>
  </div>

  <!-- Suggestion strip -->
  <div id="faqSuggestions" class="hidden mt-2 bg-white border border-indigo-200 rounded-xl shadow-lg overflow-hidden z-20">
    <div class="bg-indigo-50 px-4 py-2 text-xs font-semibold text-indigo-700 flex items-center gap-2">
      <i class="fas fa-lightbulb text-indigo-500"></i>
      <?= __t('faq_modal_title') ?>
    </div>
    <ul id="faqList" class="divide-y divide-slate-100 max-h-72 overflow-y-auto"></ul>
    <div class="px-4 py-2 border-t border-slate-100 flex items-center justify-between bg-slate-50 text-xs">
      <span id="faqResultLabel" class="text-slate-400"></span>
      <button id="faqDismissBtn"
              class="text-slate-500 hover:text-slate-800 font-medium px-3 py-1 rounded-lg hover:bg-slate-200 transition">
        <?= __t('faq_need_help_btn') ?>
      </button>
    </div>
  </div>
</div>

<!-- FAQ Answer Modal -->
<div id="faqModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col">
    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100">
      <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
        <i class="fas fa-lightbulb text-indigo-600 text-sm"></i>
      </div>
      <h3 class="font-bold text-slate-800 flex-1"><?= __t('faq_modal_title') ?></h3>
      <button id="faqModalClose" class="text-slate-400 hover:text-slate-700">
        <i class="fas fa-xmark text-lg"></i>
      </button>
    </div>

    <div class="px-6 py-4 flex-1 overflow-y-auto">
      <p id="faqModalQuestion" class="font-semibold text-slate-800 mb-3 text-base"></p>
      <div id="faqModalAnswer" class="text-sm text-slate-600 leading-relaxed whitespace-pre-wrap"></div>
      <p id="faqModalCategory" class="mt-3 text-xs text-slate-400 italic"></p>
    </div>

    <div class="px-6 py-4 border-t border-slate-100 flex items-center gap-3">
      <button id="faqSolvedBtn"
              class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold py-2.5 px-4 rounded-xl text-sm transition">
        <i class="fas fa-circle-check mr-1"></i>
        <?= __t('faq_solved_btn') ?>
      </button>
      <button id="faqNeedHelpBtn"
              class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2.5 px-4 rounded-xl text-sm transition">
        <?= __t('faq_need_help_btn') ?>
      </button>
    </div>

    <!-- Toast inside modal -->
    <div id="faqSolvedToast" class="hidden mx-6 mb-4 bg-emerald-50 border border-emerald-200 rounded-lg px-4 py-2 text-sm text-emerald-700">
      <i class="fas fa-circle-check mr-1"></i>
      <?= __t('faq_deflect_success') ?>
    </div>
  </div>
</div>

<script>
(function () {
  const DEFLECT_URL = '/api/faq/deflect';
  const SEARCH_URL  = '/api/faq/search';
  const LANG        = '<?= e(Lang::current()) ?>';

  let debounceTimer    = null;
  let activeFaqId      = null;
  let onNeedHelpCb     = null;   // called when user clicks "still need help"
  let dismissedQueries = new Set();

  // ── Expose init function for the ticket form ──────────────────────────────
  window.FaqWidget = {
    /**
     * Activate the widget, wiring its "still need help" action
     * to the provided callback (e.g., "advance to step 2").
     */
    init(onNeedHelp) {
      onNeedHelpCb = onNeedHelp;
      document.getElementById('faqWidget').style.display = '';
    },

    /**
     * Trigger a search programmatically (e.g., from description field).
     */
    search(query) {
      triggerSearch(query);
    },
  };

  // ── Internal ──────────────────────────────────────────────────────────────

  function triggerSearch(query) {
    query = query.trim();
    if (query.length < 3 || dismissedQueries.has(query)) {
      hideSuggestions();
      return;
    }

    setIcon('fa-spinner fa-spin');

    fetch(`${SEARCH_URL}?q=${encodeURIComponent(query)}&lang=${LANG}`)
      .then(r => r.json())
      .then(data => {
        setIcon('fa-magnifying-glass');
        if (data.found && data.faqs.length > 0) {
          renderSuggestions(data.faqs);
        } else {
          hideSuggestions();
        }
      })
      .catch(() => setIcon('fa-magnifying-glass'));
  }

  function renderSuggestions(faqs) {
    const list  = document.getElementById('faqList');
    const label = document.getElementById('faqResultLabel');
    list.innerHTML = '';
    label.textContent = `${faqs.length} <?= __t('faq_result_count') ?>`.replace(':count', faqs.length);

    faqs.forEach(faq => {
      const li  = document.createElement('li');
      li.className = 'px-4 py-3 hover:bg-indigo-50 cursor-pointer transition-colors';
      li.innerHTML = `
        <p class="text-sm font-medium text-slate-800 line-clamp-1">${escapeHtml(faq.question)}</p>
        <p class="text-xs text-slate-400 mt-0.5 line-clamp-1">${escapeHtml(faq.answer)}</p>
      `;
      li.addEventListener('click', () => openModal(faq));
      list.appendChild(li);
    });

    document.getElementById('faqSuggestions').classList.remove('hidden');
  }

  function hideSuggestions() {
    document.getElementById('faqSuggestions').classList.add('hidden');
  }

  function openModal(faq) {
    activeFaqId = faq.id;
    document.getElementById('faqModalQuestion').textContent = faq.question;
    document.getElementById('faqModalAnswer').textContent   = faq.answer;
    document.getElementById('faqModalCategory').textContent = faq.category ? `Category: ${faq.category}` : '';
    document.getElementById('faqSolvedToast').classList.add('hidden');
    document.getElementById('faqModal').classList.remove('hidden');
  }

  function closeModal() {
    document.getElementById('faqModal').classList.add('hidden');
    activeFaqId = null;
  }

  function setIcon(cls) {
    const el = document.getElementById('faqSearchIcon');
    el.className = 'fas ' + cls;
  }

  function escapeHtml(str) {
    return (str || '').replace(/[&<>"']/g, c => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
    }[c]));
  }

  // ── Event wiring ──────────────────────────────────────────────────────────

  document.getElementById('faqSearchInput')?.addEventListener('input', e => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => triggerSearch(e.target.value), 400);
  });

  document.getElementById('faqModalClose')?.addEventListener('click', closeModal);

  document.getElementById('faqDismissBtn')?.addEventListener('click', () => {
    const q = document.getElementById('faqSearchInput').value.trim();
    dismissedQueries.add(q);
    hideSuggestions();
    if (typeof onNeedHelpCb === 'function') onNeedHelpCb();
  });

  document.getElementById('faqNeedHelpBtn')?.addEventListener('click', () => {
    closeModal();
    if (typeof onNeedHelpCb === 'function') onNeedHelpCb();
  });

  document.getElementById('faqSolvedBtn')?.addEventListener('click', () => {
    if (!activeFaqId) return;
    const btn = document.getElementById('faqSolvedBtn');
    btn.disabled = true;

    const fd = new FormData();
    fd.append('faq_id', activeFaqId);

    fetch(DEFLECT_URL, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        document.getElementById('faqSolvedToast').classList.remove('hidden');
        document.getElementById('faqSolvedBtn').classList.add('hidden');
        document.getElementById('faqNeedHelpBtn').classList.add('hidden');
        setTimeout(closeModal, 2000);
      })
      .catch(() => { btn.disabled = false; });
  });

  // Close modal on backdrop click
  document.getElementById('faqModal')?.addEventListener('click', e => {
    if (e.target === document.getElementById('faqModal')) closeModal();
  });

  // Close suggestions on outside click
  document.addEventListener('click', e => {
    if (!document.getElementById('faqWidget')?.contains(e.target)) {
      hideSuggestions();
    }
  });
})();
</script>
