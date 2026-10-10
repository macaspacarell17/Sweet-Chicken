/*
 * Sweet Chicken POS - Kitchen Display System behavior
 * Extracted from kitchen_dashboard.php
 */

document.addEventListener('DOMContentLoaded', () => {
    const grid = document.getElementById('kdsOrdersGrid');
    let allOrders = [];
    let filter = 'all';
    let search = '';
    let orderSyncBusy = false;

    // --- TOP BAR INTERACTION LOGIC ---
    
    // Profile Dropdown Toggle
    const profileBtn = document.getElementById('profileBtn');
    const profileMenu = document.getElementById('profileMenu');
    
    profileBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        profileMenu.classList.toggle('active');
        document.getElementById('notificationPanel').classList.remove('open');
    });

    // Notification Panel Toggle
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationPanel = document.getElementById('notificationPanel');
    const closeNotification = document.getElementById('closeNotification');

    notificationBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        notificationPanel.classList.toggle('open');
        profileMenu.classList.remove('active');
    });

    closeNotification?.addEventListener('click', () => {
        notificationPanel.classList.remove('open');
    });

    // Order History Panel Toggle
    const historyBtn = document.getElementById('historyBtn');
    const historyPanel = document.getElementById('historyPanel');
    const closeHistory = document.getElementById('closeHistory');

    historyBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        historyPanel.classList.toggle('open');
        notificationPanel?.classList.remove('open');
        profileMenu?.classList.remove('active');
        if (historyPanel.classList.contains('open')) loadHistory();
    });

    closeHistory?.addEventListener('click', () => {
        historyPanel.classList.remove('open');
    });

    // Close dropdown menus when clicking outside
    document.addEventListener('click', (e) => {
        if (!profileMenu?.contains(e.target) && !profileBtn?.contains(e.target)) {
            profileMenu?.classList.remove('active');
        }
    });

    // Dark Mode Toggle Logic
    const darkModeToggle = document.getElementById('darkModeToggle');
    const darkModeIcon = document.getElementById('darkModeIcon');
    const darkModeText = document.getElementById('darkModeText');

    darkModeToggle?.addEventListener('click', (e) => {
        e.preventDefault();
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        if (isDark) {
            darkModeIcon.className = 'fas fa-sun';
            darkModeText.textContent = 'Light Mode';
        } else {
            darkModeIcon.className = 'fas fa-moon';
            darkModeText.textContent = 'Dark Mode';
        }
    });

    // Fullscreen Toggle Logic
    const fullscreenBtn = document.getElementById('kdsFullscreen');
    fullscreenBtn?.addEventListener('click', () => {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(err => {
                console.error(`Error attempting to enable fullscreen: ${err.message}`);
            });
            fullscreenBtn.innerHTML = '<i class="fa-solid fa-compress"></i>';
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            }
            fullscreenBtn.innerHTML = '<i class="fa-solid fa-expand"></i>';
        }
    });

    // --- ORDER & KDS LOGIC ---

    function money(v){ return '₱' + Number(v||0).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2}); }
    async function loadOrders(){
        try{
            if (orderSyncBusy) return;
            orderSyncBusy = true;
            const r=await fetch('api.php?action=orders&_='+Date.now(), {cache:'no-store'}); const d=await r.json();
            if(!d.success) throw new Error(d.message);
            allOrders=d.orders.filter(o=>o.status==='Preparing');
            render();
            const badge=document.querySelector('.notification span'); if(badge) badge.textContent=allOrders.length;
        }catch(e){ console.error(e); }
        finally { orderSyncBusy = false; }
    }
    function render(){
        const filtered=allOrders.filter(o=>{
            const type=(o.order_type||'').toLowerCase().replace('-',' ');
            const matchesFilter=filter==='all'||type===filter.toLowerCase();
            const matchesSearch=search===''||String(o.id).includes(search)||String(o.order_number).toLowerCase().includes(search);
            return matchesFilter&&matchesSearch;
        });
        grid.innerHTML=filtered.length?'':`<p style="color:#6B7280;padding:20px;grid-column:1/-1;">No pending kitchen orders.</p>`;
        filtered.forEach(o=>{
            const card=document.createElement('div'); card.className='kds-card '+((o.order_type||'online').toLowerCase().replace(/\s+/g,'-')); card.dataset.orderid=o.order_number; card.dataset.ordertype=o.order_type||'Online';
            const action='Mark as Ready';
            card.innerHTML=`<div class="kds-card-header"><strong>#${o.id}</strong><span>${o.status}</span></div>
                <div style="margin:8px 0"><strong>${o.customer_name||o.account_name||'Customer'}</strong><div style="color:#6B7280;font-size:12px;margin-top:4px">${o.items||''}</div></div>
                <div style="font-size:12px;color:#6B7280">${o.order_type||'Online'}${o.table_number?' · Table '+o.table_number:''} · ${money(o.total)}</div>
                <div class="actions"><button class="prepare kds-action-btn" data-id="${o.id}">${action}</button><button class="cancel kds-cancel-btn" data-id="${o.id}">Cancel</button></div>`;
            grid.appendChild(card);
        });
        grid.querySelectorAll('.kds-action-btn').forEach(btn=>btn.onclick=async()=>{
            const order=allOrders.find(x=>x.id==btn.dataset.id); if(!order) return;
            await updateStatus(order.id,'Ready');
        });
        grid.querySelectorAll('.kds-cancel-btn').forEach(btn=>btn.onclick=async()=>{ if(confirm('Cancel this order?')) await updateStatus(btn.dataset.id,'Cancelled'); });
    }
    async function updateStatus(id,status){
        try{ const r=await fetch('api.php?action=update_order_status',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({order_id:Number(id),status})}); const d=await r.json(); if(!d.success) throw new Error(d.message); await loadOrders(); }
        catch(e){ alert(e.message); }
    }

    // --- ORDER HISTORY PANEL ---
    // History shows every order that has moved past the active kitchen queue:
    // Ready (waiting for cashier pickup), Completed, or Cancelled.
    let historyOrders = [];
    let historyFilter = 'all';
    let historySearch = '';
    const historyList = document.getElementById('historyList');

    async function loadHistory(){
        if (!historyList) return;
        historyList.innerHTML = `<p style="color:#6B7280;padding:20px;text-align:center;">Loading order history...</p>`;
        try{
            const r = await fetch('api.php?action=orders&_=' + Date.now(), { cache: 'no-store' });
            const d = await r.json();
            if (!d.success) throw new Error(d.message);
            historyOrders = d.orders.filter(o => o.status !== 'Preparing' && o.status !== 'Pending');
            renderHistory();
        }catch(e){
            historyList.innerHTML = `<p style="color:#DC2626;padding:20px;text-align:center;">Could not load order history.</p>`;
            console.error(e);
        }
    }

    function renderHistory(){
        if (!historyList) return;
        const filtered = historyOrders.filter(o => {
            const matchesFilter = historyFilter === 'all' || o.status === historyFilter;
            const matchesSearch = historySearch === '' || String(o.id).includes(historySearch) || String(o.order_number).toLowerCase().includes(historySearch);
            return matchesFilter && matchesSearch;
        });

        if (filtered.length === 0) {
            historyList.innerHTML = `<p style="color:#6B7280;padding:20px;text-align:center;">No past orders found.</p>`;
            return;
        }

        historyList.innerHTML = '';
        filtered.forEach(o => {
            const card = document.createElement('div');
            card.className = 'history-card';
            card.innerHTML = `
                <div class="history-card-top">
                    <strong>${o.order_number || ('#' + o.id)}</strong>
                    <span class="history-status ${o.status.toLowerCase()}">${o.status}</span>
                </div>
                <div style="font-size:13px;font-weight:600;margin-bottom:2px;">${o.customer_name || o.account_name || 'Customer'}</div>
                <div style="color:#6B7280;font-size:12px;margin-bottom:6px;">${o.items || ''}</div>
                <div style="display:flex;justify-content:space-between;font-size:12px;color:#6B7280;">
                    <span>${o.order_type || 'Online'}${o.table_number ? ' · Table ' + o.table_number : ''}</span>
                    <strong style="color:var(--text);">${money(o.total)}</strong>
                </div>
                <div style="font-size:11px;color:#9CA3AF;margin-top:4px;">${new Date((o.created_at||'').replace(' ','T')).toLocaleString()}</div>
            `;
            historyList.appendChild(card);
        });
    }

    document.querySelectorAll('.history-filter-btn').forEach(btn => btn.addEventListener('click', () => {
        document.querySelectorAll('.history-filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        historyFilter = btn.dataset.historyFilter || 'all';
        renderHistory();
    }));
    document.getElementById('searchHistoryOrder')?.addEventListener('input', e => {
        historySearch = e.target.value.trim().toLowerCase();
        renderHistory();
    });
    document.querySelectorAll('.filter-btn').forEach(btn=>btn.addEventListener('click',()=>{document.querySelectorAll('.filter-btn').forEach(b=>b.classList.remove('active'));btn.classList.add('active');filter=btn.dataset.kdsFilter||'all';render();}));
    document.getElementById('searchKDSOrder')?.addEventListener('input',e=>{search=e.target.value.trim().toLowerCase();render();});
    document.getElementById('menuLogoutLink')?.addEventListener('click',e=>{e.preventDefault();location.href='logout.php';});
    loadOrders();
    // Near-real-time kitchen queue: updates from cashier/customer appear automatically.
    setInterval(()=>{ if(!document.hidden) loadOrders(); },1000);
    setInterval(()=>{ if(!document.hidden && historyPanel?.classList.contains('open')) loadHistory(); },3000);
    setInterval(()=>{const el=document.getElementById('clock');if(el)el.textContent=new Date().toLocaleTimeString('en-US',{hour:'numeric',minute:'2-digit',second:'2-digit'});},1000);
});