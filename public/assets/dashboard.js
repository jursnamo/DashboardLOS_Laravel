    let raw = [], mode = 'date', charts = {}, globalOutliers = [], statusOutliers = {};
    let q1Apps = [], medianApps = [], q3Apps = [], e2eData = [];
    let trendData = {}, branchData = {}, tatDistributionData = {};
    let currentDetailType = '', currentDetailData = [], currentDetailTitle = '';
    let globalStats = {}; // Store global stats for single app analysis
    let appsWithPositiveLimit = []; // Store apps with positive limit
    let statusStats = {}; // Store stats for each status
    let appFlowEvents = {};
    let statusDurationBuckets = {};
    let bottleneckMetrics = [];
    let currentBottleneckStatus = '';
    let currentBottleneckRows = [];
    let e2eById = {};
    let simReductionByStatus = {};
    let simStatusOrder = [];
    let simImpactByStatus = {};
    let baseE2EData = [];
    let baseStatusAgg = {};
    let baseAppsById = {};
    let baseGlobalStats = {};
    let baseAppFlowEvents = {};
    let simLastSnapshot = null;
    const GEMINI_API_KEY = ''; // Isi sendiri secret key Gemini Anda
    const GEMINI_MODEL = 'gemini-2.0-flash';
    let currentUploadedFileName = '';
    let latestImportMeta = null;
    
    // PERUBAHAN: Update bucket distribusi TAT - <7 days bukan <8 days
    const tatBuckets = [
        { label: '<7 days', min: 0, max: 6.999 },
        { label: '<14 days', min: 7, max: 13.999 },
        { label: '<30 days', min: 14, max: 29.999 },
        { label: '<60 days', min: 30, max: 59.999 },
        { label: '<90 days', min: 60, max: 89.999 },
        { label: '>=90 days', min: 90, max: Infinity }
    ];

    // NEW: Loan size buckets (dalam juta)
    const loanSizeBuckets = [
        { label: '0-1M', min: 0, max: 1e9 },
        { label: '1-2M', min: 1e9, max: 2e9 },
        { label: '3-3M', min: 2e9, max: 3e9 },
        { label: '4-4M', min: 3e9, max: 4e9 },
        { label: '5-5M', min: 4e9, max: 5e9 },
        { label: '6-6M', min: 5e9, max: 6e9 },
        { label: '7-7M', min: 6e9, max: 7e9 },
        { label: '8-8M', min: 7e9, max: 8e9 },
        { label: '9-9M', min: 8e9, max: 9e9 },
        { label: '9-10M', min:9e9, max: 10e9 },
        { label: '10-11M', min: 10e9, max: 11e9 },
        { label: '11-12M', min: 11e9, max: 12e9 },
        { label: '12-13M', min: 12e9, max: 13e9 },
        { label: '13-14M', min: 13e9, max: 14e9 },
        { label: '14-15M', min: 14e9, max: 15e9 },
        { label: '15-16M', min: 15e9, max: 16e9 },
        { label: '16-17M', min: 16e9, max: 17e9 },
        { label: '17-18M', min: 17e9, max: 18e9 },
        { label: '18-19M', min: 18e9, max: 19e9 },
        { label: '19-20M', min: 19e9, max: 20e9 },
        { label: '20-21M', min: 20e9, max: 21e9 },
        { label: '21-22M', min: 21e9, max: 22e9 },
        { label: '22-23M', min: 22e9, max: 23e9 },
        { label: '23-24M', min: 23e9, max: 24e9 },
        { label: '24-25M', min: 24e9, max: 25e9 },
        { label: '25-26M', min: 25e9, max: 26e9 },
        { label: '26-27M', min: 26e9, max: 27e9 },
        { label: '27-28M', min: 27e9, max: 28e9 },
        { label: '28-29M', min: 28e9, max: 29e9 },
        { label: '29-30M', min: 29e9, max: 30e9 },
        { label: '>30M', min: 30e9, max: Infinity }
    ];

    const bottleneckLoanSizeBuckets = [
        { label: '1-5M', min: 1e9, max: 5e9 },
        { label: '5-10M', min: 5e9, max: 10e9 },
        { label: '10-15M', min: 10e9, max: 15e9 },
        { label: '15-20M', min: 15e9, max: 20e9 },
        { label: '20-25M', min: 20e9, max: 25e9 },
        { label: '25-30M', min: 25e9, max: 30e9 },
        { label: '>30M', min: 30e9, max: Infinity }
    ];

    // Store modal instances
    let detailModalInstance = null;
    let singleAppModalInstance = null;

    if (window.Chart && Chart.defaults) {
        Chart.defaults.events = ['mousemove', 'mouseout', 'click'];
    }

    document.addEventListener('dragstart', function (ev) {
        if (ev.target && ev.target.closest && ev.target.closest('canvas, .chart-card')) {
            ev.preventDefault();
        }
    }, true);

    document.addEventListener('selectstart', function (ev) {
        if (ev.target && ev.target.closest && ev.target.closest('canvas')) {
            ev.preventDefault();
        }
    }, true);

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('canvas').forEach(function (cv) {
            cv.setAttribute('draggable', 'false');
            cv.ondragstart = function () { return false; };
        });

        const aiInput = document.getElementById('aiChatInput');
        if (aiInput) {
            aiInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendAIChat();
                }
            });
        }

        requestAnimationFrame(() => document.body.classList.add('sa-ready')); 

        loadDashboardFromApi(false);
    });
    function toggleAIChat() {
        document.getElementById('aiChatPanel').classList.toggle('show');
    }

    function toggleSimFloat() {
        const panel = document.getElementById('simFloatPanel');
        if (panel) panel.classList.toggle('show');
    }

    async function togglePresentMode() {
        const inMode = document.body.classList.toggle('present-mode');
        const btn = document.getElementById('presentModeBtn');
        if (btn) {
            btn.innerHTML = inMode
                ? '<i class="bi bi-easel2-fill me-1"></i>Exit Present'
                : '<i class="bi bi-easel me-1"></i>Present Mode';
        }
        try {
            if (inMode && !document.fullscreenElement) {
                await document.documentElement.requestFullscreen();
            } else if (!inMode && document.fullscreenElement) {
                await document.exitFullscreen();
            }
        } catch (_) {}
    }

    function appendAIMessage(text, cls) {
        const log = document.getElementById('aiChatLog');
        const div = document.createElement('div');
        div.className = `ai-msg ${cls}`;
        div.textContent = text;
        log.appendChild(div);
        log.scrollTop = log.scrollHeight;
    }

    function getDashboardContextForAI() {
        const total = document.getElementById('kTotalApp')?.innerText || '-';
        const avgTat = document.getElementById('vAvg')?.innerText || '-';
        const outlier = document.getElementById('vOut')?.innerText || '-';
        const bottleneck = (bottleneckMetrics || [])
            .slice()
            .sort((a,b)=>b.avg-a.avg)
            .slice(0,3)
            .map(x => `${x.stat}: ${safeNum(x.avg,0).toFixed(1)} days`)
            .join(' | ') || '-';
        return [
            `Total Applications: ${total}`,
            `Average TAT: ${avgTat}`,
            `Jumlah Outlier: ${outlier}`,
            `Top Bottleneck: ${bottleneck}`
        ].join('\n');
    }

    async function askGemini(userText, contextText) {
        if (!GEMINI_API_KEY) {
            throw new Error('GEMINI_API_KEY masih kosong. Isi dulu di script HTML.');
        }
        const url = `https://generativelanguage.googleapis.com/v1beta/models/${GEMINI_MODEL}:generateContent?key=${GEMINI_API_KEY}`;
        const body = {
            contents: [{
                role: 'user',
                parts: [{
                    text:
`Anda asisten analis LOS dashboard. Jawab ringkas dalam bahasa Indonesia.
Jika angka tidak cukup, jelaskan keterbatasannya.

Konteks Dashboard:
${contextText}

Pertanyaan:
${userText}`
                }]
            }]
        };
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        if (!res.ok) {
            const err = await res.text();
            throw new Error(`Gemini error ${res.status}: ${err}`);
        }
        const data = await res.json();
        return data?.candidates?.[0]?.content?.parts?.[0]?.text || 'No response.';
    }

    async function sendAIChat() {
        const input = document.getElementById('aiChatInput');
        const btn = document.getElementById('aiChatSendBtn');
        const question = (input.value || '').trim();
        if (!question) return;
        input.value = '';
        appendAIMessage(question, 'user');
        btn.disabled = true;
        btn.textContent = '...';
        try {
            const ctx = getDashboardContextForAI();
            const answer = await askGemini(question, ctx);
            appendAIMessage(answer, 'bot');
        } catch (e) {
            appendAIMessage(String(e.message || e), 'err');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Kirim';
        }
    }

    // FUNGSI NORMALISASI PURPOSE
    function normalizePurpose(purpose) {
        if (!purpose && purpose !== 0) return 'Unknown';
        
        // Konversi ke string, trim, lowercase
        let normalized = String(purpose).trim().toLowerCase();
        
        // Hapus karakter khusus dan spasi berlebih
        normalized = normalized.replace(/[^\w\s]/g, '');
        normalized = normalized.replace(/\s+/g, ' ');
        
        // Jika kosong setelah normalisasi
        if (!normalized || normalized === 'unknown') return 'Unknown';
        
        return normalized;
    }

    function switchMode(m) {
        mode = m;
        document.querySelectorAll('.mode-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('btn-'+m).classList.add('active');
        document.querySelectorAll('.inp-p').forEach(p => p.classList.remove('show'));
        document.getElementById('pan-'+m).classList.add('show');
    }

    function toggleSidebar() {
        document.body.classList.toggle('nav-function-minify');
    }

    function menuFromStep(stepId) {
        return stepId === 'step3' ? 'dashboard' : 'import';
    }

    function setHeaderByMenu(menuKey) {
        const pageTitle = document.getElementById('pageTitle');
        const pageSubtitle = document.getElementById('pageSubtitle');

        document.body.setAttribute('data-route', menuKey);

        if (!pageTitle || !pageSubtitle) return;

        if (menuKey === 'import') {
            pageTitle.innerHTML = '<i class="subheader-icon fal fa-file-upload"></i> Import Data';
            pageSubtitle.textContent = 'Upload dan mapping data Excel ke database';
        } else {
            pageTitle.innerHTML = '<i class="subheader-icon fal fa-chart-area"></i> Executive LOS Dashboard';
            pageSubtitle.textContent = 'Strategic Insights & Performance Monitoring';
        }
    }

    function setActiveSidebarMenu(stepId) {
        const menuKey = menuFromStep(stepId);
        document.querySelectorAll('.js-menu-link, .sidebar-link').forEach((el) => {
            const isActive = el.getAttribute('data-menu-target') === menuKey;
            el.classList.toggle('active', isActive);
            const parentLi = el.closest('li');
            if (parentLi) parentLi.classList.toggle('active', isActive);
        });
        setHeaderByMenu(menuKey);
    }

    function goToStep(stepId) {
        ['step1', 'step2', 'step3'].forEach((id) => {
            const panel = document.getElementById(id);
            if (!panel) return;

            const isActive = id === stepId;
            panel.classList.toggle('active', isActive);
            if (isActive) {
                panel.classList.remove('step-enter');
                void panel.offsetWidth;
                panel.classList.add('step-enter');
            }
        });
        setActiveSidebarMenu(stepId);
    }

    function goToMenu(menuKey) {
        if (menuKey === 'dashboard') {
            goToStep('step3');
            return;
        }

        if (raw && raw.length > 0) {
            goToStep('step2');
        } else {
            goToStep('step1');
        }
    }

    function getCurrentMapping() {
        return {
            id: document.getElementById('mId').value,
            seg: document.getElementById('mSeg').value,
            purp: document.getElementById('mPurp').value,
            limit: document.getElementById('mLimit').value,
            branch: document.getElementById('mBranch').value,
            mon: document.getElementById('mMonth').value,
            c: document.getElementById('mComplete').value,
            s: document.getElementById('mStart').value,
            e: document.getElementById('mEnd').value,
            t: document.getElementById('mTat').value,
            stat: document.getElementById('mStat').value
        };
    }

    function setImportStatus(text, type) {
        const el = document.getElementById('dbImportStatus');
        if (!el) return;

        if (!text) {
            el.innerHTML = '';
            return;
        }

        const cls = type === 'ok' ? 'text-success' : (type === 'warn' ? 'text-warning' : 'text-light');
        el.innerHTML = `<span class="${cls}">${text}</span>`;
    }

    async function importToDatabaseAndGenerate() {
        try {
            if (!raw || raw.length === 0) {
                alert('Upload file Excel terlebih dahulu.');
                goToStep('step1');
                return;
            }

            const mapping = getCurrentMapping();
            if (!mapping.id || !mapping.stat) {
                alert('Application ID dan Status wajib diisi pada mapping.');
                return;
            }

            setImportStatus('Menyimpan data ke database...', 'warn');

            const response = await fetch('/api/dashboard/import', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    filename: currentUploadedFileName || 'excel-upload',
                    mode,
                    mapping,
                    rows: raw
                })
            });

            if (!response.ok) {
                const err = await response.text();
                throw new Error(`Import gagal: ${err}`);
            }

            const result = await response.json();
            setImportStatus(`Import berhasil (${result.imported_rows || 0} rows).`, 'ok');
            await loadDashboardFromApi(false);
        } catch (err) {
            console.error(err);
            alert(err.message || 'Import gagal.');
            setImportStatus('Import gagal.', 'warn');
        }
    }

    function mapApiRecordsToRaw(records) {
        return (records || []).map((row) => ({
            app_id: row.app_id || '',
            segment: row.segment || '',
            purpose: row.purpose || '',
            approved_limit: row.approved_limit ?? '',
            branch_name: row.branch_name || '',
            booking_month: row.booking_month || '',
            create_date: row.start_date || '',
            completed_date: row.end_date || '',
            complete_date: row.complete_date || '',
            tat_days: row.tat_days ?? '',
            status_flow: row.status_flow || ''
        }));
    }

    function renderDashboardFromApiRecords(records, batchMeta) {
        latestImportMeta = batchMeta || null;
        raw = mapApiRecordsToRaw(records);
        if (!raw.length) return;

        const headers = ['app_id', 'segment', 'purpose', 'approved_limit', 'branch_name', 'booking_month', 'create_date', 'completed_date', 'complete_date', 'tat_days', 'status_flow'];
        fillSel(headers);

        document.getElementById('mId').value = 'app_id';
        document.getElementById('mSeg').value = 'segment';
        document.getElementById('mPurp').value = 'purpose';
        document.getElementById('mLimit').value = 'approved_limit';
        document.getElementById('mBranch').value = 'branch_name';
        document.getElementById('mMonth').value = 'booking_month';
        document.getElementById('mStart').value = 'create_date';
        document.getElementById('mEnd').value = 'completed_date';
        document.getElementById('mComplete').value = 'complete_date';
        document.getElementById('mTat').value = 'tat_days';
        document.getElementById('mStat').value = 'status_flow';

        const resolvedMode = (batchMeta && batchMeta.calculation_mode === 'tat') ? 'tat' : 'date';
        switchMode(resolvedMode);
        processData();

        const importedLabel = batchMeta && batchMeta.imported_at
            ? new Date(batchMeta.imported_at).toLocaleString('id-ID')
            : '-';
        setImportStatus(`Data source: MySQL (batch #${batchMeta?.id || '-'}, import ${importedLabel})`, 'ok');
        goToStep('step3');
    }

    async function loadDashboardFromApi(showAlertIfEmpty = false) {
        try {
            const response = await fetch('/api/dashboard/content');
            if (!response.ok) throw new Error(`API error ${response.status}`);

            const payload = await response.json();
            if (!payload.has_data) {
                setImportStatus('Belum ada data di database. Silakan import Excel.', 'warn');
                if (showAlertIfEmpty) alert('Belum ada data import di database.');
                goToStep('step1');
                return false;
            }

            renderDashboardFromApiRecords(payload.records, payload.batch);
            return true;
        } catch (err) {
            console.error(err);
            setImportStatus('Gagal load data API. Gunakan import manual.', 'warn');
            return false;
        }
    }
    // ROBUST FILE READER
    const fileInputEl = document.getElementById('fileIn');
    if (fileInputEl) fileInputEl.addEventListener('change', e => {
        const f = e.target.files[0];
        if(!f) return;
        currentUploadedFileName = f.name;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, {type: 'array', cellDates: true});
                const firstSheetName = workbook.SheetNames[0];
                const worksheet = workbook.Sheets[firstSheetName];
                raw = XLSX.utils.sheet_to_json(worksheet, {defval:''});
                
                if(raw.length === 0) {
                    alert("File terbaca tapi kosong atau format tidak dikenali.");
                    return;
                }
                
                const headers = XLSX.utils.sheet_to_json(worksheet, {header: 1})[0];
                fillSel(headers);
                
                goToStep('step2');
            } catch(err) {
                console.error(err);
                alert("Gagal memproses file! Error: " + err.message);
            }
        };
        reader.readAsArrayBuffer(f);
    });
    
    function fillSel(h) {
        document.querySelectorAll('.col-sel').forEach(s => {
            s.innerHTML = '<option disabled selected>Select...</option>';
            h.forEach(x => { let o = document.createElement('option'); o.value = x; o.text = x; s.appendChild(o); });
        });
        const auto = (id, keys) => {
            const el = document.getElementById(id);
            for(let i=0; i<el.options.length; i++) if(keys.some(k => el.options[i].text.toLowerCase().includes(k))) { el.selectedIndex=i; break; }
        };
        auto('mId', ['app_id', 'id', 'no']);
        auto('mSeg', ['segment']);
        auto('mPurp', ['purpose', 'pupose']);
        auto('mLimit', ['approved limit', 'limit']);
        auto('mBranch', ['branch', 'cabang']);
        auto('mMonth', ['booking month', 'month']);
        auto('mStart', ['create_date', 'start']);
        auto('mEnd', ['completed_date', 'end']);
        auto('mComplete', ['completed_date', 'complete date', 'end']);
        auto('mTat', ['tat', 'sla']);
        auto('mStat', ['status_flow', 'flow']);

        const sIdx = document.getElementById('mStart').selectedIndex;
        const tIdx = document.getElementById('mTat').selectedIndex;
        if(sIdx <= 0 && tIdx > 0) switchMode('tat');
    }

    // Fungsi untuk format bulan untuk display
    function formatMonthDisplay(monthKey) {
        if (monthKey === 'Unknown' || !monthKey) return 'Unknown';
        
        try {
            if (monthKey.includes('-')) {
                const [year, month] = monthKey.split('-');
                const monthNum = parseInt(month);
                const yearNum = parseInt(year);
                
                const monthNames = [
                    'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
                ];
                
                const shortYear = yearNum.toString().slice(-2);
                return `${monthNames[monthNum - 1]} ${shortYear}`;
            } else {
                return monthKey;
            }
        } catch (e) {
            return monthKey;
        }
    }

    function processData() {
        const m = {
            id: document.getElementById('mId').value,
            seg: document.getElementById('mSeg').value,
            purp: document.getElementById('mPurp').value,
            limit: document.getElementById('mLimit').value,
            branch: document.getElementById('mBranch').value,
            mon: document.getElementById('mMonth').value,
            c: document.getElementById('mComplete').value,
            s: document.getElementById('mStart').value,
            e: document.getElementById('mEnd').value,
            t: document.getElementById('mTat').value,
            stat: document.getElementById('mStat').value
        };

        if(!m.id || !m.stat) { alert("Application ID and Status are required!"); return; }

        const apps = {}, statusAgg = {};
        appFlowEvents = {};
        let flowRowIndex = 0;

        raw.forEach(row => {
            const id = row[m.id]; if(!id) return;
            
            // PERBAIKAN: Parsing tanggal yang lebih robust untuk menghindari masalah zona waktu
            let monthKey = '';
            const dateValue = row[m.mon];
            
            if (dateValue instanceof Date) {
                // Jika sudah Date object
                monthKey = dateValue.getFullYear() + '-' + String(dateValue.getMonth() + 1).padStart(2, '0');
            } else if (typeof dateValue === 'string' || typeof dateValue === 'number') {
                // Coba parse string atau number ke Date dengan cara yang lebih robust
                try {
                    // PERBAIKAN: Parsing manual untuk menghindari masalah zona waktu
                    let parsedDate;
                    
                    if (typeof dateValue === 'string') {
                        // Coba berbagai format tanggal
                        const dateStr = dateValue.toString().trim();
                        
                        // Format 5/1/2025 (MM/DD/YYYY) -> tanggal 5 Januari 2025
                        const parts = dateStr.split(/[\/\-\.]/);
                        if (parts.length === 3) {
                            const month = parseInt(parts[0]);
                            const day = parseInt(parts[1]);
                            const year = parseInt(parts[2]);
                            
                            // Validasi dan buat tanggal dengan UTC untuk menghindari zona waktu
                            if (!isNaN(month) && !isNaN(day) && !isNaN(year)) {
                                // Jika tahun 2 digit, tambahkan 2000
                                const fullYear = year < 100 ? 2000 + year : year;
                                parsedDate = new Date(Date.UTC(fullYear, month - 1, day));
                            }
                        }
                    }
                    
                    // Jika parsing manual gagal, coba dengan Date biasa
                    if (!parsedDate || isNaN(parsedDate.getTime())) {
                        parsedDate = new Date(dateValue);
                    }
                    
                    if (parsedDate && !isNaN(parsedDate.getTime())) {
                        // Gunakan getUTCMonth() untuk menghindari masalah zona waktu
                        monthKey = parsedDate.getUTCFullYear() + '-' + 
                                  String(parsedDate.getUTCMonth() + 1).padStart(2, '0');
                    } else {
                        monthKey = 'Unknown';
                    }
                } catch (e) {
                    monthKey = 'Unknown';
                }
            } else {
                monthKey = 'Unknown';
            }

            // Format tampilan untuk display
            const displayMonth = formatMonthDisplay(monthKey);

            if(!apps[id]) {
                apps[id] = {
                    id, 
                    seg: row[m.seg] || 'Unknown',
                    purp: row[m.purp] || 'General',
                    purpOriginal: row[m.purp] || 'General', // Simpan original purpose
                    limit: parseFloat(row[m.limit]) || 0,
                    branch: row[m.branch] || 'Unknown',
                    mon: monthKey, // Simpan key untuk grouping
                    displayMon: displayMonth, // Simpan untuk display
                    min: null, max: null, sumT: 0
                };
            }

            const statusName = (row[m.stat] || 'Unknown').toString().trim() || 'Unknown';
            let dur = 0;
            let startDate = new Date(NaN);
            let endDate = new Date(NaN);
            const completeDate = parseDateRobust(row[m.c] || row[m.e]);
            if(mode === 'date') {
                // PERBAIKAN: Parsing tanggal start dan end juga dengan cara yang sama
                startDate = parseDateRobust(row[m.s]);
                endDate = parseDateRobust(row[m.e]);
                
                if(!isNaN(startDate.getTime())) { 
                    if(!apps[id].min || startDate < apps[id].min) apps[id].min = startDate; 
                }
                if(!isNaN(endDate.getTime())) { 
                    if(!apps[id].max || endDate > apps[id].max) apps[id].max = endDate; 
                }
                if(!isNaN(startDate.getTime()) && !isNaN(endDate.getTime())) {
                    dur = (endDate - startDate) / 86400000;
                }
            } else {
                dur = parseFloat(row[m.t]) || 0;
                apps[id].sumT += dur;
            }
            if(dur < 0) dur = 0;

            if(!appFlowEvents[id]) {
                appFlowEvents[id] = {
                    id: id,
                    branch: apps[id].branch,
                    mon: apps[id].mon,
                    displayMon: apps[id].displayMon,
                    events: []
                };
            }
            appFlowEvents[id].events.push({
                status: statusName,
                duration: dur,
                startMs: !isNaN(startDate.getTime()) ? startDate.getTime() : null,
                endMs: !isNaN(endDate.getTime()) ? endDate.getTime() : null,
                completeMs: !isNaN(completeDate.getTime()) ? completeDate.getTime() : null,
                completeKey: makeDateKey(completeDate),
                seq: flowRowIndex
            });

            const k = id + "##" + statusName;
            statusAgg[k] = (statusAgg[k] || 0) + dur;
            flowRowIndex++;
        });

        e2eData = Object.values(apps).map(a => {
            let tat = 0;
            if(mode === 'date') tat = (a.min && a.max) ? (a.max - a.min) / 86400000 : 0;
            else tat = a.sumT;
            // Pastikan TAT adalah angka valid
            tat = isNaN(tat) ? 0 : Math.max(0, Math.round(tat * 10) / 10);
            return { 
                ...a, 
                tat: tat,
                purpNormalized: normalizePurpose(a.purp) // Tambahkan normalized purpose
            };
        });

        // Filter out invalid TAT
        e2eData = e2eData.filter(app => !isNaN(app.tat) && app.tat >= 0);
        e2eById = {};
        e2eData.forEach(app => { e2eById[app.id] = app; });

        // Sort by TAT for quartile calculations
        e2eData.sort((a, b) => a.tat - b.tat);

        baseE2EData = e2eData.map(x => ({ ...x }));
        baseStatusAgg = { ...statusAgg };
        baseAppsById = {};
        Object.keys(apps).forEach(id => {
            baseAppsById[id] = { ...apps[id] };
        });
        baseAppFlowEvents = cloneFlowEventMap(appFlowEvents);
        
        // Filter applications dengan limit positif (> 0) untuk perhitungan limit
        appsWithPositiveLimit = e2eData.filter(app => app.limit > 0);
        
        // Metrics Calculation - PERBAIKAN: Hanya hitung limit dari applications with limit > 0
        const totalApps = e2eData.length;
        const appsWithZeroOrNegativeLimit = e2eData.filter(app => app.limit <= 0).length;
        const totalLimit = appsWithPositiveLimit.reduce((sum, app) => sum + app.limit, 0);
        const uniqueMonths = [...new Set(e2eData.map(d=>d.mon))].filter(x=>x!=='Unknown').length || 1;
        globalStats = calcStats(e2eData.map(d=>d.tat));
        baseGlobalStats = { ...globalStats };
        globalOutliers = e2eData.filter(d => d.tat > globalStats.high);
        
        console.log("Total e2eData:", e2eData.length);
        console.log("Apps with positive limit:", appsWithPositiveLimit.length);
        console.log("Apps with zero/negative limit:", appsWithZeroOrNegativeLimit);
        console.log("Global stats:", globalStats);
        
        // Calculate Q1, Median, and Q3 applications
        calculateQuartileApplications(e2eData, globalStats);

        // Store data for charts
        prepareChartData(e2eData, globalStats);

        // Update KPIs dengan info limit
        document.getElementById('kTotalApp').innerText = totalApps.toLocaleString();
        document.getElementById('kAvgAppMonth').innerText = (totalApps/uniqueMonths).toFixed(0);
        
        // PERBAIKAN: Hanya hitung total limit dari applications with limit > 0
        document.getElementById('kTotalLimit').innerText = formatIDR(totalLimit);
        
        // Update subtitle dengan info limit
        const limitSubText = document.getElementById('limitSubText');
        const limitInfoBadge = document.getElementById('limitInfoBadge');
        
        if (appsWithZeroOrNegativeLimit > 0) {
            limitSubText.innerHTML = `Portfolio Exposure`;
            limitInfoBadge.innerHTML = `<span class="limit-info">Only from ${appsWithPositiveLimit.length} apps (${appsWithZeroOrNegativeLimit} apps without limit)</span>`;
        } else {
            limitSubText.innerHTML = `Portfolio Exposure`;
            limitInfoBadge.innerHTML = `<span class="limit-info">All ${totalApps} apps have limits</span>`;
        }
        
        // PERBAIKAN: Average limit hanya dari applications with limit > 0
        const avgLimit = appsWithPositiveLimit.length > 0 ? totalLimit / appsWithPositiveLimit.length : 0;
        document.getElementById('kAvgLimit').innerText = formatIDR(avgLimit);
        
        // Update avg limit info
        const avgLimitSubText = document.getElementById('avgLimitSubText');
        const avgLimitInfoBadge = document.getElementById('avgLimitInfoBadge');
        
        if (appsWithPositiveLimit.length > 0) {
            avgLimitSubText.innerHTML = `Average Ticket Size`;
            avgLimitInfoBadge.innerHTML = `<span class="limit-info">From ${appsWithPositiveLimit.length} apps with limit > 0</span>`;
        } else {
            avgLimitSubText.innerHTML = `Average Ticket Size`;
            avgLimitInfoBadge.innerHTML = `<span class="limit-warning">No applications with limit > 0</span>`;
        }
        
        // Update info panel tentang perhitungan limit
        document.getElementById('limitCalcInfo').innerHTML = 
            `From ${totalApps} applications, ${appsWithPositiveLimit.length} have limit > 0 (${appsWithZeroOrNegativeLimit} apps dengan limit 0 excluded from calculation).`;
        
        // Update Stats KPIs
        document.getElementById('vAvg').innerText = globalStats.avg.toFixed(1);
        document.getElementById('vMed').innerText = globalStats.med.toFixed(1);
        document.getElementById('vMode').innerText = globalStats.mod;
        document.getElementById('vQ1').innerText = globalStats.q1.toFixed(1);
        document.getElementById('vQ3').innerText = globalStats.q3.toFixed(1);
        document.getElementById('vOut').innerText = globalStats.out;
        
        // NEW: Update IQR and Outlier Boundary KPIs
        const iqrValue = globalStats.q3 - globalStats.q1;
        document.getElementById('vIQR').innerText = iqrValue.toFixed(1);
        document.getElementById('vOutBoundary').innerText = globalStats.high.toFixed(1);
        
        // Update outlier info
        document.getElementById('infoQ1').innerText = globalStats.q1.toFixed(1);
        document.getElementById('infoQ3').innerText = globalStats.q3.toFixed(1);
        document.getElementById('outlierFormula').innerHTML = 
            `${globalStats.q3.toFixed(1)} + 1.5x${iqrValue.toFixed(1)} = ${globalStats.high.toFixed(1)} days`;

        // Create Charts
        createCharts(e2eData, globalStats, statusAgg, apps);

        goToStep('step3');
    }

    function parseDateRobust(dateValue) {
        if (!dateValue) return new Date(NaN);
        
        if (dateValue instanceof Date) {
            return new Date(dateValue.getFullYear(), dateValue.getMonth(), dateValue.getDate());
        }
        
        if (typeof dateValue === 'string') {
            const dateStr = dateValue.toString().trim();
            
            // Coba parsing format MM/DD/YYYY atau DD/MM/YYYY
            const parts = dateStr.split(/[\/\-\.]/);
            if (parts.length === 3) {
                const part1 = parseInt(parts[0]);
                const part2 = parseInt(parts[1]);
                const part3 = parseInt(parts[2]);
                
                // Coba format MM/DD/YYYY (5/1/2025 = 5 Januari 2025)
                if (part1 <= 12 && part2 <= 31 && part3 > 1000) {
                    // part1 adalah bulan, part2 adalah days, part3 adalah tahun
                    const year = part3;
                    const month = part1 - 1;
                    const day = part2;
                    return new Date(Date.UTC(year, month, day));
                }
                // Coba format DD/MM/YYYY (1/5/2025 = 1 Mei 2025)
                else if (part2 <= 12 && part1 <= 31 && part3 > 1000) {
                    // part1 adalah days, part2 adalah bulan, part3 adalah tahun
                    const year = part3;
                    const month = part2 - 1;
                    const day = part1;
                    return new Date(Date.UTC(year, month, day));
                }
            }
            
            // Coba parsing dengan Date biasa
            const parsed = new Date(dateStr);
            if (!isNaN(parsed.getTime())) {
                return new Date(parsed.getFullYear(), parsed.getMonth(), parsed.getDate());
            }
        }
        
        // Jika semua gagal, return tanggal invalid
        return new Date(NaN);
    }

    function makeDateKey(dateObj) {
        if (!(dateObj instanceof Date) || isNaN(dateObj.getTime())) return null;
        const y = dateObj.getFullYear();
        const m = String(dateObj.getMonth() + 1).padStart(2, '0');
        const d = String(dateObj.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function prepareChartData(e2eData, globalStats) {
        // Trend Data - PERBAIKAN: Hitung volume semua apps, tapi limit hanya dari yang limit > 0
        trendData = {};
        e2eData.forEach(d => { 
            if(!trendData[d.mon]) trendData[d.mon] = {
                cnt:0, 
                lim:0, 
                cntPositiveLimit: 0,
                limPositive: 0,
                apps: [],
                display: d.displayMon || formatMonthDisplay(d.mon)
            };
            trendData[d.mon].cnt++; 
            trendData[d.mon].apps.push(d);
            
            // PERBAIKAN: Pisahkan perhitungan untuk limit positif
            if (d.limit > 0) {
                trendData[d.mon].cntPositiveLimit++;
                trendData[d.mon].limPositive += d.limit;
            }
            // Catat semua limit (termasuk <= 0) untuk reference
            trendData[d.mon].lim += d.limit;
        });

        // Branch Data - PERBAIKAN: Pisahkan perhitungan limit
        branchData = {};
        e2eData.forEach(d => { 
            if(!branchData[d.branch]) branchData[d.branch] = {
                cnt:0, 
                lim:0,
                cntPositiveLimit: 0,
                limPositive: 0,
                apps: []
            };
            branchData[d.branch].cnt++; 
            branchData[d.branch].apps.push(d);
            
            // PERBAIKAN: Pisahkan perhitungan untuk limit positif
            if (d.limit > 0) {
                branchData[d.branch].cntPositiveLimit++;
                branchData[d.branch].limPositive += d.limit;
            }
            // Catat semua limit (termasuk <= 0) untuk reference
            branchData[d.branch].lim += d.limit;
        });

        // TAT Distribution Data - PERBAIKAN: Pisahkan perhitungan limit
        tatDistributionData = {};
        
        // Initialize all buckets
        tatBuckets.forEach(bucket => {
            tatDistributionData[bucket.label] = {
                cnt: 0, 
                cntPositiveLimit: 0,
                limPositive: 0,
                apps: []
            };
        });
        
        // Count applications per bucket
        e2eData.forEach(d => {
            let placed = false;
            
            // Check regular buckets
            for (let bucket of tatBuckets) {
                if (d.tat >= bucket.min && d.tat <= bucket.max) {
                    tatDistributionData[bucket.label].cnt++;
                    tatDistributionData[bucket.label].apps.push(d);
                    
                    // PERBAIKAN: Hitung limit positif terpisah
                    if (d.limit > 0) {
                        tatDistributionData[bucket.label].cntPositiveLimit++;
                        tatDistributionData[bucket.label].limPositive += d.limit;
                    }
                    placed = true;
                    break;
                }
            }
            
            // If not placed in any bucket (shouldn't happen with our buckets)
            if (!placed) {
                console.warn(`App ${d.id} with TAT ${d.tat} not placed in any bucket`);
            }
        });
    }

    function createCharts(e2eData, globalStats, statusAgg, apps, skipSimInit = false) {
        // Sort months for trend chart
        const sortedMonthKeys = Object.keys(trendData)
            .filter(key => key !== 'Unknown')
            .sort((a, b) => {
                if (a === 'Unknown') return 1;
                if (b === 'Unknown') return -1;
                return a.localeCompare(b);
            });
        
        // Jika ada Unknown, taruh di akhir
        if (trendData['Unknown']) {
            sortedMonthKeys.push('Unknown');
        }

        // Prepare labels dan data untuk chart
        const trendLabels = sortedMonthKeys.map(key => trendData[key].display);
        const trendCounts = sortedMonthKeys.map(key => trendData[key].cnt);
        
        // PERBAIKAN: Average limit hanya dari yang limit > 0
        const trendAvgLimits = sortedMonthKeys.map(key => {
            const data = trendData[key];
            return data.cntPositiveLimit > 0 ? data.limPositive / data.cntPositiveLimit : 0;
        });

        // Trend Chart
        if(charts.trendMix) charts.trendMix.destroy();
        charts.trendMix = new Chart(document.getElementById('cTrendMix'), {
            type: 'bar',
            data: {
                labels: trendLabels,
                datasets: [
                    { 
                        label: 'Volume', 
                        data: trendCounts, 
                        backgroundColor: '#2563eb', 
                        order: 2,
                        onClick: (evt, elements) => {
                            if(elements.length > 0) {
                                const monthKey = sortedMonthKeys[elements[0].index];
                                showDetailModal('trend', trendData[monthKey].apps, 
                                    `Monthly Applications: ${trendData[monthKey].display || monthKey}`);
                            }
                        }
                    },
                    { 
                        label: 'Avg Limit (hanya >0)', 
                        data: trendAvgLimits, 
                        borderColor: '#10b981', 
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        type:'line', 
                        tension:0.3, 
                        yAxisID: 'y1', 
                        order: 1,
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: '#10b981',
                        pointRadius: 4
                    }
                ]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                onClick: (evt, elements) => {
                    if(elements.length > 0) {
                        const monthKey = sortedMonthKeys[elements[0].index];
                        showDetailModal('trend', trendData[monthKey].apps, 
                            `Monthly Applications: ${trendData[monthKey].display || monthKey}`);
                    }
                },
                scales: { 
                    y: {
                        display:true, 
                        position:'left', 
                        title: {display: true, text: 'Volume'},
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (value % 1 === 0) {
                                    return value;
                                }
                            }
                        }
                    }, 
                    y1: {
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Avg Limit (hanya >0)'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    } 
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                if(context.datasetIndex === 0) {
                                    return `Volume: ${context.parsed.y} applications`;
                                } else {
                                    const monthKey = sortedMonthKeys[context.dataIndex];
                                    const data = trendData[monthKey];
                                    const avgLimit = data.cntPositiveLimit > 0 ? data.limPositive / data.cntPositiveLimit : 0;
                                    return `Avg Limit (hanya >0): ${formatIDR(avgLimit)}`;
                                }
                            },
                            afterLabel: function(context) {
                                const monthKey = sortedMonthKeys[context.dataIndex];
                                const data = trendData[monthKey];
                                
                                if(context.datasetIndex === 0) {
                                    return [
                                        `Applications with limit > 0: ${data.cntPositiveLimit}`,
                                        `Total Limit (hanya >0): ${formatIDR(data.limPositive)}`,
                                        `Avg Limit (hanya >0): ${formatIDR(data.cntPositiveLimit > 0 ? data.limPositive / data.cntPositiveLimit : 0)}`
                                    ].join('\n');
                                }
                                return '';
                            }
                        }
                    },
                    legend: {
                        labels: {
                            generateLabels: function(chart) {
                                const datasets = chart.data.datasets;
                                return datasets.map(function(dataset, i) {
                                    return {
                                        text: dataset.label,
                                        fillStyle: dataset.type === 'line' ? dataset.borderColor : dataset.backgroundColor,
                                        strokeStyle: dataset.type === 'line' ? dataset.borderColor : dataset.backgroundColor,
                                        lineWidth: 2,
                                        hidden: false,
                                        index: i
                                    };
                                });
                            }
                        }
                    }
                }
            }
        });
        
        // Update chart info
        const trendChartInfo = document.getElementById('trendChartInfo');
        const totalWithPositiveLimit = e2eData.filter(app => app.limit > 0).length;
        trendChartInfo.innerHTML = 
            `<span class="limit-info"><i class="bi bi-info-circle me-1"></i>Average Limit is calculated only from ${totalWithPositiveLimit} applications with limit > 0 (${e2eData.length} total apps)</span>`;

        // Branch Chart - Top 10 berdasarkan applications dengan limit positif
        const topBr = Object.entries(branchData)
            .map(([branch, data]) => ({
                branch, 
                cnt: data.cnt, 
                cntPositiveLimit: data.cntPositiveLimit,
                lim: data.lim,
                limPositive: data.limPositive,
                apps: data.apps
            }))
            .sort((a,b) => b.cntPositiveLimit - a.cntPositiveLimit) // Sort by apps with positive limit
            .slice(0, 10);

        if(charts.branch) charts.branch.destroy();
        charts.branch = new Chart(document.getElementById('cBranch'), {
            type: 'bar',
            data: {
                labels: topBr.map(x => x.branch),
                datasets: [
                    {
                        label: 'Total Apps',
                        data: topBr.map(x => x.cnt),
                        backgroundColor: '#cbd5e1',
                        borderColor: '#cbd5e1',
                        borderWidth: 1,
                        order: 2
                    },
                    {
                        label: 'Apps dengan Limit > 0',
                        data: topBr.map(x => x.cntPositiveLimit),
                        backgroundColor: '#06b6d4',
                        borderColor: '#06b6d4',
                        borderWidth: 1,
                        order: 1
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                onClick: (evt, elements) => {
                    if(elements.length > 0) {
                        const branch = topBr[elements[0].index];
                        showDetailModal('branch', branch.apps, `Branch Applications: ${branch.branch}`);
                    }
                },
                scales: {
                    x: {
                        stacked: false,
                        title: {
                            display: true,
                            text: 'Number of Applications'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const branch = topBr[context.dataIndex];
                                const datasetLabel = context.dataset.label;
                                const value = context.parsed.x;
                                
                                if (datasetLabel === 'Total Apps') {
                                    return `Total Apps: ${value}`;
                                } else {
                                    const avgLimit = branch.cntPositiveLimit > 0 ? branch.limPositive / branch.cntPositiveLimit : 0;
                                    return [
                                        `Apps dengan Limit > 0: ${value}`,
                                        `Total Limit: ${formatIDR(branch.limPositive)}`,
                                        `Avg Limit: ${formatIDR(avgLimit)}`
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        });
        
        // Update branch chart info
        const branchChartInfo = document.getElementById('branchChartInfo');
        const totalBranches = Object.keys(branchData).length;
        branchChartInfo.innerHTML = 
            `<span class="limit-info"><i class="bi bi-info-circle me-1"></i>Top 10 branches by applications with limit > 0 (${totalBranches} total branches)</span>`;

        // TAT Distribution Chart - DENGAN 6 BUCKET (sudah diupdate ke <7 days)
        const distributionLabels = Object.keys(tatDistributionData);
        const distributionCounts = distributionLabels.map(label => tatDistributionData[label].cnt);
        
        // PERUBAHAN: Warna untuk 6 bucket
        const distributionColors = ['#10b981', '#3b82f6', '#f59e0b', '#8b5cf6', '#ec4899', '#ef4444'];
        
        if(charts.dist) charts.dist.destroy();
        
        charts.dist = new Chart(document.getElementById('cDist'), {
            type: 'bar',
            data: {
                labels: distributionLabels,
                datasets: [{
                    label: 'Number of Applications',
                    data: distributionCounts,
                    backgroundColor: distributionColors,
                    borderColor: distributionColors,
                    borderWidth: 1,
                    onClick: (evt, elements) => {
                        if(elements.length > 0) {
                            const bucketLabel = distributionLabels[elements[0].index];
                            showDetailModal('distribution', tatDistributionData[bucketLabel].apps, `Applications with TAT: ${bucketLabel}`);
                        }
                    }
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                onClick: (evt, elements) => {
                    if(elements.length > 0) {
                        const bucketLabel = distributionLabels[elements[0].index];
                        showDetailModal('distribution', tatDistributionData[bucketLabel].apps, `Applications with TAT: ${bucketLabel}`);
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Applications'
                        },
                        ticks: {
                            precision: 0,
                            callback: function(value) {
                                if (value % 1 === 0) {
                                    return value;
                                }
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Rentang Waktu TAT'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const bucketLabel = distributionLabels[context.dataIndex];
                                const bucketData = tatDistributionData[bucketLabel];
                                const percent = ((bucketData.cnt / e2eData.length) * 100).toFixed(1);
                                
                                return [
                                    `Total Apps: ${bucketData.cnt} (${percent}%)`,
                                    `Apps dengan Limit > 0: ${bucketData.cntPositiveLimit}`,
                                    `Total Limit (hanya >0): ${formatIDR(bucketData.limPositive)}`
                                ];
                            },
                            afterTitle: function(context) {
                                const bucket = tatBuckets.find(b => b.label === context[0].label);
                                if (bucket) {
                                    return `Range: ${bucket.min === 0 ? '0' : bucket.min} - ${bucket.max === Infinity ? 'inf' : bucket.max} days`;
                                }
                                return '';
                            }
                        }
                    },
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Update distribution chart info
        const distChartInfo = document.getElementById('distChartInfo');
        distChartInfo.innerHTML = 
            `<span class="limit-info"><i class="bi bi-info-circle me-1"></i>The first bucket is now <7 days (not <8 days). There are 6 distribution buckets. Total Limit is calculated only from applications with limit > 0 in each bucket.</span>`;

        // PERBAIKAN: Status buckets for outlier analysis - SAMAKAN PERHITUNGAN DENGAN TOP BOTTLENECKS
        const stBuckets = {};
        Object.keys(statusAgg).forEach(k => {
            const [id, st] = k.split("##");
            if(!stBuckets[st]) stBuckets[st] = [];
            stBuckets[st].push({ tat: statusAgg[k], app: apps[id] });
        });
        statusDurationBuckets = stBuckets;
        
        statusOutliers = {};
        statusStats = {}; // Reset status stats
        
        const stMetrics = Object.keys(stBuckets).map(st => {
            const arr = stBuckets[st].map(x=>x.tat);
            const totalTat = arr.reduce((sum, val) => sum + val, 0);
            const s = calcStats(arr);
            statusStats[st] = s; // Store stats for each status
            statusOutliers[st] = stBuckets[st].filter(x=>x.tat > s.high).map(x=>({...x.app, tat:x.tat}));
            
            // Hitung IQR dan Boundary untuk setiap status
            const iqr = s.q3 - s.q1;
            const boundary = s.high;
            
            return { 
                stat:st, 
                ...s,
                totalTat: totalTat,
                cases: arr.length,
                iqr: iqr,
                boundary: boundary
            };
        });
        bottleneckMetrics = stMetrics;

        // Status table - DITAMBAHKAN KOLOM IQR DAN BOUNDARY
        document.getElementById('tblStatus').innerHTML = stMetrics.map(x => {
            const iqrColor = x.iqr > (globalStats.q3 - globalStats.q1) ? 'text-warning' : 'text-success';
            const boundaryColor = x.boundary > globalStats.high ? 'text-warning' : 'text-success';
            
            return `
                <tr>
                    <td class="text-start">${x.stat}</td>
                    <td>${x.avg.toFixed(1)}</td>
                    <td class="text-muted small">${x.q1.toFixed(1)}</td>
                    <td class="fw-bold">${x.med.toFixed(1)}</td>
                    <td class="text-muted small">${x.q3.toFixed(1)}</td>
                    <td class="iqr-cell ${iqrColor}">${x.iqr.toFixed(1)}</td>
                    <td class="boundary-cell ${boundaryColor}">${x.boundary.toFixed(1)}</td>
                    <td class="text-center">
                        <span class="badge-out" onclick="showOutliersModal('status', '${x.stat}')">${x.out}</span>
                    </td>
                </tr>
            `;
        }).join('');

        // Top Bottlenecks Chart - PERBAIKAN: Gunakan perhitungan yang sama dengan status table
        const totalStatusTatAll = stMetrics.reduce((sum, item) => sum + item.totalTat, 0);
        const slow = [...stMetrics].sort((a,b)=>b.avg-a.avg).slice(0,10).map(item => ({
            ...item,
            impactPct: totalStatusTatAll > 0 ? (item.totalTat / totalStatusTatAll) * 100 : 0
        }));
        createTopBottleneckChart(slow, totalStatusTatAll);
        renderAvgStepPerStatusChart();
        renderManagementActionBoard(stMetrics);
        if (!skipSimInit) {
            initActionSimulator(stMetrics);
        }

        // Purpose Chart - PERBAIKAN: Hitung average TAT hanya dari apps with limit > 0, GUNAKAN PURPOSE NORMALIZED
        const prp={}; const prpC={}; const prpPositiveCount={};
        e2eData.forEach(d => { 
            if (d.limit > 0) {
                const normPurp = d.purpNormalized || normalizePurpose(d.purp);
                prp[normPurp] = (prp[normPurp] || 0) + d.tat; 
                prpPositiveCount[normPurp] = (prpPositiveCount[normPurp] || 0) + 1; 
            }
        });
        const pL = Object.keys(prp);
        mkChart('cPurp', 'bar', pL, pL.map(k=>prp[k]/prpPositiveCount[k]), 'Avg TAT (hanya limit > 0)', '#6366f1', 'y');

        // PERUBAHAN BESAR: Scatter Chart dengan 5 KATEGORI - GUNAKAN NORMALIZED PURPOSE
        // Ambil unique purpose yang sudah dinormalisasi dan urutkan
        const uPurp = [...new Set(e2eData.map(d => d.purpNormalized || normalizePurpose(d.purp)))].sort();
        
        console.log("Unique normalized purposes:", uPurp);
        console.log("Number of unique purposes:", uPurp.length);
        
        // Buat 5 dataset untuk scatter chart
        const ds = [
            {label: '< Q1', data:[], backgroundColor:'#10b981', pointStyle:'circle', pointRadius: 5},
            {label: 'Q1 - Median', data:[], backgroundColor:'#27D6F5', pointStyle:'circle', pointRadius: 5},
            {label: 'Median - Q3', data:[], backgroundColor:'#3b82f6', pointStyle:'circle', pointRadius: 5},
            {label: 'Q3 - Boundary', data:[], backgroundColor:'#f59e0b', pointStyle:'circle', pointRadius: 5},
            {label: '> Boundary (Outlier)', data:[], backgroundColor:'#ef4444', pointStyle:'triangle', pointRadius: 7}
        ];
        
        e2eData.forEach(d => {
            // Gunakan normalized purpose
            const normPurp = d.purpNormalized || normalizePurpose(d.purp);
            
            let datasetIndex;
            const tat = d.tat;
            
            if (tat < globalStats.q1) {
                datasetIndex = 0; // < Q1
            } else if (tat < globalStats.med) {
                datasetIndex = 1; // Q1 - Median
            } else if (tat < globalStats.q3) {
                datasetIndex = 2; // Median - Q3
            } else if (tat <= globalStats.high) {
                datasetIndex = 3; // Q3 - Boundary
            } else {
                datasetIndex = 4; // Outlier
            }
            
            // Temukan index purpose di array unique purpose
            const yIndex = uPurp.indexOf(normPurp);
            
            // Jika tidak ditemukan (seharusnya tidak terjadi), default ke 0
            const yPosition = yIndex >= 0 ? yIndex + 0.5 : 0.5;
            
            ds[datasetIndex].data.push({ 
                x: d.tat, 
                y: yPosition,
                full: d 
            });
        });
        
        // Update legend di atas scatter chart
        const scatterLegend = document.getElementById('scatterLegend');
        scatterLegend.innerHTML = `
            <div class="scatter-legend">
                <div class="scatter-legend-item"><div class="scatter-legend-color" style="background-color:#10b981"></div>TAT < Q1 (${globalStats.q1.toFixed(1)} days)</div>
                <div class="scatter-legend-item"><div class="scatter-legend-color" style="background-color:#27D6F5"></div>Q1 - Median (${globalStats.med.toFixed(1)} days)</div>
                <div class="scatter-legend-item"><div class="scatter-legend-color" style="background-color:#3b82f6"></div>Median - Q3 (${globalStats.q3.toFixed(1)} days)</div>
                <div class="scatter-legend-item"><div class="scatter-legend-color" style="background-color:#f59e0b"></div>Q3 - Boundary (${globalStats.high.toFixed(1)} days)</div>
                <div class="scatter-legend-item"><div class="scatter-legend-color" style="background-color:#ef4444"></div>Outlier: TAT > ${globalStats.high.toFixed(1)} days</div>
            </div>
        `;
        
        if(charts.sc) charts.sc.destroy();
        charts.sc = new Chart(document.getElementById('cScatter'), {
            type: 'scatter', 
            data: { datasets: ds },
            options: {
                responsive: true, 
                maintainAspectRatio: false,
                onClick: (e, items) => { 
                    if(items && items.length > 0){ 
                        const datasetIndex = items[0].datasetIndex;
                        const dataIndex = items[0].index;
                        const pt = ds[datasetIndex].data[dataIndex];
                        const app = pt.full;
                        
                        // Tampilkan detail applications
                        showSingleAppDetail(app);
                    } 
                },
                scales: { 
                    x: {
                        title: {display:true, text:'Days (E2E)'},
                        min: 0,
                        suggestedMax: Math.max(...e2eData.map(d => d.tat)) * 1.1,
                        afterBuildTicks: function(axis) {
                            // Tambahkan garis referensi untuk kuartil
                            if (!axis.ticks.some(t => t.value === globalStats.q1)) {
                                axis.ticks.push({value: globalStats.q1, label: `Q1: ${globalStats.q1.toFixed(1)}`});
                            }
                            if (!axis.ticks.some(t => t.value === globalStats.med)) {
                                axis.ticks.push({value: globalStats.med, label: `Median: ${globalStats.med.toFixed(1)}`});
                            }
                            if (!axis.ticks.some(t => t.value === globalStats.q3)) {
                                axis.ticks.push({value: globalStats.q3, label: `Q3: ${globalStats.q3.toFixed(1)}`});
                            }
                            if (!axis.ticks.some(t => t.value === globalStats.high)) {
                                axis.ticks.push({value: globalStats.high, label: `Boundary: ${globalStats.high.toFixed(1)}`});
                            }
                        },
                        grid: {
                            color: function(context) {
                                // Warna garis grid berbeda untuk setiap kuartil
                                if (context.tick.value === globalStats.q1) {
                                    return '#10b981';
                                } else if (context.tick.value === globalStats.med) {
                                    return '#3b82f6';
                                } else if (context.tick.value === globalStats.q3) {
                                    return '#f59e0b';
                                } else if (context.tick.value === globalStats.high) {
                                    return '#ef4444';
                                }
                                return '#e2e8f0';
                            },
                            lineWidth: function(context) {
                                if ([globalStats.q1, globalStats.med, globalStats.q3, globalStats.high].includes(context.tick.value)) {
                                    return 2;
                                }
                                return 1;
                            },
                            borderDash: function(context) {
                                if ([globalStats.q1, globalStats.med, globalStats.q3, globalStats.high].includes(context.tick.value)) {
                                    return [3, 3];
                                }
                                return [];
                            }
                        }
                    }, 
                    y: {
                        ticks: {
                            // PERBAIKAN PENTING: Hanya tampilkan label untuk nilai integer (0, 1, 2, ...)
                            // bukan untuk setiap nilai desimal (0.5, 1.5, ...)
                            callback: function(value, index, values) {
                                // Hanya tampilkan label untuk nilai integer
                                if (value % 1 === 0.5) {
                                    const purposeIndex =  Math.floor(value);
                                    if (purposeIndex >= 0 && purposeIndex < uPurp.length) {
                                        return uPurp[purposeIndex];
                                    }
                                }
                                return '';
                            },
                            // Atur step size menjadi 1 agar hanya nilai integer yang ditampilkan
                            stepSize: 0.5
                        }, 
                        min: 0, 
                        max: uPurp.length,
                        title: {
                            display: true,
                            text: 'Purpose'
                        },
                        grid: {
                            color: '#f1f5f9'
                        }
                    } 
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const app = ds[context.datasetIndex].data[context.dataIndex].full;
                                const normPurp = app.purpNormalized || normalizePurpose(app.purp);
                                const isOutlier = app.tat > globalStats.high;
                                const iqr = globalStats.q3 - globalStats.q1;
                                const boundary = globalStats.high;
                                const limitStatus = app.limit > 0 ? 
                                    `Limit: ${formatIDR(app.limit)} (Positif)` : 
                                    `Limit: ${formatIDR(app.limit)} (Excluded from total)`;
                                
                                // Tentukan kategori berdasarkan dataset
                                let category = "";
                                let categoryDesc = "";
                                switch(context.datasetIndex) {
                                    case 0: 
                                        category = "< Q1";
                                        categoryDesc = `Termasuk dalam 25% tercepat (TAT < ${globalStats.q1.toFixed(1)} days)`;
                                        break;
                                    case 1: 
                                        category = "Q1 - Median";
                                        categoryDesc = `Termasuk dalam 25-50% (${globalStats.q1.toFixed(1)} <= TAT < ${globalStats.med.toFixed(1)} days)`;
                                        break;
                                    case 2: 
                                        category = "Median - Q3";
                                        categoryDesc = `Termasuk dalam 50-75% (${globalStats.med.toFixed(1)} <= TAT < ${globalStats.q3.toFixed(1)} days)`;
                                        break;
                                    case 3: 
                                        category = "Q3 - Boundary";
                                        categoryDesc = `Termasuk dalam 75%-Boundary (${globalStats.q3.toFixed(1)} <= TAT <= ${boundary.toFixed(1)} days)`;
                                        break;
                                    case 4: 
                                        category = "Outlier";
                                        categoryDesc = `OUTLIER (TAT > ${boundary.toFixed(1)} days)`;
                                        break;
                                }
                                
                                // Tampilkan purpose asli dan normalized
                                let purposeInfo = `Purpose: ${app.purpOriginal || app.purp}`;
                                if ((app.purpOriginal || app.purp).toLowerCase() !== normPurp.toLowerCase()) {
                                    purposeInfo += ` (Normalized: ${normPurp})`;
                                }
                                
                                return [
                                    `App ID: ${app.id}`,
                                    `Branch: ${app.branch}`,
                                    purposeInfo,
                                    `TAT: ${app.tat.toFixed(1)} days`,
                                    `Category: ${category}`,
                                    categoryDesc,
                                    limitStatus,
                                    `Outlier Boundary: ${globalStats.q3.toFixed(1)} + 1.5x${iqr.toFixed(1)} = ${boundary.toFixed(1)} days`
                                ];
                            }
                        }
                    },
                    legend: {
                        display: true,
                        position: 'top',
                        onClick: function(e, legendItem, legend) {
                            return; // Nonaktifkan toggle legend
                        },
                        labels: {
                            boxWidth: 12,
                            padding: 10,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });

        // NEW: TAT vs Loan Size Scatter Chart
        createLoanSizeScatterChart(e2eData, globalStats);
    }

    // NEW FUNCTION: Create TAT vs Loan Size Scatter Chart
    function createLoanSizeScatterChart(e2eData, globalStats) {
        // Filter hanya applications with limit > 0
        const filteredData = e2eData.filter(app => app.limit > 0);
        
        if (filteredData.length === 0) {
            document.getElementById('loanSizeLegend').innerHTML = 
                '<div class="alert alert-warning p-2 mb-2">No application data with limit > 0 to display.</div>';
            return;
        }
        
        // Buat 5 dataset untuk scatter chart berdasarkan kategori TAT
        const loanSizeDatasets = [
            {label: '< Q1', data:[], backgroundColor:'#10b981', pointStyle:'circle', pointRadius: 5},
            {label: 'Q1 - Median', data:[], backgroundColor:'#27D6F5', pointStyle:'circle', pointRadius: 5},
            {label: 'Median - Q3', data:[], backgroundColor:'#3b82f6', pointStyle:'circle', pointRadius: 5},
            {label: 'Q3 - Boundary', data:[], backgroundColor:'#f59e0b', pointStyle:'circle', pointRadius: 5},
            {label: '> Boundary (Outlier)', data:[], backgroundColor:'#ef4444', pointStyle:'triangle', pointRadius: 7}
        ];
        
        // Group data berdasarkan kategori loan size
        filteredData.forEach(app => {
            let datasetIndex;
            const tat = app.tat;
            
            if (tat < globalStats.q1) {
                datasetIndex = 0; // < Q1
            } else if (tat < globalStats.med) {
                datasetIndex = 1; // Q1 - Median
            } else if (tat < globalStats.q3) {
                datasetIndex = 2; // Median - Q3
            } else if (tat <= globalStats.high) {
                datasetIndex = 3; // Q3 - Boundary
            } else {
                datasetIndex = 4; // Outlier
            }
            
            // Tentukan kategori loan size
            let loanSizeCategoryIndex = 0;
            for (let i = 0; i < loanSizeBuckets.length; i++) {
                if (app.limit >= loanSizeBuckets[i].min && app.limit <= loanSizeBuckets[i].max) {
                    loanSizeCategoryIndex = i;
                    break;
                }
            }
            
            loanSizeDatasets[datasetIndex].data.push({ 
                x: loanSizeCategoryIndex + 0.5, // Posisi X berdasarkan kategori loan size
                y: app.tat, // Posisi Y adalah TAT
                full: app 
            });
        });
        
        // Update legend untuk loan size chart
        const loanSizeLegend = document.getElementById('loanSizeLegend');
        loanSizeLegend.innerHTML = `
            <div class="scatter-legend">
                <div class="scatter-legend-item"><div class="scatter-legend-color" style="background-color:#10b981"></div>TAT < Q1 (${globalStats.q1.toFixed(1)} days)</div>
                <div class="scatter-legend-item"><div class="scatter-legend-color" style="background-color:#27D6F5"></div>Q1 - Median (${globalStats.med.toFixed(1)} days)</div>
                <div class="scatter-legend-item"><div class="scatter-legend-color" style="background-color:#3b82f6"></div>Median - Q3 (${globalStats.q3.toFixed(1)} days)</div>
                <div class="scatter-legend-item"><div class="scatter-legend-color" style="background-color:#f59e0b"></div>Q3 - Boundary (${globalStats.high.toFixed(1)} days)</div>
                <div class="scatter-legend-item"><div class="scatter-legend-color" style="background-color:#ef4444"></div>Outlier: TAT > ${globalStats.high.toFixed(1)} days</div>
            </div>
            <div class="mt-2">
                <small class="text-muted"><i class="bi bi-info-circle me-1"></i>X Axis: Loan Size Category (${loanSizeBuckets.map(b => b.label).join(', ')})</small>
            </div>
        `;
        
        // Destroy existing chart if exists
        if(charts.loanSize) charts.loanSize.destroy();
        
        // Buat chart scatter untuk TAT vs Loan Size
        charts.loanSize = new Chart(document.getElementById('cLoanSize'), {
            type: 'scatter', 
            data: { datasets: loanSizeDatasets },
            options: {
                responsive: true, 
                maintainAspectRatio: false,
                onClick: (e, items) => { 
                    if(items && items.length > 0){ 
                        const datasetIndex = items[0].datasetIndex;
                        const dataIndex = items[0].index;
                        const pt = loanSizeDatasets[datasetIndex].data[dataIndex];
                        const app = pt.full;
                        
                        // Tampilkan detail applications
                        showSingleAppDetail(app);
                    } 
                },
                scales: { 
                    x: {
                        title: {display:true, text:'Loan Size Category'},
                        min: 0,
                        max: loanSizeBuckets.length,
                        ticks: {
                            callback: function(value) {
                                if (value >= 0 && value < loanSizeBuckets.length) {
                                    return loanSizeBuckets[Math.floor(value)].label;
                                }
                                return '';
                            },
                            stepSize: 1
                        },
                        grid: {
                            color: '#e2e8f0'
                        }
                    }, 
                    y: {
                        title: {
                            display: true,
                            text: 'TAT (Days)'
                        },
                        beginAtZero: true,
                        // Buat skala Y dinamis berdasarkan data
                        suggestedMax: Math.max(...filteredData.map(d => d.tat)) * 1.1,
                        ticks: {
                            // Buat kelipatan 50 atau dinamis berdasarkan range data
                            callback: function(value) {
                                // Jika nilai lebih dari 1000, tampilkan dengan format yang lebih sederhana
                                if (value > 1000) {
                                    return Math.round(value/100) * 100;
                                }
                                // Jika nilai antara 100-1000, kelipatan 50
                                if (value > 100) {
                                    return Math.round(value/50) * 50;
                                }
                                // Jika nilai kecil, tampilkan apa adanya
                                return value;
                            }
                        },
                        grid: {
                            color: function(context) {
                                // Warna garis grid berbeda untuk setiap kuartil
                                if (context.tick.value === globalStats.q1) {
                                    return '#10b981';
                                } else if (context.tick.value === globalStats.med) {
                                    return '#3b82f6';
                                } else if (context.tick.value === globalStats.q3) {
                                    return '#f59e0b';
                                } else if (context.tick.value === globalStats.high) {
                                    return '#ef4444';
                                }
                                return '#e2e8f0';
                            },
                            lineWidth: function(context) {
                                if ([globalStats.q1, globalStats.med, globalStats.q3, globalStats.high].includes(context.tick.value)) {
                                    return 2;
                                }
                                return 1;
                            },
                            borderDash: function(context) {
                                if ([globalStats.q1, globalStats.med, globalStats.q3, globalStats.high].includes(context.tick.value)) {
                                    return [3, 3];
                                }
                                return [];
                            }
                        }
                    } 
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const app = loanSizeDatasets[context.datasetIndex].data[context.dataIndex].full;
                                const isOutlier = app.tat > globalStats.high;
                                const iqr = globalStats.q3 - globalStats.q1;
                                const boundary = globalStats.high;
                                
                                // Tentukan kategori loan size
                                let loanSizeCategory = '';
                                for (let bucket of loanSizeBuckets) {
                                    if (app.limit >= bucket.min && app.limit <= bucket.max) {
                                        loanSizeCategory = bucket.label;
                                        break;
                                    }
                                }
                                
                                // Tentukan kategori TAT
                                let tatCategory = "";
                                let tatCategoryDesc = "";
                                switch(context.datasetIndex) {
                                    case 0: 
                                        tatCategory = "< Q1";
                                        tatCategoryDesc = `Termasuk dalam 25% tercepat (TAT < ${globalStats.q1.toFixed(1)} days)`;
                                        break;
                                    case 1: 
                                        tatCategory = "Q1 - Median";
                                        tatCategoryDesc = `Termasuk dalam 25-50% (${globalStats.q1.toFixed(1)} <= TAT < ${globalStats.med.toFixed(1)} days)`;
                                        break;
                                    case 2: 
                                        tatCategory = "Median - Q3";
                                        tatCategoryDesc = `Termasuk dalam 50-75% (${globalStats.med.toFixed(1)} <= TAT < ${globalStats.q3.toFixed(1)} days)`;
                                        break;
                                    case 3: 
                                        tatCategory = "Q3 - Boundary";
                                        tatCategoryDesc = `Termasuk dalam 75%-Boundary (${globalStats.q3.toFixed(1)} <= TAT <= ${boundary.toFixed(1)} days)`;
                                        break;
                                    case 4: 
                                        tatCategory = "Outlier";
                                        tatCategoryDesc = `OUTLIER (TAT > ${boundary.toFixed(1)} days)`;
                                        break;
                                }
                                
                                return [
                                    `App ID: ${app.id}`,
                                    `Branch: ${app.branch}`,
                                    `Purpose: ${app.purp}`,
                                    `Limit: ${formatIDR(app.limit)} (${loanSizeCategory})`,
                                    `TAT: ${app.tat.toFixed(1)} days`,
                                    `TAT Category: ${tatCategory}`,
                                    tatCategoryDesc,
                                    `Outlier Boundary: ${globalStats.q3.toFixed(1)} + 1.5x${iqr.toFixed(1)} = ${boundary.toFixed(1)} days`
                                ];
                            }
                        }
                    },
                    legend: {
                        display: true,
                        position: 'top',
                        onClick: function(e, legendItem, legend) {
                            return; // Nonaktifkan toggle legend
                        },
                        labels: {
                            boxWidth: 12,
                            padding: 10,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });
    }

    // PERBAIKAN: Ganti parameter 'stats' menjadi 'globalStats'
    function calculateQuartileApplications(e2eData, globalStats) {
        q1Apps = [];
        medianApps = [];
        q3Apps = [];
        
        const q1Range = globalStats.q1;
        const medianRange = globalStats.med;
        const q3Range = globalStats.q3;
        
        const tolerance = Math.max(0.5, q1Range * 0.1);
        
        q1Apps = e2eData.filter(app => Math.abs(app.tat - q1Range) <= tolerance);
        medianApps = e2eData.filter(app => Math.abs(app.tat - medianRange) <= tolerance);
        q3Apps = e2eData.filter(app => Math.abs(app.tat - q3Range) <= tolerance);
        
        console.log(`Q1 Apps: ${q1Apps.length}, Median Apps: ${medianApps.length}, Q3 Apps: ${q3Apps.length}`);
    }

    function calcStats(arr) {
        if(!arr.length) return { avg:0, med:0, q1:0, q3:0, out:0, high:0, mod: 0, iqr: 0, boundary: 0 };
        const s = [...arr].sort((a,b)=>a-b);
        const q = p => { let i=(s.length-1)*p, b=Math.floor(i), r=i-b; return s[b] + r*(s[b+1]?s[b+1]-s[b]:0); };
        const q1=q(0.25), q3=q(0.75), iqr=q3-q1;
        const avg = arr.reduce((a,b)=>a+b,0)/arr.length;
        
        const f = {}; let mf = 0; 
        arr.forEach(x => { let k = Math.round(x); f[k] = (f[k] || 0) + 1; if(f[k] > mf) mf = f[k]; });
        const mod = Object.keys(f).filter(k => f[k] == mf)[0] || 0;
        
        const boundary = q3 + 1.5 * iqr;

        return { 
            avg, 
            med: q(0.5), 
            q1, 
            q3, 
            iqr: iqr,
            high: boundary, 
            boundary: boundary,
            out: arr.filter(x=>x > boundary).length, 
            mod 
        };
    }

    function createTopBottleneckChart(slowData, totalStatusTatAll) {
        if(charts.cSlow) charts.cSlow.destroy();
        document.getElementById('tatImpactTotal').textContent = `TAT Impact (Total: ${totalStatusTatAll.toFixed(1)} days)`;

        charts.cSlow = new Chart(document.getElementById('cSlow'), {
            type: 'bar',
            data: {
                labels: slowData.map(x => x.stat),
                datasets: [
                    {
                        type: 'bar',
                        label: 'Avg TAT (hari)',
                        data: slowData.map(x => x.avg),
                        backgroundColor: '#ef4444',
                        borderColor: '#ef4444',
                        borderWidth: 1,
                        order: 2,
                        yAxisID: 'y'
                    },
                    {
                        type: 'line',
                        label: 'TAT Impact (%)',
                        data: slowData.map(x => x.impactPct),
                        borderColor: '#22c55e',
                        backgroundColor: '#22c55e',
                        yAxisID: 'y1',
                        order: 1,
                        fill: false,
                        borderWidth: 3,
                        tension: 0.3,
                        pointRadius: 4,
                        pointBorderWidth: 2,
                        pointBackgroundColor: '#22c55e',
                        pointBorderColor: '#166534',
                        pointHoverRadius: 5
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                onClick: (evt, elements) => {
                    if(elements.length > 0) {
                        const status = slowData[elements[0].index].stat;
                        showBottleneckDetail(status);
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Avg TAT (hari)' }
                    },
                    y1: {
                        position: 'right',
                        beginAtZero: true,
                        grid: { drawOnChartArea: false },
                        title: { display: true, text: 'TAT Impact (%)' },
                        ticks: {
                            callback: function(value) { return `${value}%`; }
                        }
                    }
                },
                plugins: {
                    legend: { display: true },
                    tooltip: {
                        callbacks: {
                            afterBody: function(ctx) {
                                const item = slowData[ctx[0].dataIndex];
                                return [
                                    `Cases: ${item.cases.toLocaleString()}`,
                                    `Total durasi status: ${item.totalTat.toFixed(1)} days`
                                ];
                            }
                        }
                    }
                }
            }
        });
    }

    function getOrderedFlowEvents(flowObj) {
        if (!flowObj || !flowObj.events || !flowObj.events.length) return [];
        const events = [...flowObj.events];
        events.sort((a, b) => {
            if (a.startMs !== null && b.startMs !== null) return a.startMs - b.startMs;
            if (a.startMs !== null) return -1;
            if (b.startMs !== null) return 1;
            return a.seq - b.seq;
        });
        return events;
    }

    function aggregateFlowEventsByStatusAndCompleteDate(events) {
        if (!events || !events.length) return [];
        const order = [];
        const grouped = {};
        events.forEach(ev => {
            const status = String(ev.status || 'Unknown').trim() || 'Unknown';
            const dateKey = (ev.completeKey && String(ev.completeKey).trim()) || null;
            const stepCount = Number.isFinite(Number(ev.count)) ? Number(ev.count) : 1;
            if (!dateKey) {
                order.push({
                    status,
                    completeMs: null,
                    duration: safeNum(ev.duration, 0),
                    count: stepCount,
                    seq: ev.seq
                });
                return;
            }
            const key = `${status}##${dateKey}`;
            if (!grouped[key]) {
                grouped[key] = {
                    status,
                    completeMs: ev.completeMs,
                    completeKey: dateKey,
                    duration: 0,
                    count: 0,
                    seq: ev.seq
                };
                order.push(grouped[key]);
            }
            grouped[key].duration += safeNum(ev.duration, 0);
            grouped[key].count += stepCount;
            if (ev.seq < grouped[key].seq) grouped[key].seq = ev.seq;
            if (Number.isFinite(ev.completeMs) && (!Number.isFinite(grouped[key].completeMs) || ev.completeMs < grouped[key].completeMs)) {
                grouped[key].completeMs = ev.completeMs;
            }
        });
        const sorted = order.sort((a, b) => {
            if (a.completeMs !== null && b.completeMs !== null) return a.completeMs - b.completeMs;
            if (a.completeMs !== null) return -1;
            if (b.completeMs !== null) return 1;
            return a.seq - b.seq;
        });
        const merged = [];
        sorted.forEach(ev => {
            const curr = {
                status: ev.status || 'Unknown',
                statusList: [ev.status || 'Unknown'],
                completeMs: ev.completeMs,
                duration: safeNum(ev.duration, 0),
                count: safeNum(ev.count, 1),
                seq: ev.seq
            };
            if (!merged.length) {
                merged.push(curr);
                return;
            }
            const prev = merged[merged.length - 1];
            const gapDays = getGapDays(prev.completeMs, curr.completeMs);
            if (gapDays === 0) {
                prev.duration += curr.duration;
                prev.count += curr.count;
                if (Number.isFinite(curr.completeMs) && (!Number.isFinite(prev.completeMs) || curr.completeMs > prev.completeMs)) {
                    prev.completeMs = curr.completeMs;
                }
                if (curr.seq < prev.seq) prev.seq = curr.seq;
                curr.statusList.forEach(st => {
                    if (!prev.statusList.includes(st)) prev.statusList.push(st);
                });
                prev.status = prev.statusList.join(' + ');
                return;
            }
            merged.push(curr);
        });
        return merged;
    }

    function bucketLabelForBottleneckLoan(limitValue) {
        for (const bucket of bottleneckLoanSizeBuckets) {
            if (limitValue >= bucket.min && limitValue < bucket.max) return bucket.label;
        }
        return '>30M';
    }

    function safeNum(value, fallback = 0) {
        const n = Number(value);
        return Number.isFinite(n) ? n : fallback;
    }

    function cloneFlowEventMap(source) {
        const out = {};
        Object.keys(source || {}).forEach(id => {
            const flow = source[id] || {};
            out[id] = {
                id: flow.id,
                branch: flow.branch,
                mon: flow.mon,
                displayMon: flow.displayMon,
                events: (flow.events || []).map(ev => ({ ...ev }))
            };
        });
        return out;
    }

    function escapeHtml(text) {
        return String(text || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function renderAvgStepPerStatusChart() {
        const statAgg = {};
        Object.values(appFlowEvents).forEach(flow => {
            const cntByStatus = {};
            (flow.events || []).forEach(ev => {
                const st = String(ev.status || 'Unknown').trim() || 'Unknown';
                const stepCount = Number.isFinite(Number(ev.count)) ? Number(ev.count) : 1;
                cntByStatus[st] = (cntByStatus[st] || 0) + stepCount;
            });
            Object.keys(cntByStatus).forEach(st => {
                if (!statAgg[st]) statAgg[st] = { totalStep: 0, appCount: 0 };
                statAgg[st].totalStep += cntByStatus[st];
                statAgg[st].appCount += 1;
            });
        });

        const rows = Object.keys(statAgg).map(st => {
            const item = statAgg[st];
            const avgStep = item.appCount > 0 ? item.totalStep / item.appCount : 0;
            return { status: st, avgStep, totalStep: item.totalStep, appCount: item.appCount };
        }).sort((a, b) => b.avgStep - a.avgStep);
        const totalStepAll = rows.reduce((sum, r) => sum + r.totalStep, 0);

        const topRows = rows.slice(0, 12);
        topRows.forEach(r => {
            r.impactPct = totalStepAll > 0 ? (r.totalStep / totalStepAll) * 100 : 0;
        });
        const impactCaptured = topRows.reduce((sum, r) => sum + r.impactPct, 0);
        const infoEl = document.getElementById('avgStepStatusInfo');
        if (infoEl) {
            infoEl.textContent = rows.length
                ? `Showing Top ${topRows.length} status dari ${rows.length} status. Average is calculated per App ID that has that status.`
                : 'No data yet status.';
        }
        const impactEl = document.getElementById('stepImpactInfo');
        if (impactEl) {
            impactEl.textContent = rows.length ? `Impact Captured: ${impactCaptured.toFixed(1)}%` : '';
        }

        const canvas = document.getElementById('cAvgStepStatus');
        if (!canvas) return;
        if (charts.cAvgStepStatus) charts.cAvgStepStatus.destroy();
        if (!topRows.length) return;

        charts.cAvgStepStatus = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: topRows.map(x => x.status),
                datasets: [
                    {
                        label: 'Avg Step / App ID',
                        data: topRows.map(x => Number(x.avgStep.toFixed(2))),
                        backgroundColor: '#2563eb',
                        borderRadius: 6,
                        maxBarThickness: 36,
                        yAxisID: 'y'
                    },
                    {
                        type: 'line',
                        label: 'Impact (%)',
                        data: topRows.map(x => Number(x.impactPct.toFixed(2))),
                        borderColor: '#16a34a',
                        backgroundColor: '#16a34a',
                        borderWidth: 2,
                        tension: 0.25,
                        pointRadius: 3,
                        pointHoverRadius: 4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Avg Step' }
                    },
                    x: {
                        ticks: { maxRotation: 35, minRotation: 0 }
                    },
                    y1: {
                        position: 'right',
                        beginAtZero: true,
                        grid: { drawOnChartArea: false },
                        title: { display: true, text: 'Impact (%)' },
                        ticks: {
                            callback: function(value) { return `${value}%`; }
                        }
                    }
                },
                plugins: {
                    legend: { display: true },
                    tooltip: {
                        callbacks: {
                            afterBody: function(ctx) {
                                const row = topRows[ctx[0].dataIndex];
                                return [
                                    `Total Step: ${row.totalStep.toLocaleString()}`,
                                    `Jumlah App ID: ${row.appCount.toLocaleString()}`,
                                    `Impact: ${row.impactPct.toFixed(2)}%`
                                ];
                            }
                        }
                    }
                }
            }
        });
    }

    function getRepeatStatusSummary(appId) {
        const flow = appFlowEvents[appId];
        if (!flow || !flow.events || !flow.events.length) {
            return [];
        }
        const counts = {};
        const tatTotals = {};
        flow.events.forEach(ev => {
            const st = String(ev.status || 'Unknown').trim() || 'Unknown';
            const stepCount = Number.isFinite(Number(ev.count)) ? Number(ev.count) : 1;
            counts[st] = (counts[st] || 0) + stepCount;
            tatTotals[st] = (tatTotals[st] || 0) + safeNum(ev.duration, 0);
        });
        return Object.entries(counts)
            .map(([status, cnt]) => ({
                status,
                step: cnt,
                repeat: cnt,
                tatTotal: tatTotals[status] || 0
            }))
            .filter(x => x.step > 0)
            .sort((a, b) => b.step - a.step || b.tatTotal - a.tatTotal);
    }

    function getStepPriority(totalTat) {
        const s = safeNum(totalTat, 0);
        if (s > 5) return { label: 'High', className: 'bg-danger' };
        if (s > 3) return { label: 'Medium', className: 'bg-warning text-dark' };
        return { label: 'Low', className: 'bg-success' };
    }

    function formatDateDisplay(dateMs) {
        if (!dateMs) return '-';
        try {
            const d = new Date(dateMs);
            if (isNaN(d.getTime())) return '-';
            const dd = String(d.getDate()).padStart(2, '0');
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const yyyy = d.getFullYear();
            return `${dd}/${mm}/${yyyy}`;
        } catch (_) {
            return '-';
        }
    }

    function getGapDays(fromMs, toMs) {
        if (!Number.isFinite(fromMs) || !Number.isFinite(toMs)) return null;
        const diff = (toMs - fromMs) / 86400000;
        if (!Number.isFinite(diff) || diff < 0) return 0;
        return Math.round(diff);
    }

    function renderManagementActionBoard(stMetrics) {
        const tableEl = document.getElementById('mgmtActionTable');
        const summaryEl = document.getElementById('mgmtActionSummary');
        const recoEl = document.getElementById('mgmtActionReco');
        const kpiHighEl = document.getElementById('mgmtKpiHigh');
        const kpiImpactEl = document.getElementById('mgmtKpiImpact');
        const kpiDelayEl = document.getElementById('mgmtKpiDelay');
        if (!tableEl || !summaryEl || !recoEl) return;
        if (!stMetrics || !stMetrics.length) {
            tableEl.innerHTML = `<tr><td colspan="7" class="text-center text-muted">No data yet</td></tr>`;
            summaryEl.textContent = 'Menunggu data...';
            recoEl.innerHTML = `<div class="text-muted">No recommendations yet.</div>`;
            if (kpiHighEl) kpiHighEl.textContent = '-';
            if (kpiImpactEl) kpiImpactEl.textContent = '-';
            if (kpiDelayEl) kpiDelayEl.textContent = '-';
            return;
        }

        const totalTat = stMetrics.reduce((sum, x) => sum + safeNum(x.totalTat, 0), 0);
        const stepAgg = {};
        Object.values(appFlowEvents).forEach(flow => {
            const perStatus = {};
            (flow.events || []).forEach(ev => {
                const st = String(ev.status || 'Unknown').trim() || 'Unknown';
                const stepCount = Number.isFinite(Number(ev.count)) ? Number(ev.count) : 1;
                perStatus[st] = (perStatus[st] || 0) + stepCount;
            });
            Object.entries(perStatus).forEach(([st, cnt]) => {
                if (!stepAgg[st]) stepAgg[st] = { totalStep: 0, appCount: 0 };
                stepAgg[st].totalStep += cnt;
                stepAgg[st].appCount += 1;
            });
        });

        const rows = stMetrics.map(x => {
            const s = stepAgg[x.stat] || { totalStep: 0, appCount: 0 };
            const avgStep = s.appCount > 0 ? s.totalStep / s.appCount : 0;
            const impactPct = totalTat > 0 ? (safeNum(x.totalTat, 0) / totalTat) * 100 : 0;
            let priority = 'Low';
            let priorityClass = 'bg-success';
            if (impactPct > 8) {
                priority = 'High';
                priorityClass = 'bg-danger';
            } else if (impactPct > 5) {
                priority = 'Medium';
                priorityClass = 'bg-warning text-dark';
            }
            return {
                status: x.stat,
                impactPct,
                appCount: safeNum(x.cases, 0),
                avgTat: safeNum(x.avg, 0),
                avgStep,
                priority,
                priorityClass
            };
        }).sort((a, b) => b.impactPct - a.impactPct);

        const eligibleRows = rows.filter(r => r.impactPct > 5);
        const top5 = eligibleRows.slice(0, 5);
        const top5Impact = top5.reduce((sum, r) => sum + r.impactPct, 0);
        const top5Delay = top5.length ? top5.reduce((sum, r) => sum + r.avgTat, 0) / top5.length : 0;
        tableEl.innerHTML = top5.length ? top5.map((r, i) => `
            <tr class="${r.priority === 'High' ? 'table-danger' : (r.priority === 'Medium' ? 'table-warning' : '')}">
                <td>${i + 1}</td>
                <td class="fw-bold">${r.status}</td>
                <td class="text-end impact-cell">
                    <div>${r.impactPct.toFixed(1)}%</div>
                    <div class="impact-track"><div class="impact-bar" style="width:${Math.min(100, r.impactPct)}%"></div></div>
                </td>
                <td class="text-end">${r.appCount.toLocaleString()}</td>
                <td class="text-end">${r.avgTat.toFixed(1)} days</td>
                <td class="text-end">${r.avgStep.toFixed(2)}x</td>
                <td class="text-center"><span class="badge ${r.priorityClass}">${r.priority}</span></td>
            </tr>
        `).join('') : `<tr><td colspan="7" class="text-center text-muted">No data yet</td></tr>`;

        const highCount = eligibleRows.filter(r => r.priority === 'High').length;
        const totalApp = e2eData.length || 0;
        summaryEl.innerHTML = `
            <span class="badge bg-danger me-1">High: ${highCount}</span>
            <span class="badge bg-primary me-1">Impact >5%: ${eligibleRows.length}</span>
            <span class="badge bg-secondary">Total App: ${totalApp.toLocaleString()}</span>
        `;
        if (kpiHighEl) kpiHighEl.textContent = highCount.toLocaleString();
        if (kpiImpactEl) kpiImpactEl.textContent = `${top5Impact.toFixed(1)}%`;
        if (kpiDelayEl) kpiDelayEl.textContent = `${top5Delay.toFixed(1)} days`;

        const topImpact3 = [...eligibleRows].sort((a, b) => b.impactPct - a.impactPct).slice(0, 3);
        const recos = topImpact3.map((r, idx) => {
            const act = r.avgStep >= 2
                ? 'perbaiki loop approval dan return handling'
                : (r.avgTat > globalStats.q3 ? 'percepat SLA internal dan escalation' : 'monitor mingguan');
            const tone = r.priority === 'High' ? 'high' : (r.priority === 'Medium' ? 'medium' : 'low');
            return `
                <div class="playbook-item ${tone}">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold">${idx + 1}. ${r.status}</span>
                        <span class="badge ${r.priorityClass}">${r.priority}</span>
                    </div>
                    <div>Impact ${r.impactPct.toFixed(1)}% | Avg TAT ${r.avgTat.toFixed(1)} days | Avg Step ${r.avgStep.toFixed(2)}x</div>
                    <div class="mt-1">Aksi: <span class="text-primary fw-semibold">${act}</span></div>
                </div>
            `;
        });
        recoEl.innerHTML = recos.length ? recos.join('') : `<div class="text-muted">No status with impact > 5%.</div>`;
    }

    function initActionSimulator(stMetrics) {
        const container = document.getElementById('simStatusControls');
        if (!container || !stMetrics || !stMetrics.length) return;
        const totalTat = stMetrics.reduce((sum, x) => sum + safeNum(x.totalTat, 0), 0);
        const sorted = [...stMetrics]
            .map(x => ({ ...x, impactPct: totalTat > 0 ? (safeNum(x.totalTat, 0) / totalTat) * 100 : 0 }))
            .sort((a, b) => b.impactPct - a.impactPct);
        simImpactByStatus = {};
        sorted.forEach(x => { simImpactByStatus[x.stat] = x.impactPct; });
        simStatusOrder = sorted.map(x => x.stat);
        simReductionByStatus = {};
        simStatusOrder.forEach(status => {
            simReductionByStatus[status] = 0;
        });
        container.dataset.key = '';
        ensureSimStatusControls(simStatusOrder);
        recalcActionSimulator();
    }

    function getStatusSimulationBase(status) {
        const boundary = safeNum((statusStats[status] || {}).high, globalStats.high);
        let appCount = 0;
        let baselineTat = 0;
        let breachBase = 0;
        const perApp = [];

        Object.values(baseAppFlowEvents || {}).forEach(flow => {
            const hits = getOrderedFlowEvents(flow).filter(ev => String(ev.status || '').trim() === status);
            if (!hits.length) return;
            const durations = hits.map(ev => safeNum(ev.duration, 0));
            const totalTat = durations.reduce((s, n) => s + safeNum(n, 0), 0);
            appCount++;
            baselineTat += totalTat;
            if (totalTat > boundary) breachBase++;
            perApp.push({ totalTat });
        });

        return { status, boundary, appCount, baselineTat, breachBase, perApp };
    }

    function renderActionSimChart(baseAvg, projAvg, breachBase, breachProj) {
        const cv = document.getElementById('cActionSim');
        if (!cv) return;
        if (charts.cActionSim) charts.cActionSim.destroy();
        charts.cActionSim = new Chart(cv, {
            type: 'bar',
            data: {
                labels: ['Avg TAT Portfolio', 'SLA Breach Case'],
                datasets: [
                    {
                        label: 'Baseline',
                        data: [Number(baseAvg.toFixed(2)), breachBase],
                        backgroundColor: '#94a3b8'
                    },
                    {
                        label: 'Proyeksi',
                        data: [Number(projAvg.toFixed(2)), breachProj],
                        backgroundColor: '#b45353'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } },
                plugins: { legend: { display: true } }
            }
        });
    }

    function renderSimStatusControlsInto(container, statusList) {
        if (!container) return;
        const key = statusList.join('||');
        if (container.dataset.key === key) return;
        container.dataset.key = key;
        if (!statusList.length) {
            container.innerHTML = `<div class="small text-muted p-2">Status belum tersedia.</div>`;
            return;
        }
        container.innerHTML = statusList.map(status => {
            const val = safeNum(simReductionByStatus[status], 0);
            const impactPct = safeNum(simImpactByStatus[status], 0);
            return `
                <div class="sim-row">
                    <div class="sim-status-main">
                        <span class="sim-status-name" title="${status}">${status}</span>
                        <span class="sim-impact-chip">${impactPct.toFixed(1)}%</span>
                    </div>
                    <div class="sim-slider-wrap">
                        <input type="range" class="form-range form-range-sm sim-slider" min="0" max="60" step="5" value="${val}" data-status="${status}" oninput="onSimStatusReductionInput(this)">
                    </div>
                    <div class="sim-pct-badge sim-pct">${val}%</div>
                </div>
            `;
        }).join('');
    }

    function ensureSimStatusControls(statusList) {
        renderSimStatusControlsInto(document.getElementById('simStatusControls'), statusList);
        renderSimStatusControlsInto(document.getElementById('simStatusControlsFloating'), statusList);
    }

    function onSimStatusReductionInput(el) {
        const status = el.getAttribute('data-status') || '';
        const val = safeNum(el.value, 0);
        if (status) simReductionByStatus[status] = val;
        document.querySelectorAll(`#simStatusControls input[data-status="${status}"], #simStatusControlsFloating input[data-status="${status}"]`).forEach(input => {
            input.value = val;
            const row = input.closest('.sim-row');
            const pct = row ? row.querySelector('.sim-pct') : null;
            if (pct) pct.textContent = `${val}%`;
        });
        recalcActionSimulator();
    }

    function recalcActionSimulator() {
        const simulatedStatuses = (simStatusOrder || []).filter(Boolean);
        if (!simulatedStatuses.length) {
            document.getElementById('simInsight').textContent = 'Belum ada status untuk disimulasikan.';
            return;
        }

        let totalBaselineTat = 0;
        let totalProjectedTat = 0;
        let totalCase = 0;
        let breachBase = 0;
        let breachProj = 0;

        simulatedStatuses.forEach(status => {
            const base = getStatusSimulationBase(status);
            const reductionPct = safeNum(simReductionByStatus[status], 0);
            const r = reductionPct / 100;
            const projectedTat = base.baselineTat * (1 - r);
            totalBaselineTat += base.baselineTat;
            totalProjectedTat += projectedTat;
            totalCase += base.appCount;
            breachBase += base.breachBase;
            base.perApp.forEach(x => {
                const projTat = x.totalTat * (1 - r);
                if (projTat > base.boundary) breachProj++;
            });
        });

        const tatSaved = totalBaselineTat - totalProjectedTat;
        const portfolioDelta = (e2eData.length > 0) ? tatSaved / e2eData.length : 0;
        const currentPortfolioAvg = safeNum((baseGlobalStats || {}).avg, safeNum(globalStats.avg, 0));
        const projectedPortfolioAvg = Math.max(0, currentPortfolioAvg - portfolioDelta);

        const id = (x) => document.getElementById(x);
        if (id('simBaseAvgTat')) id('simBaseAvgTat').textContent = `${currentPortfolioAvg.toFixed(1)} days`;
        if (id('simProjAvgTat')) id('simProjAvgTat').textContent = `${projectedPortfolioAvg.toFixed(1)} days`;
        if (id('simTatSaved')) id('simTatSaved').textContent = `${tatSaved.toFixed(1)} days`;
        if (id('simPortfolioDelta')) id('simPortfolioDelta').textContent = `-${portfolioDelta.toFixed(2)} days`;
        if (id('simBreachBase')) id('simBreachBase').textContent = breachBase.toLocaleString();
        if (id('simBreachProj')) id('simBreachProj').textContent = breachProj.toLocaleString();
        const reduced = simulatedStatuses
            .filter(s => safeNum(simReductionByStatus[s], 0) > 0)
            .map(s => ({ status: s, pct: safeNum(simReductionByStatus[s], 0) }));
        simLastSnapshot = {
            baselineAvg: currentPortfolioAvg,
            projectedAvg: projectedPortfolioAvg,
            tatSaved,
            portfolioDelta,
            breachBase,
            breachProj,
            reduced
        };
        if (id('simInsight')) {
            const breachDrop = breachBase - breachProj;
            const metricSaved = `<span class="sim-insight-metric">${tatSaved.toFixed(1)} hari</span>`;
            const metricDelta = `<span class="sim-insight-metric">${portfolioDelta.toFixed(2)} hari/app</span>`;
            const metricBreach = `<span class="sim-insight-metric">${breachDrop.toLocaleString()} case</span>`;
            if (!reduced.length) {
                id('simInsight').innerHTML =
                    `Belum ada status yang diturunkan (>0%): pengurangan loop gabungan berpotensi menghemat ${metricSaved}, menurunkan avg portfolio ${metricDelta}, dan mengurangi breach ${metricBreach}.`;
            } else {
                const allBadges = reduced.map(x =>
                    `<span class="sim-insight-badge">${escapeHtml(x.status)}:${x.pct}%</span>`
                ).join('');
                id('simInsight').innerHTML =
                    `Status diturunkan ${allBadges}: pengurangan loop gabungan berpotensi menghemat ${metricSaved}, menurunkan avg portfolio ${metricDelta}, dan mengurangi breach ${metricBreach}.`;
            }
        }

        renderActionSimChart(currentPortfolioAvg, projectedPortfolioAvg, breachBase, breachProj);
        applySimulationToDashboard();
    }

    function updateActionSimulator() {
        ensureSimStatusControls(simStatusOrder || []);
        recalcActionSimulator();
    }

    function buildSimulatedProjection() {
        const statusAggSim = {};
        const tatSavedByApp = {};
        const flowSource = baseAppFlowEvents || {};
        const flowEventsSim = cloneFlowEventMap(flowSource);
        const appIds = Object.keys(flowEventsSim || {});
        appIds.forEach(appId => {
            const events = getOrderedFlowEvents(flowEventsSim[appId] || {});
            events.forEach(ev => {
                const status = String(ev.status || 'Unknown').trim() || 'Unknown';
                const reductionPct = safeNum(simReductionByStatus[status], 0);
                const originalDur = safeNum(ev.duration, 0);
                let simulatedDur = originalDur;
                if (reductionPct > 0) {
                    simulatedDur = originalDur * (1 - (reductionPct / 100));
                    tatSavedByApp[appId] = (tatSavedByApp[appId] || 0) + (originalDur - simulatedDur);
                }
                ev.duration = simulatedDur;
                const key = `${appId}##${status}`;
                statusAggSim[key] = (statusAggSim[key] || 0) + simulatedDur;
            });
        });

        const e2eSim = (baseE2EData || []).map(app => {
            const saved = safeNum(tatSavedByApp[app.id], 0);
            const tat = Math.max(0, safeNum(app.tat, 0) - saved);
            return { ...app, tat: Math.round(tat * 10) / 10 };
        }).sort((a, b) => a.tat - b.tat);

        const appsSim = {};
        Object.keys(baseAppsById || {}).forEach(id => {
            appsSim[id] = { ...baseAppsById[id] };
        });

        return { e2eSim, statusAggSim, appsSim, flowEventsSim };
    }

    function renderDashboardFromCurrentDataset(appsMap, statusAggMap) {
        appsWithPositiveLimit = e2eData.filter(app => app.limit > 0);
        const totalApps = e2eData.length;
        const appsWithZeroOrNegativeLimit = e2eData.filter(app => app.limit <= 0).length;
        const totalLimit = appsWithPositiveLimit.reduce((sum, app) => sum + app.limit, 0);
        const uniqueMonths = [...new Set(e2eData.map(d=>d.mon))].filter(x=>x!=='Unknown').length || 1;

        globalStats = calcStats(e2eData.map(d=>d.tat));
        globalOutliers = e2eData.filter(d => d.tat > globalStats.high);
        calculateQuartileApplications(e2eData, globalStats);
        prepareChartData(e2eData, globalStats);

        document.getElementById('kTotalApp').innerText = totalApps.toLocaleString();
        document.getElementById('kAvgAppMonth').innerText = (totalApps/uniqueMonths).toFixed(0);
        document.getElementById('kTotalLimit').innerText = formatIDR(totalLimit);

        const limitSubText = document.getElementById('limitSubText');
        const limitInfoBadge = document.getElementById('limitInfoBadge');
        if (appsWithZeroOrNegativeLimit > 0) {
            limitSubText.innerHTML = `Portfolio Exposure`;
            limitInfoBadge.innerHTML = `<span class="limit-info">Only from ${appsWithPositiveLimit.length} apps (${appsWithZeroOrNegativeLimit} apps without limit)</span>`;
        } else {
            limitSubText.innerHTML = `Portfolio Exposure`;
            limitInfoBadge.innerHTML = `<span class="limit-info">All ${totalApps} apps have limits</span>`;
        }

        const avgLimit = appsWithPositiveLimit.length > 0 ? totalLimit / appsWithPositiveLimit.length : 0;
        document.getElementById('kAvgLimit').innerText = formatIDR(avgLimit);
        const avgLimitSubText = document.getElementById('avgLimitSubText');
        const avgLimitInfoBadge = document.getElementById('avgLimitInfoBadge');
        if (appsWithPositiveLimit.length > 0) {
            avgLimitSubText.innerHTML = `Average Ticket Size`;
            avgLimitInfoBadge.innerHTML = `<span class="limit-info">From ${appsWithPositiveLimit.length} apps with limit > 0</span>`;
        } else {
            avgLimitSubText.innerHTML = `Average Ticket Size`;
            avgLimitInfoBadge.innerHTML = `<span class="limit-warning">No applications with limit > 0</span>`;
        }
        document.getElementById('limitCalcInfo').innerHTML =
            `From ${totalApps} applications, ${appsWithPositiveLimit.length} have limit > 0 (${appsWithZeroOrNegativeLimit} apps dengan limit 0 excluded from calculation).`;

        document.getElementById('vAvg').innerText = globalStats.avg.toFixed(1);
        document.getElementById('vMed').innerText = globalStats.med.toFixed(1);
        document.getElementById('vMode').innerText = globalStats.mod;
        document.getElementById('vQ1').innerText = globalStats.q1.toFixed(1);
        document.getElementById('vQ3').innerText = globalStats.q3.toFixed(1);
        document.getElementById('vOut').innerText = globalStats.out;

        const iqrValue = globalStats.q3 - globalStats.q1;
        document.getElementById('vIQR').innerText = iqrValue.toFixed(1);
        document.getElementById('vOutBoundary').innerText = globalStats.high.toFixed(1);
        document.getElementById('infoQ1').innerText = globalStats.q1.toFixed(1);
        document.getElementById('infoQ3').innerText = globalStats.q3.toFixed(1);
        document.getElementById('outlierFormula').innerHTML =
            `${globalStats.q3.toFixed(1)} + 1.5x${iqrValue.toFixed(1)} = ${globalStats.high.toFixed(1)} days`;

        createCharts(e2eData, globalStats, statusAggMap, appsMap, true);
    }

    function applySimulationToDashboard() {
        if (!baseE2EData.length) return;
        const hasReduction = (simStatusOrder || []).some(status => safeNum(simReductionByStatus[status], 0) > 0);
        if (!hasReduction) {
            e2eData = baseE2EData.map(x => ({ ...x })).sort((a, b) => a.tat - b.tat);
            appFlowEvents = cloneFlowEventMap(baseAppFlowEvents || {});
            e2eById = {};
            e2eData.forEach(app => { e2eById[app.id] = app; });
            renderDashboardFromCurrentDataset(baseAppsById, baseStatusAgg);
            refreshOpenSingleAppModalIfNeeded();
            refreshOpenBottleneckModalIfNeeded();
            return;
        }
        const sim = buildSimulatedProjection();
        e2eData = sim.e2eSim;
        appFlowEvents = sim.flowEventsSim;
        e2eById = {};
        e2eData.forEach(app => { e2eById[app.id] = app; });
        renderDashboardFromCurrentDataset(sim.appsSim, sim.statusAggSim);
        refreshOpenSingleAppModalIfNeeded();
        refreshOpenBottleneckModalIfNeeded();
    }

    function refreshOpenSingleAppModalIfNeeded() {
        const modalEl = document.getElementById('singleAppModal');
        if (!modalEl || !modalEl.classList.contains('show')) return;
        const currentId = window.currentSingleApp && window.currentSingleApp.id;
        if (!currentId) return;
        const app = e2eById[currentId] || e2eData.find(x => x.id === currentId);
        if (app) {
            showSingleAppDetail(app, true);
        }
    }

    function refreshOpenBottleneckModalIfNeeded() {
        const modalEl = document.getElementById('bottleneckDetailModal');
        if (!modalEl || !modalEl.classList.contains('show')) return;
        if (!currentBottleneckStatus) return;
        showBottleneckDetail(currentBottleneckStatus, true);
    }

    function resetActionSimulator() {
        const statusList = (simStatusOrder || []).filter(Boolean);
        if (!statusList.length) return;
        statusList.forEach(status => {
            simReductionByStatus[status] = 0;
        });
        document.querySelectorAll('#simStatusControls input[data-status], #simStatusControlsFloating input[data-status]').forEach(input => {
            input.value = 0;
            const row = input.closest('.sim-row');
            const pct = row ? row.querySelector('.sim-pct') : null;
            if (pct) pct.textContent = '0%';
        });
        recalcActionSimulator();
    }

    function saveScenarioSlot() {
        const slotEl = document.getElementById('simScenarioSlot');
        const slot = slotEl ? slotEl.value : 'A';
        const payload = {
            savedAt: new Date().toISOString(),
            reductions: { ...simReductionByStatus }
        };
        localStorage.setItem(`los_sim_scenario_${slot}`, JSON.stringify(payload));
        alert(`Scenario ${slot} saved.`);
    }

    function loadScenarioSlot() {
        const slotEl = document.getElementById('simScenarioSlot');
        const slot = slotEl ? slotEl.value : 'A';
        const rawScenario = localStorage.getItem(`los_sim_scenario_${slot}`);
        if (!rawScenario) {
            alert(`Scenario ${slot} belum ada.`);
            return;
        }
        try {
            const data = JSON.parse(rawScenario);
            const reductions = data && data.reductions ? data.reductions : {};
            (simStatusOrder || []).forEach(status => {
                simReductionByStatus[status] = safeNum(reductions[status], 0);
            });
            ensureSimStatusControls(simStatusOrder || []);
            document.querySelectorAll('#simStatusControls input[data-status], #simStatusControlsFloating input[data-status]').forEach(input => {
                const status = input.getAttribute('data-status') || '';
                const val = safeNum(simReductionByStatus[status], 0);
                input.value = val;
                const row = input.closest('.sim-row');
                const pct = row ? row.querySelector('.sim-pct') : null;
                if (pct) pct.textContent = `${val}%`;
            });
            recalcActionSimulator();
        } catch (_) {
            alert(`Scenario ${slot} tidak valid.`);
        }
    }

    function exportSimulationReport() {
        const snap = simLastSnapshot || {
            baselineAvg: safeNum((baseGlobalStats || {}).avg, 0),
            projectedAvg: safeNum(globalStats.avg, 0),
            tatSaved: 0,
            portfolioDelta: 0,
            breachBase: 0,
            breachProj: 0,
            reduced: []
        };
        const rows = (snap.reduced || []).map(x => `<li>${escapeHtml(x.status)}: ${x.pct}%</li>`).join('');
        const html = `
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Simulation Compare</title>
<style>
body{font-family:Segoe UI,Arial,sans-serif;padding:24px;color:#0f172a}
h2{margin:0 0 6px 0} .muted{color:#64748b;font-size:12px;margin-bottom:14px}
.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px}
.card{border:1px solid #cbd5e1;border-radius:8px;padding:10px}
.k{font-size:11px;color:#64748b;text-transform:uppercase}.v{font-size:24px;font-weight:800}
ul{margin:6px 0 0 18px}
</style></head>
<body>
<h2>LOS Simulation Report (Before vs After)</h2>
<div class="muted">Generated: ${new Date().toLocaleString()}</div>
<div class="grid">
<div class="card"><div class="k">Baseline Avg TAT</div><div class="v">${safeNum(snap.baselineAvg,0).toFixed(2)} days</div></div>
<div class="card"><div class="k">Projected Avg TAT</div><div class="v">${safeNum(snap.projectedAvg,0).toFixed(2)} days</div></div>
<div class="card"><div class="k">Delta Portfolio</div><div class="v">-${safeNum(snap.portfolioDelta,0).toFixed(2)} days/app</div></div>
<div class="card"><div class="k">TAT Saved</div><div class="v">${safeNum(snap.tatSaved,0).toFixed(1)} days</div></div>
<div class="card"><div class="k">SLA Breach Baseline</div><div class="v">${safeNum(snap.breachBase,0).toLocaleString()}</div></div>
<div class="card"><div class="k">SLA Breach Projected</div><div class="v">${safeNum(snap.breachProj,0).toLocaleString()}</div></div>
</div>
<div class="card">
<div class="k">Applied Reductions</div>
${rows ? `<ul>${rows}</ul>` : '<div>No reduction applied.</div>'}
</div>
</body></html>`;
        const w = window.open('', '_blank');
        if (!w) return;
        w.document.open();
        w.document.write(html);
        w.document.close();
        w.focus();
        setTimeout(() => w.print(), 250);
    }

    function mkChart(id, type, l, d, lbl, c, ax='x') {
        if(charts[id]) charts[id].destroy();
        charts[id] = new Chart(document.getElementById(id), {
            type, 
            data: {
                labels: l, 
                datasets: [{
                    label: lbl, 
                    data: d, 
                    backgroundColor: c, 
                    borderColor: c, 
                    tension: 0.3
                }]
            },
            options: {
                indexAxis: ax, 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: {legend: {display: false}}
            }
        });
    }

    function formatIDR(n) {
        if (isNaN(n) || n === 0) return "0";
        if (n < 0) return `-${formatIDR(Math.abs(n))}`;
        if (n >= 1e12) return (n/1e12).toFixed(1) + " T";
        if (n >= 1e9) return (n/1e9).toFixed(1) + " M";
        if (n >= 1e6) return (n/1e6).toFixed(1) + " Jt";
        if (n >= 1e3) return (n/1e3).toFixed(1) + " Rb";
        return n.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 0});
    }

    function showAppsInRange(type, apps) {
        let title = "";
        let infoText = "";
        let headerColor = "";
        
        switch(type) {
            case 'q1':
                title = "Applications in Quartile 1 (25%)";
                infoText = `Showing ${apps.length} applications with TAT around ${document.getElementById('vQ1').innerText} days (Q1)`;
                headerColor = "#3b82f6";
                break;
            case 'median':
                title = "Applications at Median (50%)";
                infoText = `Showing ${apps.length} applications with TAT around ${document.getElementById('vMed').innerText} days (Median)`;
                headerColor = "#8b5cf6";
                break;
            case 'q3':
                title = "Applications in Quartile 3 (75%)";
                infoText = `Showing ${apps.length} applications with TAT around ${document.getElementById('vQ3').innerText} days (Q3)`;
                headerColor = "#f59e0b";
                break;
        }
        
        showDetailModal(type, apps, title, headerColor, infoText);
    }

    function showOutliersModal(type, status = null) {
        let apps = [];
        let title = "";
        let headerColor = "#ef4444";
        let infoText = "";
        
        if(type === 'global') {
            apps = globalOutliers;
            title = "Global Outliers (E2E)";
            infoText = `Showing ${apps.length} applications outlier (TAT > Q3 + 1.5xIQR = ${globalStats.high.toFixed(1)} days)`;
        } else if(type === 'status' && status) {
            apps = statusOutliers[status] || [];
            const stats = statusStats[status] || {};
            const boundary = stats.boundary || globalStats.high;
            title = `Outliers in Status: ${status}`;
            infoText = `Showing ${apps.length} applications outlier for status "${status}" (TAT > ${boundary.toFixed(1)} days)`;
        } else if(type === 'scatter') {
            apps = globalOutliers;
            title = "All Outlier (Scatter Plot)";
            infoText = `Showing ${apps.length} applications outlier based on scatter plot`;
        }
        
        showDetailModal('outlier', apps, title, headerColor, infoText);
    }

    function showSingleAppDetail(app, refreshOnly = false) {
        if (!app) return;
        
        const isOutlier = app.tat > globalStats.high;
        const daysAboveQ3 = app.tat - globalStats.q3;
        const daysAboveOutlierBoundary = app.tat - globalStats.high;
        
        // Calculate percentile
        const sortedTats = e2eData.map(d => d.tat).sort((a, b) => a - b);
        const percentile = ((sortedTats.indexOf(app.tat) / sortedTats.length) * 100).toFixed(1);
        
        // PERBAIKAN: Update modal styling berdasarkan status applications (Normal vs Outlier)
        const modalHeader = document.getElementById('singleAppModalHeader');
        const alertBox = document.getElementById('singleAppAlert');
        const infoCard = document.getElementById('singleAppInfoCard');
        const statCard = document.getElementById('singleAppStatCard');
        const analysisCard = document.getElementById('singleAppAnalysisCard');
        const analysisHeader = document.getElementById('singleAppAnalysisHeader');
        const showAllBtn = document.getElementById('singleAppShowAllBtn');
        
        if (isOutlier) {
            // Styling untuk Outlier
            modalHeader.className = 'modal-header bg-danger text-white';
            modalHeader.querySelector('.modal-title').innerHTML = `<i class="bi bi-exclamation-triangle-fill me-2"></i>Application Detail Outlier`;
            
            alertBox.className = 'alert alert-warning py-2 mb-3';
            alertBox.querySelector('i').className = 'bi bi-exclamation-triangle me-2';
            
            infoCard.className = 'card border-danger h-100';
            infoCard.querySelector('.card-title').className = 'card-title text-danger fw-bold mb-3';
            
            statCard.className = 'card border-warning h-100';
            statCard.querySelector('.card-title').className = 'card-title text-warning fw-bold mb-3';
            
            analysisCard.className = 'card border-info';
            analysisHeader.className = 'card-header bg-info text-white py-2';
            
            showAllBtn.style.display = 'inline-block';
            showAllBtn.innerHTML = `<i class="bi bi-list-ul me-1"></i>Show All Outliers`;
            showAllBtn.onclick = function() { showAllOutliersFromSingle(); };
        } else {
            // Styling untuk Normal
            modalHeader.className = 'modal-header bg-primary text-white';
            modalHeader.querySelector('.modal-title').innerHTML = `<i class="bi bi-file-earmark-text me-2"></i>Application Detail Normal`;
            
            alertBox.className = 'alert alert-info py-2 mb-3';
            alertBox.querySelector('i').className = 'bi bi-info-circle me-2';
            
            infoCard.className = 'card border-primary h-100';
            infoCard.querySelector('.card-title').className = 'card-title text-primary fw-bold mb-3';
            
            statCard.className = 'card border-success h-100';
            statCard.querySelector('.card-title').className = 'card-title text-success fw-bold mb-3';
            
            analysisCard.className = 'card border-secondary';
            analysisHeader.className = 'card-header bg-secondary text-white py-2';
            
            showAllBtn.style.display = 'inline-block';
            showAllBtn.innerHTML = `<i class="bi bi-list-ul me-1"></i>Show All Outliers`;
            showAllBtn.onclick = function() { showAllOutliersFromSingle(); };
        }
        
        // Update modal content
        document.getElementById('singleAppId').textContent = app.id;
        document.getElementById('singleAppBranch').textContent = app.branch;
        document.getElementById('singleAppPurpose').textContent = app.purpOriginal || app.purp;
        document.getElementById('singleAppSegment').textContent = app.seg;
        document.getElementById('singleAppMonth').textContent = app.displayMon || formatMonthDisplay(app.mon);
        document.getElementById('singleAppTat').textContent = `${app.tat.toFixed(1)} days`;
        
        // Format limit dengan status
        const limitClass = app.limit > 0 ? 'limit-positive' : (app.limit < 0 ? 'limit-negative' : 'limit-zero');
        document.getElementById('singleAppLimit').innerHTML = `<span class="${limitClass}">${formatIDR(app.limit)}</span>`;
        
        // PERBAIKAN PENTING: Update status berdasarkan apakah applications outlier atau normal
        if (isOutlier) {
            document.getElementById('singleAppStatus').innerHTML = `<span class="badge status-outlier">Outlier</span>`;
            document.getElementById('singleAppTat').className = 'app-detail-value fw-bold text-danger';
        } else {
            document.getElementById('singleAppStatus').innerHTML = `<span class="badge status-normal">Normal</span>`;
            document.getElementById('singleAppTat').className = 'app-detail-value fw-bold text-primary';
        }
        
        document.getElementById('singleAppVsQ3').textContent = isOutlier ? 
            `${daysAboveQ3.toFixed(1)} days above Q3` : 
            `${Math.abs(daysAboveQ3).toFixed(1)} days below Q3`;
        
        document.getElementById('singleAppVsOutlierBoundary').textContent = isOutlier ? 
            `${daysAboveOutlierBoundary.toFixed(1)} days di atas Boundary` : 
            `${Math.abs(daysAboveOutlierBoundary).toFixed(1)} days di bawah Boundary`;
        
        document.getElementById('singleAppPercentile').textContent = `${percentile}%`;
        document.getElementById('singleAppDaysAboveQ3').textContent = `${isOutlier ? daysAboveQ3.toFixed(0) : '0'} days`;
        document.getElementById('singleAppDaysAboveOutlierBoundary').textContent = `${isOutlier ? daysAboveOutlierBoundary.toFixed(0) : '0'} days`;
        
        // Tentukan kategori berdasarkan kuartil
        let quartileCategory = "";
        if (app.tat < globalStats.q1) {
            quartileCategory = "< Q1 (25% tercepat)";
        } else if (app.tat < globalStats.med) {
            quartileCategory = "Q1 - Median (25-50%)";
        } else if (app.tat < globalStats.q3) {
            quartileCategory = "Median - Q3 (50-75%)";
        } else if (app.tat <= globalStats.high) {
            quartileCategory = "Q3 - Boundary (75%-Boundary)";
        } else {
            quartileCategory = "> Boundary (Outlier)";
        }
        
        // Update analysis text dengan info limit dan status yang benar
        let analysisText = '';
        if (isOutlier) {
            analysisText = `This application is <span class="fw-bold text-danger">OUTLIER</span> (${quartileCategory}) with TAT ${app.tat.toFixed(1)} days (${daysAboveOutlierBoundary.toFixed(1)} days above outlier boundary ${globalStats.high.toFixed(1)} days)`;
        } else {
            analysisText = `This application is <span class="fw-bold text-success">NORMAL</span> (${quartileCategory}) with TAT ${app.tat.toFixed(1)} days (${Math.abs(daysAboveOutlierBoundary).toFixed(1)} days below outlier boundary ${globalStats.high.toFixed(1)} days)`;
        }
        
        // Tambahkan info limit
        if (app.limit > 0) {
            analysisText += ` | Limit: ${formatIDR(app.limit)} <span class="limit-info">(INCLUDED in total portfolio)</span>`;
        } else if (app.limit < 0) {
            analysisText += ` | Limit: ${formatIDR(app.limit)} <span class="text-danger">(EXCLUDED - negative)</span>`;
        } else {
            analysisText += ` | Limit: ${formatIDR(app.limit)} <span class="text-muted">(EXCLUDED - zero)</span>`;
        }
        
        document.getElementById('singleAppAnalysisText').innerHTML = analysisText;
        document.getElementById('singleAppInfoText').innerHTML = analysisText;
        const repeatRows = getRepeatStatusSummary(app.id);
        const totalReturn = repeatRows.reduce((sum, x) => sum + x.step, 0);
        document.getElementById('singleAppRepeatTotal').textContent = `Total Step: ${totalReturn}x`;
        document.getElementById('singleAppRepeatTable').innerHTML = repeatRows.length
            ? repeatRows.map(x => {
                const pr = getStepPriority(x.tatTotal);
                return `
                <tr>
                    <td><span class="repeat-status-chip">${x.status}</span></td>
                    <td class="text-end fw-bold text-warning-emphasis">${x.step}</td>
                    <td class="text-end fw-bold">${x.tatTotal.toFixed(1)} days</td>
                    <td class="text-center"><span class="badge ${pr.className}">${pr.label}</span></td>
                </tr>
                `;
            }).join('')
            : `<tr><td colspan="4" class="text-center text-muted">No repeated statuses</td></tr>`;

        const flowObj = appFlowEvents[app.id];
        const flowEvents = aggregateFlowEventsByStatusAndCompleteDate(getOrderedFlowEvents(flowObj));
        if (flowEvents.length) {
            const timelineHtml = [];
            flowEvents.forEach((ev, idx) => {
                timelineHtml.push(`
                    <div class="flow-step ${safeNum(ev.duration, 0) > 5 ? 'high-tat' : ''}">
                        <div class="step-index">${idx + 1}</div>
                        <div class="step-node">${ev.status || 'Unknown'}</div>
                        <div class="step-meta">
                            Complete: ${formatDateDisplay(ev.completeMs)}<br>
                            TAT (SUM): ${safeNum(ev.duration, 0).toFixed(1)} days${ev.count > 1 ? `<br><span class="text-muted">Gabung ${ev.count} step</span>` : ''}
                        </div>
                    </div>
                `);
                if (idx < flowEvents.length - 1) {
                    const next = flowEvents[idx + 1];
                    const gapDays = getGapDays(ev.completeMs, next.completeMs);
                    if (gapDays !== null) {
                        const gapClass = gapDays > 10 ? 'danger' : (gapDays > 5 ? 'warn' : '');
                        timelineHtml.push(`
                            <div class="flow-gap ${gapClass}">
                                <div class="gap-days">${gapDays} Days</div>
                                <div class="gap-line"></div>
                            </div>
                        `);
                    }
                }
            });
            document.getElementById('singleAppFlowTimeline').innerHTML = timelineHtml.join('');
        } else {
            document.getElementById('singleAppFlowTimeline').innerHTML = `<div class="small text-muted">Flow data is not available yet</div>`;
        }
        
        // Update progress bar visualization
        const normalWidth = 75;
        const q3ToBoundaryWidth = Math.min(20, ((globalStats.high - globalStats.q3) / globalStats.high * 100));
        const outlierWidth = 5;
        
        if (isOutlier) {
            // Untuk outlier, tunjukkan area outlier
            const outlierPosition = Math.min(100, 75 + q3ToBoundaryWidth + ((app.tat - globalStats.high) / globalStats.high * 20));
            document.getElementById('singleAppProgressNormal').style.width = `${normalWidth}%`;
            document.getElementById('singleAppProgressQ3').style.width = `${q3ToBoundaryWidth}%`;
            document.getElementById('singleAppProgressOutlier').style.width = `${outlierPosition - (normalWidth + q3ToBoundaryWidth)}%`;
        } else {
            // Untuk normal, tunjukkan posisi dalam range normal
            const normalPosition = (app.tat / globalStats.high * 75);
            document.getElementById('singleAppProgressNormal').style.width = `${normalPosition}%`;
            document.getElementById('singleAppProgressQ3').style.width = `${q3ToBoundaryWidth}%`;
            document.getElementById('singleAppProgressOutlier').style.width = `0%`;
        }
        
        if (parseFloat(percentile) > 95) {
            document.getElementById('singleAppProgressOutlier').classList.add('progress-bar-striped');
        } else if (parseFloat(percentile) > 75) {
            document.getElementById('singleAppProgressQ3').classList.add('progress-bar-striped');
        }
        
        // Store current app for navigation
        window.currentSingleApp = app;

        if (refreshOnly) {
            return;
        }
        
        // Show modal
        const modalElement = document.getElementById('singleAppModal');
        
        const existingModal = bootstrap.Modal.getInstance(modalElement);
        if (existingModal) {
            existingModal.hide();
        }
        
        const existingBackdrop = document.querySelector('.modal-backdrop');
        if (existingBackdrop) {
            existingBackdrop.remove();
        }
        
        document.body.classList.remove('modal-open');
        document.body.style.overflow = 'auto';
        document.body.style.paddingRight = '0';
        
        const modal = new bootstrap.Modal(modalElement, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
        
        modal.show();
    }

    function showAllOutliersFromSingle() {
        // Tutup modal single app terlebih dahulu
        const singleModal = bootstrap.Modal.getInstance(document.getElementById('singleAppModal'));
        if (singleModal) {
            singleModal.hide();
            
            // Wait until the modal is fully closed before opening the outlier modal
            const singleModalElement = document.getElementById('singleAppModal');
            singleModalElement.addEventListener('hidden.bs.modal', function onHidden() {
                singleModalElement.removeEventListener('hidden.bs.modal', onHidden);
                
                // Hapus backdrop yang mungkin tertinggal
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
                
                // Reset body styles
                document.body.classList.remove('modal-open');
                document.body.style.overflow = 'auto';
                document.body.style.paddingRight = '0';
                
                // Tampilkan modal outlier
                showOutliersModal('global');
            }, { once: true });
        } else {
            // Jika modal tidak ditemukan, langsung tampilkan modal outlier
            showOutliersModal('global');
        }
    }

    function showTrendDetail() {
        const allTrendApps = Object.values(trendData).flatMap(data => data.apps);
        showDetailModal('trend', allTrendApps, "All Applications (Trend)", "#2563eb", 
            `Showing all applications from trend data. Total: ${allTrendApps.length} applications`);
    }

    function showBranchDetail() {
        const allBranchApps = Object.values(branchData).flatMap(data => data.apps);
        showDetailModal('branch', allBranchApps, "All Applications (Branch)", "#06b6d4", "Showing all applications by branch");
    }

    function showTatDistributionDetail() {
        const allDistApps = Object.values(tatDistributionData).flatMap(data => data.apps);
        showDetailModal('distribution', allDistApps, "All Applications (TAT Distribution)", "#8b5cf6", 
            `Showing all applications by TAT distribution. Total: ${allDistApps.length} applications`);
    }

    function showDetailModal(type, apps, title, headerColor = "#2563eb", infoText = "") {
        if (!apps || apps.length === 0) {
            alert("No data to display.");
            return;
        }
        
        currentDetailType = type;
        currentDetailData = apps;
        currentDetailTitle = title;
        
        // Update modal header
        const modalHeader = document.getElementById('detailModalHeader');
        modalHeader.style.backgroundColor = headerColor;
        modalHeader.style.color = "white";
        modalHeader.querySelector('.modal-title').innerHTML = `<i class="bi bi-list-check me-2"></i>${title}`;
        
        // Update info text
        document.getElementById('detailInfoText').innerText = infoText || `Showing ${apps.length} applications`;
        
        // Calculate summary metrics - PERBAIKAN: Hanya hitung limit dari apps with limit > 0
        const totalApps = apps.length;
        const appsWithPositiveLimit = apps.filter(app => app.limit > 0);
        const appsWithZeroOrNegativeLimit = apps.filter(app => app.limit <= 0);
        const avgTat = apps.reduce((sum, app) => sum + safeNum(app.tat, 0), 0) / totalApps;
        const totalLimit = appsWithPositiveLimit.reduce((sum, app) => sum + app.limit, 0);
        const avgLimit = appsWithPositiveLimit.length > 0 ? totalLimit / appsWithPositiveLimit.length : 0;
        
        // Update summary cards dengan info limit yang jelas
        const summaryCardsHTML = `
            <div class="col-md-3">
                <div class="card border-0 bg-light">
                    <div class="card-body p-3 text-center">
                        <div class="small text-muted">Total Applications</div>
                        <div class="h4 fw-bold">${totalApps.toLocaleString()}</div>
                        <div class="small text-muted mt-1">
                            <span class="limit-info">${appsWithPositiveLimit.length} with limit > 0</span><br>
                            <span class="text-muted">${appsWithZeroOrNegativeLimit.length} without limit</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 bg-light">
                    <div class="card-body p-3 text-center">
                        <div class="small text-muted">Rata-rata TAT</div>
                        <div class="h4 fw-bold">${avgTat.toFixed(1)} days</div>
                        <div class="small text-muted mt-1">All applications</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 bg-light">
                    <div class="card-body p-3 text-center">
                        <div class="small text-muted">Total Limit (>0)</div>
                        <div class="h4 fw-bold">${formatIDR(totalLimit)}</div>
                        <div class="small text-muted mt-1">
                            <span class="limit-info">From ${appsWithPositiveLimit.length} apps</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 bg-light">
                    <div class="card-body p-3 text-center">
                        <div class="small text-muted">Avg Limit/App (>0)</div>
                        <div class="h4 fw-bold">${formatIDR(avgLimit)}</div>
                        <div class="small text-muted mt-1">Hanya limit positif</div>
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('detailSummaryCards').innerHTML = summaryCardsHTML;
        
        // Set table headers dengan kolom status limit
        let tableHeaders = '';
        if (type === 'outlier') {
            tableHeaders = `
                <tr>
                    <th>#</th>
                    <th>App ID</th>
                    <th>Branch</th>
                    <th>Purpose</th>
                    <th>Segment</th>
                    <th>Limit</th>
                    <th class="text-end">TAT (Days)</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Action</th>
                </tr>
            `;
        } else {
            tableHeaders = `
                <tr>
                    <th>#</th>
                    <th>App ID</th>
                    <th>Branch</th>
                    <th>Purpose</th>
                    <th>Segment</th>
                    <th>Limit</th>
                    <th class="text-end">TAT (Days)</th>
                    <th>Month</th>
                    <th class="text-center">Action</th>
                </tr>
            `;
        }
        
        document.getElementById('detailTableHeader').innerHTML = tableHeaders;
        
        // Populate table dengan styling limit
        const tbody = document.getElementById('detailTableBody');
        tbody.innerHTML = apps.map((app, index) => {
            let statusCell = '';
            let actionCell = '';
            let rowClass = '';
            
            const isOutlier = globalOutliers.some(o => o.id === app.id);
            const isLimitPositive = app.limit > 0;
            const isLimitZero = app.limit === 0;
            const isLimitNegative = app.limit < 0;
            
            // Determine limit class
            let limitClass = '';
            let limitBadge = '';
            
            if (isLimitPositive) {
                limitClass = 'limit-positive';
                limitBadge = `<span class="limit-info">${formatIDR(app.limit)}</span>`;
            } else if (isLimitNegative) {
                limitClass = 'limit-negative';
                limitBadge = `<span class="badge bg-danger">${formatIDR(app.limit)}</span>`;
            } else {
                limitClass = 'limit-zero';
                limitBadge = `<span class="text-muted">${formatIDR(app.limit)}</span>`;
            }
            
            if (isOutlier) {
                rowClass = 'highlight-outlier';
            }
            
            // Status cell for non-outlier tables
            if (type !== 'outlier') {
                statusCell = `<td>${app.displayMon || formatMonthDisplay(app.mon)}</td>`;
            } else {
                statusCell = `<td class="text-center">
                    <span class="badge ${isOutlier ? 'bg-danger' : 'bg-success'}">
                        ${isOutlier ? 'Outlier' : 'Normal'}
                    </span>
                </td>`;
            }
            
            // Action cell with view button
            actionCell = `
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary" onclick="viewSingleAppDetail('${app.id}')" title="View detail">
                        <i class="bi bi-eye"></i>
                    </button>
                </td>
            `;
            
            return `
                <tr class="${rowClass}">
                    <td>${index + 1}</td>
                    <td><span class="badge bg-light text-dark border">${app.id}</span></td>
                    <td>${app.branch}</td>
                    <td>${app.purpOriginal || app.purp}</td>
                    <td><span class="badge bg-secondary">${app.seg}</span></td>
                    <td class="${limitClass}">${limitBadge}</td>
                    <td class="text-end fw-bold">${safeNum(app.tat, 0).toFixed(1)}</td>
                    ${statusCell}
                    ${actionCell}
                </tr>
            `;
        }).join('');
        
        // Update footer dengan info limit
        const limitInfo = appsWithPositiveLimit.length > 0 ? 
            `Total Limit is calculated only from ${appsWithPositiveLimit.length} applications with limit > 0.` : 
            `No applications with limit > 0 dalam data ini.`;
        
        document.getElementById('detailTableFooter').innerHTML = 
            `Showing ${apps.length} applications. ${limitInfo} Click header to sort, use search to filter, click the eye icon for per-application detail.`;
        
        // Show modal
        const modalElement = document.getElementById('detailModal');
        
        const existingModal = bootstrap.Modal.getInstance(modalElement);
        if (existingModal) {
            existingModal.hide();
        }
        
        const existingBackdrop = document.querySelector('.modal-backdrop');
        if (existingBackdrop) {
            existingBackdrop.remove();
        }
        
        document.body.classList.remove('modal-open');
        document.body.style.overflow = 'auto';
        document.body.style.paddingRight = '0';
        
        const modal = new bootstrap.Modal(modalElement, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
        
        modal.show();
    }

    function closeDetailModal() {
        const modalElement = document.getElementById('detailModal');
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) {
            modal.hide();
        }
        
        setTimeout(() => {
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
            
            document.body.classList.remove('modal-open');
            document.body.style.overflow = 'auto';
            document.body.style.paddingRight = '0';
        }, 300);
    }

    function closeSingleAppModal() {
        const modalElement = document.getElementById('singleAppModal');
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) {
            modal.hide();
        }
        
        setTimeout(() => {
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
            
            document.body.classList.remove('modal-open');
            document.body.style.overflow = 'auto';
            document.body.style.paddingRight = '0';
        }, 300);
    }

    function viewSingleAppDetail(appId) {
        const app = e2eData.find(a => a.id === appId);
        if (app) {
            const detailModal = bootstrap.Modal.getInstance(document.getElementById('detailModal'));
            if (detailModal) detailModal.hide();
            
            setTimeout(() => {
                showSingleAppDetail(app);
            }, 300);
        }
    }

    function filterDetailTable() {
        const searchTerm = document.getElementById('detailSearch').value.toLowerCase();
        const rows = document.getElementById('detailTableBody').getElementsByTagName('tr');
        
        for (let row of rows) {
            const rowText = row.textContent.toLowerCase();
            row.style.display = rowText.includes(searchTerm) ? '' : 'none';
        }
    }

    function exportDetailData() {
        if (!currentDetailData || currentDetailData.length === 0) {
            alert("No data to export.");
            return;
        }
        
        const headers = ["No", "App ID", "Branch", "Purpose", "Segment", "Limit", "Limit Status", "TAT (Days)", "Month", "Outlier Status"];
        
        const csvRows = [
            [currentDetailTitle],
            [`Total Applications: ${currentDetailData.length}`],
            [`Applications with limit > 0: ${currentDetailData.filter(app => app.limit > 0).length}`],
            [],
            headers
        ];
        
        currentDetailData.forEach((app, index) => {
            const isOutlier = globalOutliers.some(o => o.id === app.id);
            const limitStatus = app.limit > 0 ? "Positive (Included)" : 
                               app.limit < 0 ? "Negatif (Tidak Dihitung)" : 
                               "Nol (Tidak Dihitung)";
            
            csvRows.push([
                index + 1,
                app.id,
                app.branch,
                app.purpOriginal || app.purp,
                app.seg,
                app.limit,
                limitStatus,
                app.tat.toFixed(1),
                app.displayMon || formatMonthDisplay(app.mon),
                isOutlier ? "Outlier" : "Normal"
            ]);
        });
        
        const csvContent = csvRows.map(row => row.join(",")).join("\n");
        const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
        const link = document.createElement("a");
        const url = URL.createObjectURL(blob);
        
        link.setAttribute("href", url);
        link.setAttribute("download", `${currentDetailType}_${new Date().toISOString().slice(0,10)}.csv`);
        link.style.visibility = "hidden";
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    function showBottleneckDetail(status, refreshOnly = false) {
        const statusRows = statusDurationBuckets[status] || [];
        if (!statusRows.length) {
            alert(`No data for status "${status}"`);
            return;
        }

        currentBottleneckStatus = status;
        currentBottleneckRows = statusRows.map(x => ({
            id: x.app.id,
            branch: x.app.branch,
            segment: x.app.seg,
            purpose: x.app.purpOriginal || x.app.purp,
            limit: x.app.limit,
            statusTat: x.tat,
            e2eTat: safeNum((e2eById[x.app.id] || x.app).tat, 0)
        }));

        const appMap = {};
        statusRows.forEach(item => {
            const appRef = e2eById[item.app.id] || item.app;
            appMap[item.app.id] = {
                app: appRef,
                statusTat: safeNum(item.tat, 0)
            };
        });
        const appIds = Object.keys(appMap);

        const loanBucketStats = {};
        bottleneckLoanSizeBuckets.forEach(b => { loanBucketStats[b.label] = { sumTat: 0, cnt: 0, sumLoop: 0, breachCount: 0 }; });

        let returnCases = 0;
        let maxLoop = 0;
        let totalLoopForReturnCases = 0;
        let totalExtraTat = 0;

        appIds.forEach(id => {
            const flowObj = appFlowEvents[id];
            const events = getOrderedFlowEvents(flowObj);
            const hits = events.filter(e => e.status === status);
            const occurrences = hits.length;
            const returnCount = Math.max(0, occurrences - 1);
            const isReturn = returnCount > 0;
            const extraTat = hits.slice(1).reduce((sum, e) => sum + safeNum(e.duration, 0), 0);
            if (isReturn) {
                returnCases++;
                totalLoopForReturnCases += occurrences;
                totalExtraTat += extraTat;
                maxLoop = Math.max(maxLoop, occurrences);
            } else {
                maxLoop = Math.max(maxLoop, occurrences);
            }

            const limit = safeNum(appMap[id].app.limit, 0);
            if (limit >= 1e9) {
                const bucketLabel = bucketLabelForBottleneckLoan(limit);
                loanBucketStats[bucketLabel].sumTat += safeNum(appMap[id].statusTat, 0);
                loanBucketStats[bucketLabel].cnt++;
                loanBucketStats[bucketLabel].sumLoop += returnCount;
            }

        });

        const totalCase = appIds.length;
        const reworkRate = totalCase > 0 ? (returnCases / totalCase) * 100 : 0;
        const avgLoopPerCase = returnCases > 0 ? totalLoopForReturnCases / returnCases : 1;
        const avgExtraTat = returnCases > 0 ? totalExtraTat / returnCases : 0;
        const statusAvgTatThreshold = safeNum((statusStats[status] || {}).avg, safeNum(globalStats.avg, 0));

        appIds.forEach(id => {
            const limit = safeNum(appMap[id].app.limit, 0);
            if (limit >= 1e9) {
                const bucketLabel = bucketLabelForBottleneckLoan(limit);
                if (safeNum(appMap[id].statusTat, 0) > statusAvgTatThreshold) {
                    loanBucketStats[bucketLabel].breachCount++;
                }
            }
        });

        document.getElementById('bottleneckStatusName').textContent = status;
        document.getElementById('bottleneckStatusHeader').textContent = status;

        document.getElementById('bottleneckSummaryCards').innerHTML = `
            <div class="col-md-4 col-lg-2"><div class="kpi-card"><div class="kpi-title">Total Case</div><div class="kpi-val">${totalCase.toLocaleString()}</div><div class="kpi-sub">in this status</div></div></div>
            <div class="col-md-4 col-lg-2"><div class="kpi-card"><div class="kpi-title">Case Return</div><div class="kpi-val">${returnCases.toLocaleString()}</div><div class="kpi-sub">mengalami return</div></div></div>
            <div class="col-md-4 col-lg-2"><div class="kpi-card"><div class="kpi-title">Rework Rate</div><div class="kpi-val">${reworkRate.toFixed(1)}%</div><div class="kpi-sub">return / total</div></div></div>
            <div class="col-md-4 col-lg-2"><div class="kpi-card"><div class="kpi-title">Avg Loop</div><div class="kpi-val">${avgLoopPerCase.toFixed(1)}</div><div class="kpi-sub">per case return</div></div></div>
            <div class="col-md-4 col-lg-2"><div class="kpi-card"><div class="kpi-title">Max Loop</div><div class="kpi-val">${maxLoop}</div><div class="kpi-sub">kunjungan status</div></div></div>
            <div class="col-md-4 col-lg-2"><div class="kpi-card"><div class="kpi-title">Avg Tambahan TAT</div><div class="kpi-val">+${avgExtraTat.toFixed(1)}</div><div class="kpi-sub">hari karena return</div></div></div>
        `;

        const loanLabels = bottleneckLoanSizeBuckets.map(b => b.label);
        const avgStatusTatByLoan = loanLabels.map(label => {
            const d = loanBucketStats[label];
            return d.cnt > 0 ? d.sumTat / d.cnt : 0;
        });
        const avgLoopByLoan = loanLabels.map(label => {
            const d = loanBucketStats[label];
            return d.cnt > 0 ? d.sumLoop / d.cnt : 0;
        });

        const tatWithData = avgStatusTatByLoan.filter(v => v > 0);
        const minTat = tatWithData.length ? Math.min(...tatWithData) : 0;
        const maxTat = tatWithData.length ? Math.max(...tatWithData) : 10;
        const yMinTat = Math.max(0, Math.floor(minTat - 1));
        const yMaxTat = Math.ceil(maxTat + 1);

        const slaRows = loanLabels.map(label => {
            const d = loanBucketStats[label];
            const breachPct = d.cnt > 0 ? (d.breachCount / d.cnt) * 100 : 0;
            const avgLoopDist = d.cnt > 0 ? d.sumLoop / d.cnt : 0;
            const avgTat = d.cnt > 0 ? d.sumTat / d.cnt : 0;
            let priority = 'Low';
            let priorityClass = 'bg-success';
            if (d.cnt >= 30 && breachPct >= 15) {
                priority = 'High';
                priorityClass = 'bg-danger';
            } else if (d.cnt >= 30 && breachPct >= 8) {
                priority = 'Medium';
                priorityClass = 'bg-warning text-dark';
            }
            const lowSample = d.cnt > 0 && d.cnt < 10;
            return { label, cnt: d.cnt, breachCount: d.breachCount, breachPct, avgLoopDist, avgTat, priority, priorityClass, lowSample };
        });

        const rowsWithData = slaRows.filter(r => r.cnt > 0);
        const noDataCount = slaRows.length - rowsWithData.length;
        const priorityRank = { High: 0, Medium: 1, Low: 2 };
        rowsWithData.sort((a, b) => {
            const pr = (priorityRank[a.priority] ?? 9) - (priorityRank[b.priority] ?? 9);
            if (pr !== 0) return pr;
            return b.breachPct - a.breachPct;
        });

        document.getElementById('slaImpactTable').innerHTML = rowsWithData.length ? rowsWithData.map(r => `
            <tr class="${r.priority === 'High' ? 'table-danger' : (r.priority === 'Medium' ? 'table-warning' : '')}">
                <td>${r.label}</td>
                <td class="text-end">${r.cnt.toLocaleString()}</td>
                <td class="text-end">${r.avgLoopDist.toFixed(2)}x</td>
                <td class="text-end">${r.avgTat.toFixed(1)} days</td>
                <td class="text-end">${statusAvgTatThreshold.toFixed(1)} days</td>
                <td class="text-end">${r.breachCount}/${r.cnt} (${r.breachPct.toFixed(1)}%) ${r.lowSample ? '<span class="badge bg-secondary ms-1">Low Sample</span>' : ''}</td>
                <td class="text-center"><span class="badge ${r.priorityClass}">${r.priority}</span></td>
            </tr>
        `).join('') : `<tr><td colspan="7" class="text-center text-muted">No SLA data for this status</td></tr>`;

        document.getElementById('slaNoDataInfo').innerHTML = noDataCount > 0
            ? `No-data buckets hidden: ${noDataCount} bucket.`
            : '';

        const totalVisibleCases = rowsWithData.reduce((s, r) => s + r.cnt, 0);
        const totalVisibleBreach = rowsWithData.reduce((s, r) => s + r.breachCount, 0);
        const overallBreachPct = totalVisibleCases > 0 ? (totalVisibleBreach / totalVisibleCases) * 100 : 0;
        const highPriorityCount = rowsWithData.filter(r => r.priority === 'High').length;
        document.getElementById('slaOverallBreach').textContent = `${totalVisibleBreach}/${totalVisibleCases} (${overallBreachPct.toFixed(1)}%)`;
        document.getElementById('slaHighPriority').textContent = highPriorityCount.toLocaleString();
        document.getElementById('slaNoDataCount').textContent = noDataCount.toLocaleString();

        const topBreach = rowsWithData[0];
        const biggestVolume = [...rowsWithData].sort((a, b) => b.cnt - a.cnt)[0];
        let insight = `Avg TAT status ${status}: ${statusAvgTatThreshold.toFixed(1)} days. `;
        if (topBreach) {
            insight += `Breach tertinggi: ${topBreach.label} (${topBreach.breachCount}/${topBreach.cnt}, ${topBreach.breachPct.toFixed(1)}%). `;
        }
        if (biggestVolume) {
            insight += `Volume terbesar: ${biggestVolume.label} (n=${biggestVolume.cnt}, breach ${biggestVolume.breachPct.toFixed(1)}%).`;
        }
        document.getElementById('slaInsightText').textContent = insight;

        if (charts.bottleneckLoanSize) charts.bottleneckLoanSize.destroy();
        charts.bottleneckLoanSize = new Chart(document.getElementById('bottleneckLoanSizeChart'), {
            type: 'bar',
            data: {
                labels: loanLabels,
                datasets: [
                    {
                        label: 'Avg TAT Status (hari)',
                        data: avgStatusTatByLoan,
                        backgroundColor: '#ef4444'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                events: ['click'],
                interaction: { mode: 'nearest', intersect: true },
                plugins: { tooltip: { enabled: false } },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: yMinTat,
                        max: yMaxTat,
                        title: { display: true, text: 'Avg TAT (hari)' }
                    }
                }
            }
        });

        if (charts.bottleneckLoopFreq) charts.bottleneckLoopFreq.destroy();
        charts.bottleneckLoopFreq = new Chart(document.getElementById('bottleneckLoopFreqChart'), {
            type: 'bar',
            data: {
                labels: loanLabels,
                datasets: [{
                    label: 'Avg Loop (x)',
                    data: avgLoopByLoan,
                    backgroundColor: '#0ea5e9'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                events: ['click'],
                interaction: { mode: 'nearest', intersect: true },
                scales: { y: { beginAtZero: true, title: { display: true, text: 'Avg Loop (x)' } } },
                plugins: { legend: { display: false }, tooltip: { enabled: false } }
            }
        });

        const loanCanvas = document.getElementById('bottleneckLoanSizeChart');
        const loopCanvas = document.getElementById('bottleneckLoopFreqChart');
        loanCanvas.onmousedown = (ev) => ev.preventDefault();
        loopCanvas.onmousedown = (ev) => ev.preventDefault();
        loanCanvas.ondragstart = () => false;
        loopCanvas.ondragstart = () => false;

        if (!refreshOnly) {
            new bootstrap.Modal(document.getElementById('bottleneckDetailModal')).show();
        }
    }

    function exportBottleneckData() {
        if (!currentBottleneckRows.length) {
            alert('No data yet bottleneck yang dipilih.');
            return;
        }

        const headers = ['No', 'App ID', 'Branch', 'Purpose', 'Segment', 'Limit', 'TAT Status (Days)', 'TAT E2E (Days)'];
        const csvRows = [headers];
        currentBottleneckRows.forEach((row, idx) => {
            csvRows.push([
                idx + 1,
                row.id,
                row.branch,
                row.purpose,
                row.segment,
                row.limit,
                safeNum(row.statusTat, 0).toFixed(2),
                safeNum(row.e2eTat, 0).toFixed(2)
            ]);
        });

        const csvContent = csvRows.map(r => r.join(',')).join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `bottleneck_${currentBottleneckStatus}_${new Date().toISOString().slice(0,10)}.csv`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }








