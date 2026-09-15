/**
 * SEO Scoring Page JavaScript
 * Handles article searching, scoring calculation, results rendering, and UI animations
 */

let currentArticleId = null;
let currentScoreData = null;

document.addEventListener('DOMContentLoaded', () => {
    initArticleSearch();
    initUrlParams();

    // Note: User explicitly requested results NOT to show automatically on /scoring
    // The user must press the "Hitung Skor SEO Sekarang" button manually.
    if (window.IS_DETAIL_PAGE && window.INITIAL_SCORE_DATA) {
        renderScoreResults(window.INITIAL_SCORE_DATA);
    }
});


/**
 * Read query parameter from URL on page load
 */
function initUrlParams() {
    const urlParams = new URLSearchParams(window.location.search);
    const articleId = urlParams.get('article_id');
    if (articleId) {
        currentArticleId = parseInt(articleId);
        // If article data wasn't pre-rendered by server, fetch it
        if (!window.INITIAL_ARTICLE_LOADED) {
            selectArticleById(currentArticleId);
        }
    }
}

/**
 * Autocomplete / search input handler
 */
function initArticleSearch() {
    const searchInput = document.getElementById('articleSearchInput');
    const resultsContainer = document.getElementById('articleSearchResults');
    if (!searchInput || !resultsContainer) return;

    let debounceTimer;

    searchInput.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        const query = e.target.value.trim();

        if (query.length === 0) {
            fetchArticles('');
            return;
        }

        debounceTimer = setTimeout(() => {
            fetchArticles(query);
        }, 300);
    });

    searchInput.addEventListener('focus', () => {
        fetchArticles(searchInput.value.trim());
    });

    // Close dropdown on click outside
    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
            resultsContainer.classList.add('hidden');
        }
    });
}

/**
 * Fetch articles from backend for the dropdown
 */
async function fetchArticles(query = '') {
    const resultsContainer = document.getElementById('articleSearchResults');
    if (!resultsContainer) return;

    try {
        const res = await fetch(`/api/scoring/search-articles?q=${encodeURIComponent(query)}`);
        const json = await res.json();

        if (json.status === 'success' && json.data.length > 0) {
            renderArticleDropdown(json.data);
            resultsContainer.classList.remove('hidden');
        } else {
            resultsContainer.innerHTML = '<div class="p-3 text-xs text-muted-foreground text-center">Tidak ada artikel ditemukan.</div>';
            resultsContainer.classList.remove('hidden');
        }
    } catch (err) {
        console.error('Error fetching articles:', err);
    }
}

/**
 * Render article dropdown items
 */
function renderArticleDropdown(articles) {
    const container = document.getElementById('articleSearchResults');
    if (!container) return;

    container.innerHTML = articles.map(art => {
        const dateStr = art.articleDate ? new Date(art.articleDate).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '';
        const statusClass = art.status === 'Publish' ? 'bg-emerald-500/10 text-emerald-600' : 'bg-muted text-muted-foreground';
        return `
            <div class="article-search-item flex items-center justify-between gap-2" onclick="selectArticle(${art.id}, '${escapeQuotes(art.title)}')">
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-semibold text-foreground truncate">${escapeHtml(art.title)}</div>
                    <div class="text-[10px] text-muted-foreground flex items-center gap-2 mt-0.5">
                        <span class="font-mono">ID: ${art.id}</span>
                        <span>•</span>
                        <span>${dateStr}</span>
                    </div>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded ${statusClass} shrink-0 uppercase">${art.status}</span>
            </div>
        `;
    }).join('');
}

/**
 * Handle article selection from dropdown
 */
function selectArticle(id, title) {
    currentArticleId = id;
    const searchInput = document.getElementById('articleSearchInput');
    const resultsContainer = document.getElementById('articleSearchResults');

    if (searchInput) searchInput.value = `[ID: ${id}] ${title}`;
    if (resultsContainer) resultsContainer.classList.add('hidden');

    // Update URL without reload
    const url = new URL(window.location.href);
    url.searchParams.set('article_id', id);
    window.history.replaceState({}, '', url.toString());

    // Load article details
    selectArticleById(id);
}

/**
 * Load article details via API
 */
async function selectArticleById(id) {
    showLoadingSpinner(true);

    try {
        const res = await fetch(`/api/scoring/article/${id}`);
        const json = await res.json();

        if (json.status === 'success') {
            const data = json.data;
            renderSelectedArticleCard(data.article, data.keywords);

            // Per user requirement: Do NOT auto-show results when an article is selected!
            // The user must manually click the "Hitung Skor SEO Sekarang" button.
            hideScoreResults();

            // Enable calculate button
            const btn = document.getElementById('btnCalculateScore');
            if (btn) btn.disabled = false;
        } else {
            if (typeof showToast === 'function') {
                showToast(json.message || 'Gagal memuat artikel', 'destructive');
            }
        }
    } catch (err) {
        console.error('Error loading article:', err);
    } finally {
        showLoadingSpinner(false);
    }
}

/**
 * Render selected article preview card
 */
function renderSelectedArticleCard(article, keywords) {
    const card = document.getElementById('selectedArticleCard');
    if (!card) return;

    document.getElementById('articlePreviewTitle').textContent = article.headline || '-';
    document.getElementById('articlePreviewMeta').textContent = `ID: ${article.id} • ${article.status} • ${article.word_count || 0} kata`;
    document.getElementById('articlePreviewSummary').textContent = article.summary || '(Ringkasan/Meta Description belum diisi)';

    // Keywords / Tags
    const tagsContainer = document.getElementById('articlePreviewTags');
    if (tagsContainer) {
        if (keywords && keywords.length > 0) {
            tagsContainer.innerHTML = keywords.map(k => `
                <span class="inline-flex items-center rounded bg-primary/10 border border-primary/20 px-2 py-0.5 text-[11px] font-medium text-primary">
                    #${escapeHtml(k)}
                </span>
            `).join('');
        } else {
            tagsContainer.innerHTML = '<span class="text-xs text-amber-600 dark:text-amber-400 font-medium">⚠️ Belum ada kata kunci/tag terdaftar untuk artikel ini</span>';
        }
    }

    // Link to Editor
    const editLink = document.getElementById('linkOpenEditor');
    if (editLink) {
        editLink.href = `/post/edit/${article.id}`;
        editLink.classList.remove('hidden');
    }

    card.classList.remove('hidden');
}

/**
 * Trigger SEO scoring calculation
 */
async function calculateScore(skipAi = false) {
    if (!currentArticleId) {
        if (typeof showToast === 'function') {
            showToast('Pilih artikel terlebih dahulu.', 'warning');
        }
        return;
    }

    // Show Global AI Loading Modal (Identical to editor.js)
    if (typeof showAiLoading === 'function') {
        showAiLoading(skipAi 
            ? 'Sedang menghitung skor SEO teknis (rule-based)...' 
            : 'Menganalisis konten via Gemini AI & mengevaluasi 12 parameter SEO...'
        );
    }

    const btn = document.getElementById('btnCalculateScore');
    if (btn) btn.disabled = true;

    try {
        const res = await fetch('/api/scoring/calculate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                article_id: currentArticleId,
                skip_ai: skipAi
            })
        });

        const json = await res.json();

        if (json.status === 'success') {
            currentScoreData = json.data;
            renderScoreResults(json.data);

            if (typeof showToast === 'function') {
                showToast('Skor SEO berhasil dihitung!', 'success');
            }

            // Reload history list
            loadHistory(currentArticleId);
        } else {
            if (typeof showToast === 'function') {
                showToast(json.message || 'Gagal menghitung skor SEO', 'destructive');
            }
        }
    } catch (err) {
        console.error('Error calculating score:', err);
        if (typeof showToast === 'function') {
            showToast('Terjadi kesalahan saat memproses scoring.', 'destructive');
        }
    } finally {
        if (typeof hideAiLoading === 'function') {
            hideAiLoading();
        }
        if (btn) btn.disabled = false;
    }
}

/**
 * Render complete score results in UI
 */
function renderScoreResults(data) {
    const resultsContainer = document.getElementById('scoringResultsSection');
    if (!resultsContainer) return;

    // Normalizing parsed data
    const finalScore = parseFloat(data.final_score) || 0;
    const ruleScore = parseFloat(data.rule_score) || 0;
    const aiScore = data.ai_score !== null ? parseFloat(data.ai_score) : null;
    const category = data.score_category || 'Poor';

    // 1. Update Score Displays & Semicircle Gauges
    updateScoreGauge(finalScore, category, ruleScore, aiScore);

    // 2. Update AI Missing Note
    if (aiScore !== null) {
        document.getElementById('aiScoreBadge').classList.remove('opacity-50');
        document.getElementById('aiScoreMissingNote').classList.add('hidden');
    } else {
        document.getElementById('aiScoreBadge').classList.add('opacity-50');
        document.getElementById('aiScoreMissingNote').classList.remove('hidden');
    }

    // 3. Render 12 Rule Parameters
    renderRuleParameters(data.details || data.rule_details || []);

    // 4. Render AI Feedback
    renderAiFeedback(data.ai_feedback, data.ai_error);

    // 5. Render Recommendations
    renderRecommendations(data.recommendations || []);

    resultsContainer.classList.remove('hidden');
    resultsContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

/**
 * Update Full Circle & Semicircle Score Gauge Animation & Category Label for Overall, Rule, and AI
 */
function updateScoreGauge(score, category, ruleScore = null, aiScore = null) {
    // 1. Overall Score Gauge (1 Full Circle)
    const scoreText = document.getElementById('gaugeScoreText');
    const categoryLabel = document.getElementById('gaugeCategoryLabel');
    const fullCircleBar = document.getElementById('scoreCircleBar');

    const roundedScore = Math.round(score);
    if (scoreText) scoreText.textContent = `${roundedScore}%`;

    const colorConfig = getScoreColorAndLabel(score);

    if (categoryLabel) {
        categoryLabel.textContent = colorConfig.label;
        categoryLabel.className = `inline-flex items-center px-3 py-1 rounded-full text-xs font-bold ${colorConfig.badgeClass}`;
    }

    if (fullCircleBar) {
        animateFullCircle(fullCircleBar, score, colorConfig.color);
    }

    // 2. Rule Score Semicircle Gauge
    if (ruleScore !== null) {
        const ruleValEl = document.getElementById('ruleScoreVal');
        const ruleBar = document.getElementById('ruleSemicircleBar');
        const roundedRule = Math.round(ruleScore);
        if (ruleValEl) ruleValEl.textContent = `${roundedRule}%`;
        if (ruleBar) {
            const ruleColorConfig = getScoreColorAndLabel(ruleScore);
            animateSemicircle(ruleBar, ruleScore, ruleColorConfig.color);
        }
    }

    // 3. AI Score Semicircle Gauge
    const aiValEl = document.getElementById('aiScoreVal');
    const aiBar = document.getElementById('aiSemicircleBar');
    if (aiScore !== null) {
        const roundedAi = Math.round(aiScore);
        if (aiValEl) aiValEl.textContent = `${roundedAi}%`;
        if (aiBar) {
            const aiColorConfig = getScoreColorAndLabel(aiScore);
            animateSemicircle(aiBar, aiScore, aiColorConfig.color);
        }
    } else {
        if (aiValEl) aiValEl.textContent = 'N/A';
        if (aiBar) {
            animateSemicircle(aiBar, 0, '#94a3b8');
        }
    }
}

/**
 * Animate Full Circle SVG (cx=50, cy=50, r=40, circumference = 2 * PI * 40 ~= 251.32)
 */
function animateFullCircle(element, score, color) {
    const circumference = 2 * Math.PI * 40; // ~251.32
    const clampedScore = Math.min(100, Math.max(0, score));
    const offset = circumference - (clampedScore / 100) * circumference;

    element.style.strokeDasharray = circumference;
    element.style.strokeDashoffset = offset;
    element.style.stroke = color;
}

/**
 * Animate SVG semicircle path with stroke-dashoffset (arc length ~ 119.38)
 */
function animateSemicircle(element, score, color) {
    const pathTotalLength = element.getTotalLength ? element.getTotalLength() : 120;
    const clampedScore = Math.min(100, Math.max(0, score));
    const offset = pathTotalLength - (clampedScore / 100) * pathTotalLength;

    element.style.strokeDasharray = pathTotalLength;
    element.style.strokeDashoffset = offset;
    element.style.stroke = color;
}

/**
 * Helper to get color code, label, and CSS badge class based on score value
 */
function getScoreColorAndLabel(score) {
    if (score >= 85) {
        return {
            color: '#10b981', // Emerald
            label: 'Sangat Siap (Siap Rilis)',
            badgeClass: 'badge-score-excellent'
        };
    } else if (score >= 70) {
        return {
            color: '#6366f1', // Indigo/Blue
            label: 'Cukup Baik (Siap Rilis)',
            badgeClass: 'badge-score-good'
        };
    } else if (score >= 50) {
        return {
            color: '#f59e0b', // Amber
            label: 'Perlu Perbaikan',
            badgeClass: 'badge-score-warning'
        };
    } else {
        return {
            color: '#ef4444', // Red
            label: 'Belum Siap Publikasi',
            badgeClass: 'badge-score-poor'
        };
    }
}

/**
 * Render 12 Rule-Based Parameters table/cards
 */
function renderRuleParameters(params) {
    const container = document.getElementById('ruleParametersList');
    if (!container) return;

    if (!Array.isArray(params) || params.length === 0) {
        container.innerHTML = '<div class="p-3 text-xs text-muted-foreground text-center">Data parameter tidak tersedia.</div>';
        return;
    }

    container.innerHTML = params.map(p => {
        let statusBadge = '';
        let iconName = 'check-circle';

        if (p.status === 'pass') {
            statusBadge = '<span class="status-pill-pass px-2 py-0.5 rounded text-[10px] font-bold uppercase">Lulus</span>';
            iconName = 'check-circle-2';
        } else if (p.status === 'warning') {
            statusBadge = '<span class="status-pill-warning px-2 py-0.5 rounded text-[10px] font-bold uppercase">Peringatan</span>';
            iconName = 'alert-triangle';
        } else {
            statusBadge = '<span class="status-pill-fail px-2 py-0.5 rounded text-[10px] font-bold uppercase">Perlu Tindakan</span>';
            iconName = 'x-circle';
        }

        return `
            <div class="p-3.5 rounded-lg border border-border bg-card hover:bg-muted/30 transition-colors space-y-1.5">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <i data-lucide="${iconName}" class="size-4 shrink-0 ${p.status === 'pass' ? 'text-emerald-600' : (p.status === 'warning' ? 'text-amber-500' : 'text-red-500')}"></i>
                        <span class="text-xs font-bold text-foreground">${escapeHtml(p.title)}</span>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="text-[10px] font-mono text-muted-foreground">Bobot: ${p.weight}%</span>
                        ${statusBadge}
                    </div>
                </div>
                <p class="text-xs text-muted-foreground pl-6">${escapeHtml(p.message)}</p>
                <div class="flex items-center justify-between text-[10px] text-muted-foreground pl-6 pt-1 border-t border-border/50">
                    <span>Nilai saat ini: <strong class="text-foreground">${escapeHtml(p.current_val || '-')}</strong></span>
                    <span>Target ideal: <span class="font-medium">${escapeHtml(p.ideal_val || '-')}</span></span>
                </div>
            </div>
        `;
    }).join('');

    // Re-initialize Lucide icons
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

/**
 * Render AI Feedback (4 criteria cards + reasonings)
 */
function renderAiFeedback(aiData, aiError) {
    const container = document.getElementById('aiFeedbackSection');
    if (!container) return;

    if (aiError) {
        container.innerHTML = `
            <div class="p-4 rounded-lg bg-amber-500/10 border border-amber-500/30 text-xs space-y-2">
                <div class="flex items-center gap-2 text-amber-600 font-bold">
                    <i data-lucide="alert-circle" class="size-4"></i>
                    <span>Analisis AI Tidak Lengkap</span>
                </div>
                <p class="text-muted-foreground">${escapeHtml(aiError)}</p>
                <button type="button" onclick="calculateScore(false)" class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline">
                    <i data-lucide="rotate-cw" class="size-3"></i>
                    <span>Coba Ulang Penilaian AI (Manual Retry)</span>
                </button>
            </div>
        `;
        if (typeof lucide !== 'undefined') lucide.createIcons();
        return;
    }

    if (!aiData) {
        container.innerHTML = `
            <div class="p-4 rounded-lg border border-border bg-muted/20 text-center text-xs text-muted-foreground space-y-2">
                <p>Penilaian AI belum dijalankan atau dilewati.</p>
                <button type="button" onclick="calculateScore(false)" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md bg-primary text-primary-foreground text-xs font-bold hover:bg-primary/90">
                    <i data-lucide="sparkles" class="size-3.5"></i>
                    <span>Jalankan Penilaian AI Sekarang</span>
                </button>
            </div>
        `;
        if (typeof lucide !== 'undefined') lucide.createIcons();
        return;
    }

    const criteria = [
        { key: 'search_intent', label: 'Search Intent Relevancy', weight: '30%', icon: 'target' },
        { key: 'content_depth', label: 'Content Depth & Substansi', weight: '30%', icon: 'file-text' },
        { key: 'click_readiness', label: 'Click-Readiness & Daya Tarik', weight: '20%', icon: 'mouse-pointer-click' },
        { key: 'keyword_naturalness', label: 'Keyword Naturalness & Distribusi', weight: '20%', icon: 'sparkles' }
    ];

    container.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            ${criteria.map(c => {
                const item = aiData[c.key] || { score: 0, reasoning: '-' };
                const scoreNum = Math.round(item.score);
                const scoreColor = scoreNum >= 75 ? 'text-emerald-600' : (scoreNum >= 50 ? 'text-amber-500' : 'text-red-500');

                return `
                    <div class="p-3.5 rounded-lg border border-border bg-card space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <i data-lucide="${c.icon}" class="size-3.5 text-primary"></i>
                                <span class="text-xs font-bold text-foreground">${c.label}</span>
                            </div>
                            <span class="text-sm font-bold font-mono ${scoreColor}">${scoreNum}/100</span>
                        </div>
                        <p class="text-[11px] text-muted-foreground leading-relaxed bg-muted/30 p-2 rounded">
                            ${escapeHtml(item.reasoning)}
                        </p>
                    </div>
                `;
            }).join('')}
        </div>
    `;

    if (typeof lucide !== 'undefined') lucide.createIcons();
}

/**
 * Render consolidated recommendations
 */
function renderRecommendations(recs) {
    const container = document.getElementById('recommendationsList');
    if (!container) return;

    if (!Array.isArray(recs) || recs.length === 0) {
        container.innerHTML = '<div class="p-3 text-xs text-emerald-600 font-medium">✨ Luar biasa! Tidak ada rekomendasi perbaikan teknis atau editorial yang mendesak.</div>';
        return;
    }

    container.innerHTML = recs.map(r => {
        const isTech = r.type === 'technical';
        return `
            <li class="flex items-start gap-2.5 p-2.5 rounded-lg border border-border bg-card text-xs">
                <i data-lucide="${isTech ? 'wrench' : 'sparkles'}" class="size-4 shrink-0 mt-0.5 ${isTech ? 'text-primary' : 'text-amber-500'}"></i>
                <div class="min-w-0 flex-1">
                    <strong class="text-foreground">${escapeHtml(r.title || (isTech ? 'Teknis' : 'Editorial'))}:</strong>
                    <span class="text-muted-foreground ml-1">${escapeHtml(r.message || '')}</span>
                </div>
            </li>
        `;
    }).join('');

    if (typeof lucide !== 'undefined') lucide.createIcons();
}

/**
 * Load history list
 */
async function loadHistory(articleId) {
    const container = document.getElementById('scoreHistoryList');
    if (!container) return;

    try {
        const res = await fetch(`/api/scoring/history/${articleId}`);
        const json = await res.json();

        if (json.status === 'success' && json.data.length > 0) {
            container.innerHTML = json.data.map(h => {
                const dateStr = h.created_at ? new Date(h.created_at).toLocaleDateString('id-ID', {
                    day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
                }) : '-';
                return `
                    <div class="flex items-center justify-between p-2 rounded hover:bg-muted/40 transition-colors text-xs">
                        <div class="flex items-center gap-2">
                            <span class="font-bold font-mono text-foreground">${Math.round(h.final_score)}/100</span>
                            <span class="text-muted-foreground font-mono text-[10px]">${dateStr}</span>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-muted uppercase">${escapeHtml(h.score_category)}</span>
                    </div>
                `;
            }).join('');
        }
    } catch (err) {
        console.error('Error loading history:', err);
    }
}

function hideScoreResults() {
    const resultsContainer = document.getElementById('scoringResultsSection');
    if (resultsContainer) resultsContainer.classList.add('hidden');
}

function showLoadingSpinner(show) {
    const spinner = document.getElementById('articleLoadingSpinner');
    if (spinner) {
        if (show) spinner.classList.remove('hidden');
        else spinner.classList.add('hidden');
    }
}

// Utility escaping helpers
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function escapeQuotes(str) {
    if (!str) return '';
    return String(str).replace(/'/g, "\\'").replace(/"/g, '&quot;');
}
