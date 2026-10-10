/*
 * Sweet Chicken POS - Admin Dashboard behavior
 * Extracted from admin_dashboard.php
 */

    AOS.init();

    // Embedded Data Sources 
    let activeOrders = [];

    let menuItems = [];
    
    let usersData = [];

    let accessData = [
        { module: "Sales & Transactions", admin: true, cashier: true, kitchen: false },
        { module: "Order Management", admin: true, cashier: true, kitchen: true },
        { module: "Kitchen Display System", admin: true, cashier: false, kitchen: true },
        { module: "Menu Management", admin: true, cashier: false, kitchen: false },
        { module: "User Accounts", admin: true, cashier: false, kitchen: false },
        { module: "Audit Logs", admin: true, cashier: false, kitchen: false }
    ];

    let auditData = []; // loaded from the audit_logs table (see syncAdminData)

    let transactionHistory = [];

    // Global View Switcher Helper
    function switchView(targetViewId) {
        const navItems = document.querySelectorAll(".nav-menu li");
        navItems.forEach(nav => {
            if (nav.getAttribute("data-target") === targetViewId) {
                nav.classList.add("active");
            } else {
                nav.classList.remove("active");
            }
        });
        document.querySelectorAll(".app-view").forEach(view => view.classList.remove("active"));
        document.getElementById(targetViewId)?.classList.add("active");
    }

    document.addEventListener("DOMContentLoaded", function () {
        // 1. Live Clock Updater
        const clockElement = document.getElementById("clock");
        if (clockElement) {
            function updateClock() {
                const now = new Date();
                let hours = now.getHours();
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                const ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12 || 12;
                clockElement.textContent = `${hours}:${minutes}:${seconds} ${ampm}`;
            }
            updateClock();
            setInterval(updateClock, 1000);
        }

        // 2. Navigation Switcher Functionality (Clickable Sidebar Links)
        const navItems = document.querySelectorAll(".nav-menu li");
        navItems.forEach(item => {
            item.addEventListener("click", function (e) {
                e.preventDefault();
                const targetViewId = this.getAttribute("data-target");
                if (!targetViewId) return;
                switchView(targetViewId);
            });
        });

        // 3. Initial Rendering
        renderActiveOrders();
        renderMenuTable(menuItems);
        renderSalesTable(transactionHistory);
        renderUsersTable();
        renderAccessTable();
        renderAuditTable();
        initCharts();

        // 4. Menu Modal & Form Submissions
        const menuModal = document.getElementById("menuModal");
        const openAddModalBtn = document.getElementById("openAddModalBtn");
        const closeModalBtn = document.getElementById("closeModalBtn");
        const menuForm = document.getElementById("menuForm");

        function toggleMenuModal(open = true) {
            if (menuModal) {
                menuModal.style.right = open ? "0" : "-450px";
                if (!open) menuForm.reset();
            }
        }

        openAddModalBtn?.addEventListener("click", () => {
            document.getElementById("modalTitle").textContent = "Add New Menu Item";
            document.getElementById("itemId").value = "";
            document.getElementById("itemStock").value = "100";
            document.getElementById("itemStatus").value = "Available";
            toggleMenuModal(true);
        });

        closeModalBtn?.addEventListener("click", () => toggleMenuModal(false));

        // 5. New Order Modal Management
        const orderModal = document.getElementById("orderModal");
        const createNewOrderBtn = document.getElementById("createNewOrderBtn");
        const dashNewOrderBtn = document.getElementById("dashNewOrderBtn");
        const closeOrderModalBtn = document.getElementById("closeOrderModalBtn");
        const orderForm = document.getElementById("orderForm");

        function toggleOrderModal(open = true) {
            if (orderModal) {
                orderModal.style.right = open ? "0" : "-450px";
                if (!open) orderForm.reset();
            }
        }

        createNewOrderBtn?.addEventListener("click", () => toggleOrderModal(true));
        dashNewOrderBtn?.addEventListener("click", () => toggleOrderModal(true));
        closeOrderModalBtn?.addEventListener("click", () => toggleOrderModal(false));

        orderForm?.addEventListener("submit", function(e) {
            e.preventDefault();
            const customer = document.getElementById("orderCustomerName").value;
            const items = document.getElementById("orderItemsList").value;
            const total = parseFloat(document.getElementById("orderTotalAmount").value);
            const payment = document.getElementById("orderPaymentMethod").value;
            const newId = activeOrders.length > 0 ? Math.max(...activeOrders.map(o => o.id)) + 1 : 1001;

            const newOrder = {
                id: newId,
                customer: customer,
                items: items,
                total: total,
                status: "Preparing",
                seconds: 0,
                paymentMethod: payment
            };

            activeOrders.push(newOrder);
            renderActiveOrders();
            toggleOrderModal(false);
            alert(`Order #${newId} successfully created and sent to the kitchen queue!`);
        });

        // 6. User Modal Management
        const userModal = document.getElementById("userModal");
        const addUserBtn = document.getElementById("addUserBtn");
        const closeUserModalBtn = document.getElementById("closeUserModalBtn");

        function toggleUserModal(open = true) {
            if (userModal) {
                userModal.style.right = open ? "0" : "-450px";
            }
        }

        addUserBtn?.addEventListener("click", () => toggleUserModal(true));
        closeUserModalBtn?.addEventListener("click", () => toggleUserModal(false));

        // 7. Filters and Search Handlers
        document.getElementById("menuSearchInput")?.addEventListener("input", filterMenu);
        document.getElementById("categoryFilter")?.addEventListener("change", filterMenu);

        document.getElementById("salesSearchInput")?.addEventListener("input", filterSales);
        document.getElementById("paymentMethodFilter")?.addEventListener("change", filterSales);
        document.getElementById("salesDateFilter")?.addEventListener("change", filterSales);

        document.getElementById("generateReportBtn")?.addEventListener("click", function () {
            alert("Sales Report successfully generated and downloaded as PDF/CSV!");
        });

        // 8. Administration Action Handlers
        document.getElementById("saveAccessBtn")?.addEventListener("click", function () {
            alert("Access Control permission matrix saved successfully!");
        });

        document.getElementById("exportLogsBtn")?.addEventListener("click", function () {
            if (!auditData.length) { alert("There are no audit log entries to export."); return; }
            const rows = [["Log ID", "User", "Role", "Action Performed", "Target / Resource", "Details", "IP Address", "Timestamp"]];
            auditData.forEach(l => rows.push([l.id, l.user, l.role, l.action, l.target, l.details, l.ip, l.datetime]));
            const csv = rows.map(row => row.map(v => `"${String(v ?? "").replace(/"/g, '""')}"`).join(",")).join("\n");
            const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
            const url = URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = `sweet-chicken-audit-logs-${new Date().toISOString().slice(0, 10)}.csv`;
            a.click();
            URL.revokeObjectURL(url);
        });

        // 9. Profile & Notification Drawers
        const profileBtn = document.getElementById("profileBtn");
        const profileMenu = document.getElementById("profileMenu");

        function toggleProfile(forceState) {
            const isActive = forceState !== undefined ? forceState : !profileMenu.classList.contains("active");
            if (profileMenu) profileMenu.classList.toggle("active", isActive);
            
            document.getElementById("notificationPanel")?.classList.remove("active");
            document.getElementById("overlay")?.classList.remove("active");
        }

        profileBtn?.addEventListener("click", (e) => { e.stopPropagation(); toggleProfile(); });
        const profileOverlay = document.getElementById("profileOverlay");
        profileOverlay?.addEventListener("click", () => toggleProfile(false));

        const notificationBtn = document.getElementById("notificationBtn");
        const notifPanel = document.getElementById("notificationPanel");
        const overlay = document.getElementById("overlay");

        function toggleNotifications(forceState) {
            const isActive = forceState !== undefined ? forceState : !notifPanel.classList.contains("active");
            if (notifPanel) notifPanel.classList.toggle("active", isActive);
            if (overlay) overlay.classList.toggle("active", isActive);
            profileMenu?.classList.remove("active");
            profileOverlay?.classList.remove("active");
        }

        notificationBtn?.addEventListener("click", (e) => { e.stopPropagation(); toggleNotifications(); });
        document.getElementById("closeNotification")?.addEventListener("click", () => toggleNotifications(false));
        overlay?.addEventListener("click", () => {
            toggleNotifications(false);
            toggleProfile(false);
        });

        document.addEventListener("click", (e) => {
            if (profileMenu && !profileMenu.contains(e.target) && !profileBtn.contains(e.target)) {
                toggleProfile(false);
            }
        });
    });

    // Fullscreen Toggle Functionality
    const fullscreenBtn = document.getElementById("fullscreenBtn");
    const fullscreenIcon = document.getElementById("fullscreenIcon");

    fullscreenBtn?.addEventListener("click", function () {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(err => {
                alert(`Error attempting to enable fullscreen mode: ${err.message} (${err.name})`);
            });
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        }
    });

    document.addEventListener("fullscreenchange", function () {
        if (document.fullscreenElement) {
            fullscreenIcon.className = "fas fa-compress";
            fullscreenBtn.setAttribute("title", "Exit Fullscreen");
        } else {
            fullscreenIcon.className = "fas fa-expand";
            fullscreenBtn.setAttribute("title", "Toggle Fullscreen");
        }
    });

    // Profile Modal Functionality
    const profileModal = document.getElementById("profileModal");
    const openProfileModalLink = document.getElementById("openProfileModalLink");
    const closeProfileModalBtn = document.getElementById("closeProfileModalBtn");
    const profileForm = document.getElementById("profileForm");

    function toggleProfileModal(open = true) {
        if (profileModal) {
            profileModal.style.right = open ? "0" : "-450px";
        }
    }

    openProfileModalLink?.addEventListener("click", (e) => {
        e.preventDefault();
        toggleProfile(false); // Close dropdown menu
        toggleProfileModal(true); // Open profile modal
    });

    closeProfileModalBtn?.addEventListener("click", () => {
        toggleProfileModal(false);
    });

    profileForm?.addEventListener("submit", function (e) {
        e.preventDefault();
        const newName = document.getElementById("profileNameInput").value;
        const newEmail = document.getElementById("profileEmailInput").value;

        // Update UI elements dynamically across the topbar & dropdown header
        document.querySelector(".profile h4").textContent = newName;
        document.querySelector(".profile-dropdown-header h4").textContent = newName;
        document.getElementById("profileDisplayName").textContent = newName;

        toggleProfileModal(false);
        alert("Profile details successfully updated!");
    });

    // Sidebar Toggle Functionality (Burger Icon)
    const toggleSidebarBtn = document.getElementById("toggleSidebar");
    const sidebar = document.getElementById("sidebar");
    const mainContent = document.getElementById("mainContent");

    toggleSidebarBtn?.addEventListener("click", function () {
        sidebar.classList.toggle("collapsed");
        mainContent.classList.toggle("sidebar-collapsed");
        
        if (window.innerWidth <= 992) {
            sidebar.classList.toggle("active");
        }
    });

    // Rendering Active Orders for both Orders Table and Kitchen Queue
    function renderActiveOrders() {
        const ordersTableBody = document.querySelector("#ordersModuleTable tbody");
        const kitchenGrid = document.getElementById("kitchenQueueGrid");
        const kitchenBadge = document.getElementById("kitchenBadge");
        const orderBadgeCount = document.getElementById("orderBadgeCount");

        if (kitchenBadge) kitchenBadge.textContent = activeOrders.length;
        if (orderBadgeCount) orderBadgeCount.textContent = activeOrders.length;

        // Render Orders Management Table
        if (ordersTableBody) {
            ordersTableBody.innerHTML = "";
            if (activeOrders.length === 0) {
                ordersTableBody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 30px; color: #6B7280;">No active customer orders in queue.</td></tr>`;
            } else {
                activeOrders.forEach(order => {
                    ordersTableBody.innerHTML += `
                        <tr>
                            <td>#${order.id}</td>
                            <td><strong>${order.customer}</strong></td>
                            <td>${order.items}</td>
                            <td>₱${order.total}</td>
                            <td><span class="status-label pending">${order.status}</span></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-btn view" onclick="viewOrderDetails(${order.id})" title="View Details"><i class="fa-solid fa-eye"></i></button>
                                    <button class="action-btn complete" onclick="completeOrder(${order.id})" title="Mark Complete"><i class="fa-solid fa-check"></i></button>
                                    <button class="action-btn delete" onclick="cancelOrder(${order.id})" title="Cancel Order"><i class="fa-solid fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
            }
        }

        // Render Kitchen Monitoring Grid
        if (kitchenGrid) {
            kitchenGrid.innerHTML = "";
            if (activeOrders.length === 0) {
                kitchenGrid.innerHTML = `<p style="color: #6B7280; padding: 20px;">No pending items in the kitchen queue right now.</p>`;
            } else {
                activeOrders.forEach(order => {
                    const mins = String(Math.floor(order.seconds / 60)).padStart(2, '0');
                    const secs = String(order.seconds % 60).padStart(2, '0');
                    kitchenGrid.innerHTML += `
                        <div class="kitchen-order">
                            <div class="kitchen-header">
                                <strong>#${order.id}</strong>
                                <span class="preparing">${order.status}</span>
                            </div>
                            <div class="timer">${mins}:${secs}</div>
                            <div class="progress-line">
                                <div class="progress-fill" style="width: ${Math.min(order.seconds / 3, 100)}%;"></div>
                            </div>
                            <small>${order.customer} - ${order.items}</small>
                            <button class="btn-primary" style="padding: 6px 12px; font-size: 12px; margin-top: 4px; justify-content: center;" onclick="completeOrder(${order.id})">Mark as Ready</button>
                        </div>
                    `;
                });
            }
        }
    }

    // Interactive Functions for Order Actions
    window.viewOrderDetails = function(id) {
        const order = activeOrders.find(o => o.id === id) || { id: id, customer: "Maria Santos", items: "Chicken Combo", total: 560, status: "Preparing" };
        alert(`Order Details #${order.id}\nCustomer: ${order.customer}\nItems: ${order.items}\nTotal: ₱${order.total}\nStatus: ${order.status}`);
    };

    window.completeOrder = function(id) {
        const index = activeOrders.findIndex(o => o.id === id);
        if (index !== -1) {
            const completedOrder = activeOrders.splice(index, 1)[0];
            
            // Push to transaction history
            const now = new Date();
            const dateStr = now.toISOString().slice(0, 10) + ' ' + now.toTimeString().slice(0, 5);
            transactionHistory.unshift({
                id: 9800 + transactionHistory.length + 1,
                customer: completedOrder.customer,
                items: completedOrder.items.split(',').length,
                total: completedOrder.total,
                payment: completedOrder.paymentMethod || "Cash",
                datetime: dateStr
            });

            renderActiveOrders();
            renderSalesTable(transactionHistory);
            updateSalesMetrics();
            alert(`Order #${id} has been marked as completed and moved to sales history!`);
        }
    };

    window.cancelOrder = function(id) {
        if (confirm(`Are you sure you want to cancel Order #${id}?`)) {
            activeOrders = activeOrders.filter(o => o.id !== id);
            renderActiveOrders();
            alert(`Order #${id} has been cancelled.`);
        }
    };

    function updateSalesMetrics() {
        const totalSales = transactionHistory.reduce((sum, t) => sum + t.total, 0);
        const totalTrans = transactionHistory.length;
        const avgOrder = totalTrans > 0 ? (totalSales / totalTrans).toFixed(2) : 0;

        const totalSalesMetric = document.getElementById("totalSalesMetric");
        const totalTransactionsMetric = document.getElementById("totalTransactionsMetric");
        const avgOrderMetric = document.getElementById("avgOrderMetric");

        if (totalSalesMetric) totalSalesMetric.textContent = `₱${totalSales.toLocaleString()}`;
        if (totalTransactionsMetric) totalTransactionsMetric.textContent = totalTrans;
        if (avgOrderMetric) avgOrderMetric.textContent = `₱${avgOrder}`;
    }

    // Rendering Functions
    function renderMenuTable(data) {
        const tbody = document.querySelector("#menuTable tbody");
        if (!tbody) return;
        tbody.innerHTML = "";
        if (data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:30px;color:#6B7280;">No menu items available.</td></tr>`;
            return;
        }
        data.forEach(item => {
            const stock = Number(item.stock || 0);
            const status = item.status === 'Available' && stock > 0 ? 'Available' : 'Unavailable';
            const stockClass = stock <= 0 ? 'out-of-stock' : (stock <= 10 ? 'pending' : 'available');
            tbody.innerHTML += `
                <tr>
                    <td>#${item.id}</td>
                    <td><strong>${item.name}</strong></td>
                    <td>${item.category || ''}</td>
                    <td>₱${Number(item.price).toFixed(2)}</td>
                    <td><span class="status-label ${stockClass}">${stock}</span></td>
                    <td><span class="status-label ${status === 'Available' ? 'available' : 'out-of-stock'}">${status}</span></td>
                    <td class="actions">
                        <div class="action-buttons">
                            <button class="action-btn edit" onclick="editItem(${item.id})" title="Edit Item"><i class="fa-solid fa-pen"></i></button>
                            <button class="action-btn delete" onclick="removeItem(${item.id})" title="Remove Item"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </td>
                </tr>`;
        });
    }

    function filterMenu() {
        const query = document.getElementById("menuSearchInput")?.value.toLowerCase() || "";
        const cat = document.getElementById("categoryFilter")?.value || "All";

        const filtered = menuItems.filter(item => {
            const matchesQuery = item.name.toLowerCase().includes(query);
            const matchesCat = cat === "All" || item.category === cat;
            return matchesQuery && matchesCat;
        });

        renderMenuTable(filtered);
    }

    function renderSalesTable(data) {
        const tbody = document.querySelector("#salesTable tbody");
        if (!tbody) return;
        tbody.innerHTML = "";

        if (data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 30px; color: #6B7280;">No transaction records found.</td></tr>`;
            return;
        }

        data.forEach(trx => {
            tbody.innerHTML += `
                <tr>
                    <td>#${trx.id}</td>
                    <td><strong>${trx.customer}</strong></td>
                    <td>${trx.items}</td>
                    <td>₱${trx.total}</td>
                    <td><span class="status-label completed">${trx.payment}</span></td>
                    <td>${trx.datetime}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-btn view" onclick="viewReceipt(${trx.id})" title="View Receipt"><i class="fa-solid fa-eye"></i></button>
                            <button class="action-btn print" onclick="printReceipt(${trx.id})" title="Print Receipt"><i class="fa-solid fa-print"></i></button>
                        </div>
                    </td>
                </tr>
            `;
        });
    }

    function filterSales() {
        const query = document.getElementById("salesSearchInput")?.value.toLowerCase() || "";
        const payment = document.getElementById("paymentMethodFilter")?.value || "All";
        const date = document.getElementById("salesDateFilter")?.value || "";

        const filtered = transactionHistory.filter(trx => {
            const matchesQuery = trx.customer.toLowerCase().includes(query) || String(trx.id).includes(query);
            const matchesPayment = payment === "All" || trx.payment === payment;
            const matchesDate = date === "" || trx.datetime.startsWith(date);
            return matchesQuery && matchesPayment && matchesDate;
        });

        renderSalesTable(filtered);
    }

    function renderUsersTable() {
        const tbody = document.querySelector("#usersTable tbody");
        if (!tbody) return;
        tbody.innerHTML = usersData.map(user => {
            // Only Cashier and Kitchen Staff accounts are managed here; customers and the administrator are view-only.
            const isStaff = user.role === "Cashier" || user.role === "Kitchen";
            const actions = isStaff ? `
                <div class="action-buttons">
                    <button class="action-btn edit" onclick="editUser(${user.id})" title="Edit User"><i class="fa-solid fa-pen"></i></button>
                    <button class="action-btn delete" onclick="toggleUserStatus(${user.id})" title="${user.status === "Active" ? "Deactivate account" : "Reactivate account"}"><i class="fa-solid fa-power-off"></i></button>
                </div>` : `<span style="color:#9CA3AF;font-size:12px;">View only</span>`;
            return `
                <tr>
                    <td>#${user.id}</td>
                    <td><strong>${escapeHtml(user.name)}</strong></td>
                    <td>${escapeHtml(user.email)}</td>
                    <td>${escapeHtml(user.role)}</td>
                    <td><span class="status-label ${user.status.toLowerCase()}">${escapeHtml(user.status)}</span></td>
                    <td>${actions}</td>
                </tr>`;
        }).join("");
    }

    function renderAccessTable() {
        const tbody = document.querySelector("#accessTable tbody");
        if (!tbody) return;
        tbody.innerHTML = "";

        accessData.forEach((item, index) => {
            tbody.innerHTML += `
                <tr>
                    <td><strong>${item.module}</strong></td>
                    <td>
                        <label class="switch">
                            <input type="checkbox" ${item.cashier ? 'checked' : ''} onchange="updateAccess(${index}, 'cashier', this.checked)">
                            <span class="slider"></span>
                        </label>
                    </td>
                    <td>
                        <label class="switch">
                            <input type="checkbox" ${item.kitchen ? 'checked' : ''} onchange="updateAccess(${index}, 'kitchen', this.checked)">
                            <span class="slider"></span>
                        </label>
                    </td>
                </tr>
            `;
        });
    }

    function renderAuditTable() {
        const tbody = document.querySelector("#auditTable tbody");
        if (!tbody) return;
        if (!auditData.length) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:24px;">No activity recorded yet.</td></tr>`;
            return;
        }
        tbody.innerHTML = auditData.map(log => `
            <tr>
                <td>#${log.id}</td>
                <td><strong>${escapeHtml(log.user)}</strong>${log.role ? `<br><small>${escapeHtml(log.role)}</small>` : ""}</td>
                <td>${escapeHtml(log.action)}</td>
                <td>${escapeHtml(log.target)}${log.details ? `<br><small>${escapeHtml(log.details)}</small>` : ""}</td>
                <td><code>${escapeHtml(log.ip)}</code></td>
                <td>${escapeHtml(log.datetime)}</td>
            </tr>
        `).join("");
    }

    // Global Interactive Functions
    window.editItem = function (id) {
        const item = menuItems.find(i => Number(i.id) === Number(id));
        if (!item) return;
        document.getElementById("modalTitle").textContent = "Edit Menu Item";
        document.getElementById("itemId").value = item.id;
        document.getElementById("itemName").value = item.name || '';
        document.getElementById("itemCategory").value = item.category || '';
        document.getElementById("itemPrice").value = item.price ?? 0;
        document.getElementById("itemStock").value = item.stock ?? 0;
        document.getElementById("itemStatus").value = item.status === 'Available' ? 'Available' : 'Out of Stock';
        document.getElementById("menuModal").style.right = "0";
    };

    window.removeItem = function (id) {
        if (confirm("Are you sure you want to remove this item from the menu?")) {
            menuItems = menuItems.filter(i => i.id !== id);
            renderMenuTable(menuItems);
        }
    };

    window.viewReceipt = async function(id) {
        try {
            const r = await apiCall('order_details&id=' + encodeURIComponent(id));
            const o = r.order;
            const lines = (o.items || []).map(i => `${i.name} x${i.quantity} - ₱${(Number(i.price)*Number(i.quantity)).toFixed(2)}`).join('\n');
            alert(`RECEIPT\nOrder: ${o.order_number}\nCustomer: ${o.customer_name || o.account_name || 'Walk-in Customer'}\nType: ${o.order_type || 'Online'}\nPayment: ${o.payment_method} (${o.payment_status})\n\n${lines}\n\nTOTAL: ₱${Number(o.total).toFixed(2)}`);
        } catch(e) { alert(e.message); }
    };

    window.printReceipt = async function(id) {
        try {
            const r = await apiCall('order_details&id=' + encodeURIComponent(id));
            const o = r.order;
            const rows = (o.items || []).map(i => `<tr><td>${escapeHtml(i.name)}</td><td>${i.quantity}</td><td>₱${Number(i.price).toFixed(2)}</td><td>₱${(Number(i.price)*Number(i.quantity)).toFixed(2)}</td></tr>`).join('');
            const w = window.open('', '_blank', 'width=420,height=650');
            if (!w) { alert('Please allow pop-ups to print the receipt.'); return; }
            w.document.write(`<!doctype html><html><head><title>Receipt ${escapeHtml(o.order_number)}</title><style>body{font-family:Arial,sans-serif;padding:24px;color:#222}h2{text-align:center;margin-bottom:4px}.meta{font-size:12px;color:#555;margin-bottom:18px}table{width:100%;border-collapse:collapse;font-size:12px}th,td{padding:7px 3px;border-bottom:1px solid #ddd;text-align:left}th:nth-child(n+2),td:nth-child(n+2){text-align:right}.total{font-size:18px;font-weight:bold;text-align:right;margin-top:16px}</style></head><body><h2>Sweet Chicken</h2><div class="meta">${escapeHtml(o.order_number)}<br>${escapeHtml(o.customer_name || o.account_name || 'Walk-in Customer')}<br>${escapeHtml(o.order_type || '')} · ${escapeHtml(o.payment_method || '')}</div><table><thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Amount</th></tr></thead><tbody>${rows}</tbody></table><div class="total">TOTAL: ₱${Number(o.total).toFixed(2)}</div><script>window.onload=()=>window.print();<\/script></body></html>`);
            w.document.close();
        } catch(e) { alert(e.message); }
    };

    document.getElementById('generateReportBtn')?.addEventListener('click', function() {
        if (!transactionHistory.length) { alert('No completed sales to export.'); return; }
        const rows = [['Transaction #','Customer','Items Count','Total','Payment Method','Payment Status','Order Type','Date & Time'], ...transactionHistory.map(t => [t.id,t.customer,t.items,t.total,t.payment,t.paymentStatus || '',t.orderType || '',t.datetime])];
        const csv = rows.map(row => row.map(v => `"${String(v ?? '').replace(/"/g,'""')}"`).join(',')).join('\n');
        const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a'); a.href=url; a.download=`sweet-chicken-sales-${new Date().toISOString().slice(0,10)}.csv`; a.click();
        URL.revokeObjectURL(url);
    });

    window.editUser = async function(id) {
        const user = usersData.find(u => u.id === id);
        if (!user) return;
        const newName = prompt("Edit user name:", user.name);
        if (newName === null || newName.trim() === "" || newName.trim() === user.name) return;
        try {
            await apiCall('update_user', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id, fullname:newName.trim(), role:user.role.toLowerCase()})});
            await syncAdminData();
        } catch (e) { alert(e.message); }
    };

    window.toggleUserStatus = async function(id) {
        const user = usersData.find(u => u.id === id);
        if (!user) return;
        const newStatus = user.status === "Active" ? "Deactivated" : "Active";
        const verb = newStatus === "Deactivated" ? "deactivate" : "reactivate";
        if (!confirm(`Are you sure you want to ${verb} ${user.name}'s account?` + (newStatus === "Deactivated" ? " They will be signed out and unable to log in." : ""))) return;
        try {
            await apiCall('set_user_status', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id, status:newStatus})});
            await syncAdminData();
        } catch (e) { alert(e.message); }
    };

    window.updateAccess = function(index, roleKey, value) {
        accessData[index][roleKey] = value;
    };

    // Initialize Chart.js Dashboards
    function initCharts() {
        const salesCtx = document.getElementById('salesChart')?.getContext('2d');
        if (salesCtx) {
            new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                    datasets: [{
                        label: 'Sales Revenue (₱)',
                        data: [250000, 310000, 290000, 350000],
                        borderColor: '#FF7A00',
                        backgroundColor: 'rgba(255, 122, 0, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }
                }
            });
        }

        const menuCtx = document.getElementById('menuChart')?.getContext('2d');
        if (menuCtx) {
            new Chart(menuCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Chicken Bucket', 'Fried Chicken Meal', 'Lemon Tea', 'Rice'],
                    datasets: [{
                        data: [45, 30, 15, 10],
                        backgroundColor: ['#FF7A00', '#F59E0B', '#22C55E', '#3B82F6']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }
    }

    // Dark Mode Functionality
    const darkModeToggle = document.getElementById("darkModeToggle");
    const darkModeIcon = document.getElementById("darkModeIcon");
    const darkModeText = document.getElementById("darkModeText");

    // Check for saved user preference on load
    const currentTheme = localStorage.getItem("theme");
    if (currentTheme === "dark") {
        document.body.classList.add("dark-mode");
        if (darkModeIcon) darkModeIcon.className = "fas fa-sun";
        if (darkModeText) darkModeText.textContent = "Light Mode";
    }

    darkModeToggle?.addEventListener("click", function (e) {
        e.preventDefault();
        document.body.classList.toggle("dark-mode");
        
        let theme = "light";
        if (document.body.classList.contains("dark-mode")) {
            theme = "dark";
            if (darkModeIcon) darkModeIcon.className = "fas fa-sun";
            if (darkModeText) darkModeText.textContent = "Light Mode";
        } else {
            if (darkModeIcon) darkModeIcon.className = "fas fa-moon";
            if (darkModeText) darkModeText.textContent = "Dark Mode";
        }
        
        localStorage.setItem("theme", theme);
        toggleProfile(false); // Close profile dropdown after clicking
    });


    // ================= CONNECTED DATABASE SYNC =================
    async function apiCall(action, options = {}) {
        const response = await fetch(`api.php?action=${encodeURIComponent(action)}`, options);
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Request failed');
        return data;
    }

    async function loadAuditLogs() {
        const response = await fetch('get_audit_logs.php?_=' + Date.now(), { cache: 'no-store' });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Could not load audit logs');
        auditData = data.logs.map(l => ({
            id: l.id, user: l.user_name, role: l.user_role || '', action: l.action_performed,
            target: l.target_resource || '', details: l.details || '', ip: l.ip_address || '', datetime: l.created_at
        }));
        return auditData;
    }

    async function syncAdminData() {
        try {
            const [ordersData, menuData, salesData, usersResult, auditResult] = await Promise.all([
                apiCall('orders'), apiCall('admin_menu'), apiCall('sales'), apiCall('users'), loadAuditLogs().catch(err => { console.error('Audit logs unavailable:', err); return auditData; })
            ]);
            activeOrders = ordersData.orders.filter(o => !['Completed','Cancelled'].includes(o.status)).map(o => ({
                id:o.id, customer:o.customer_name || o.account_name || 'Walk-in Customer', items:o.items || '',
                total:Number(o.total), status:o.status, seconds:0, paymentMethod:o.payment_method
            }));
            menuItems = menuData.menu.map(m => ({...m, status:m.status === 'Available' ? 'Available' : 'Out of Stock'}));
            transactionHistory = salesData.sales.map(s => ({
                id:s.order_id, customer:s.customer_name || s.fullname || 'Customer', items:Number(s.items_count || 0),
                total:Number(s.total), payment:s.payment_method, paymentStatus:s.payment_status, orderType:s.order_type, datetime:s.created_at
            }));
            usersData = usersResult.users.map(u => ({
                id:Number(u.id), name:u.fullname, email:u.email,
                role:u.role.charAt(0).toUpperCase()+u.role.slice(1), status:u.status || 'Active'
            }));
            renderActiveOrders(); renderMenuTable(menuItems); renderSalesTable(transactionHistory); renderUsersTable(); renderAuditTable(); updateSalesMetrics();
        } catch (e) { console.error('Admin sync error:', e); }
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>'"]/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','\"':'&quot;'}[ch]));
    }

    window.completeOrder = async function(id) {
        try {
            const r = await apiCall('update_order_status', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({order_id:id,status:'Completed'})});
            alert(r.message); await syncAdminData();
        } catch(e) { alert(e.message); }
    };
    window.cancelOrder = async function(id) {
        if (!confirm(`Are you sure you want to cancel Order #${id}?`)) return;
        try { await apiCall('update_order_status', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({order_id:id,status:'Cancelled'})}); await syncAdminData(); }
        catch(e) { alert(e.message); }
    };
    window.removeItem = async function(id) {
        if (!confirm('Remove this item from the menu? It will be marked unavailable and will no longer appear to customers/cashiers.')) return;
        try { await apiCall('delete_menu', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})}); await syncAdminData(); }
        catch(e) { alert(e.message); }
    };

    document.addEventListener('DOMContentLoaded', () => {
        const menuForm = document.getElementById('menuForm');
        menuForm?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const id = document.getElementById('itemId').value;
            const name = document.getElementById('itemName').value.trim();
            const category = document.getElementById('itemCategory').value.trim();
            const price = Number(document.getElementById('itemPrice').value);
            const stock = Math.max(0, Number(document.getElementById('itemStock').value));
            const status = document.getElementById('itemStatus').value;
            const existing = id ? menuItems.find(x => Number(x.id) === Number(id)) : null;
            const code = existing?.code || name.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'') || `item-${Date.now()}`;
            try {
                await apiCall('save_menu', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:id?Number(id):0,code,name,category,price,stock,status:status==='Available' && stock>0?'Available':'Unavailable'})});
                document.getElementById('menuModal').style.right = '-450px';
                await syncAdminData();
            } catch(err) { alert(err.message); }
        });

        syncAdminData();
        setInterval(syncAdminData, 5000);
    });
