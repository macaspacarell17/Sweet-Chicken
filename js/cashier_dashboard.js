/*
 * Sweet Chicken POS - Cashier Dashboard behavior
 * Extracted from cashier_dashboard.php
 */

    AOS.init();

    document.addEventListener("DOMContentLoaded", function () {
        let cart = [];
        let transactionCounter = parseInt(localStorage.getItem("sweetChickenTransactionCounter")) || 1;
        let currentProduct = null;
        let quantityValue = 1;
        let editingIndex = null; // null = adding a new item; number = editing cart[index]

        // Fullscreen Toggle Functionality
        const fullscreenBtn = document.getElementById("fullscreenBtn");
        const fullscreenIcon = document.getElementById("fullscreenIcon");

        if (fullscreenBtn) {
            fullscreenBtn.addEventListener("click", () => {
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

            document.addEventListener("fullscreenchange", () => {
                if (document.fullscreenElement) {
                    fullscreenIcon.classList.remove("fa-expand");
                    fullscreenIcon.classList.add("fa-compress");
                    fullscreenBtn.title = "Exit Fullscreen";
                } else {
                    fullscreenIcon.classList.remove("fa-compress");
                    fullscreenIcon.classList.add("fa-expand");
                    fullscreenBtn.title = "Toggle Fullscreen";
                }
            });
        }

        // Sales Summary Period Filter Handler
        const salesPeriodDropdown = document.getElementById("salesPeriodDropdown");
        if (salesPeriodDropdown) {
            salesPeriodDropdown.addEventListener("change", function () {
                loadSalesSummary(this.value);
            });
        }

        let allSalesRecords = [];

        // Pulls the real completed-sales records from the database (shared
        // across all cashiers/terminals) and renders the cards, chart and
        // transaction table for the selected period.
        async function loadSalesSummary(period) {
            period = period || (salesPeriodDropdown ? salesPeriodDropdown.value : "weekly");
            try {
                const r = await fetch('api.php?action=sales&_=' + Date.now(), { cache: 'no-store' });
                const d = await r.json();
                if (!d.success) throw new Error(d.message || 'Could not load sales.');
                allSalesRecords = d.sales || [];
            } catch (e) {
                console.error('Sales summary load failed:', e);
                allSalesRecords = [];
            }
            renderSummaryRecords(period);
        }

        function orderNumberFor(rec) {
            const prefix = (String(rec.order_type || '').toLowerCase() === 'walk-in') ? 'SC-WALKIN-' : 'SC-ONLINE-';
            return prefix + String(rec.order_id).padStart(6, '0');
        }

        function isSameDay(a, b) {
            return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
        }

        function filterRecordsByPeriod(records, period, now) {
            return records.filter(rec => {
                const d = new Date(rec.created_at.replace(' ', 'T'));
                if (period === 'daily') return isSameDay(d, now);
                if (period === 'weekly') {
                    const weekAgo = new Date(now); weekAgo.setDate(weekAgo.getDate() - 6); weekAgo.setHours(0, 0, 0, 0);
                    return d >= weekAgo && d <= now;
                }
                if (period === 'annual') return d.getFullYear() === now.getFullYear();
                return true;
            });
        }

        // Buckets the already-period-filtered records into chart-ready labels/data.
        function buildChartBuckets(records, period, now) {
            if (period === 'daily') {
                const labels = ['12 AM','3 AM','6 AM','9 AM','12 PM','3 PM','6 PM','9 PM'];
                const data = new Array(8).fill(0);
                records.forEach(rec => {
                    const d = new Date(rec.created_at.replace(' ', 'T'));
                    const bucket = Math.min(7, Math.floor(d.getHours() / 3));
                    data[bucket] += Number(rec.total) || 0;
                });
                return { labels, data, label: 'Daily Sales (₱)' };
            }
            if (period === 'annual') {
                const labels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                const data = new Array(12).fill(0);
                records.forEach(rec => {
                    const d = new Date(rec.created_at.replace(' ', 'T'));
                    data[d.getMonth()] += Number(rec.total) || 0;
                });
                return { labels, data, label: 'Annual Sales (₱)' };
            }
            // weekly: last 7 days ending today
            const labels = [];
            const data = new Array(7).fill(0);
            const dayStart = new Date(now); dayStart.setHours(0, 0, 0, 0);
            for (let i = 6; i >= 0; i--) {
                const day = new Date(dayStart); day.setDate(day.getDate() - i);
                labels.push(day.toLocaleDateString(undefined, { weekday: 'short' }));
            }
            records.forEach(rec => {
                const d = new Date(rec.created_at.replace(' ', 'T'));
                const diffDays = Math.floor((dayStart - new Date(d.getFullYear(), d.getMonth(), d.getDate())) / 86400000);
                if (diffDays >= 0 && diffDays <= 6) data[6 - diffDays] += Number(rec.total) || 0;
            });
            return { labels, data, label: 'Weekly Sales (₱)' };
        }

        const cartItems = document.getElementById("cartItems");
        const itemCount = document.getElementById("itemCount");
        const subtotalElement = document.getElementById("subtotal");
        const discountInput = document.getElementById("discount");
        const totalElement = document.getElementById("total");
        const paymentInput = document.getElementById("payment");
        const paymentMethodInput = document.getElementById("paymentMethod");
        const changeElement = document.getElementById("change");
        const noteInput = document.getElementById("orderNote");
        const completeButton = document.getElementById("completeTransaction");
        const clearCartButton = document.getElementById("clearCart");
        const transactionNumber = document.getElementById("transactionNumber");
        const gcashQRSection = document.getElementById("gcashQRSection");
        const walkinOrderTypeSelect = document.getElementById("walkinOrderType");
        const walkinTableNumberWrap = document.getElementById("walkinTableNumberWrap");
        const walkinTableNumberInput = document.getElementById("walkinTableNumber");

        function syncTableNumberVisibility() {
            const isDineIn = (walkinOrderTypeSelect?.value || "") === "Dine-In";
            if (walkinTableNumberWrap) walkinTableNumberWrap.style.display = isDineIn ? "block" : "none";
            if (!isDineIn && walkinTableNumberInput) walkinTableNumberInput.value = "";
        }
        if (walkinOrderTypeSelect) {
            walkinOrderTypeSelect.addEventListener("change", syncTableNumberVisibility);
            syncTableNumberVisibility();
        }

        // Shows the transaction number the NEXT completed order will get,
        // so the badge isn't stuck on a hardcoded placeholder.
        async function refreshNextTransactionNumber() {
            if (!transactionNumber) return;
            try {
                const r = await fetch('api.php?action=next_order_number&_=' + Date.now(), { cache: 'no-store' });
                const d = await r.json();
                if (d.success) transactionNumber.textContent = d.next_order_number;
            } catch (e) { /* keep whatever was last shown */ }
        }
        refreshNextTransactionNumber();

        // Category Filtering Handler
        const categoryButtons = document.querySelectorAll(".category-btn");
        categoryButtons.forEach(btn => {
            btn.addEventListener("click", function() {
                categoryButtons.forEach(b => b.classList.remove("active"));
                this.classList.add("active");
                const selectedCat = this.dataset.category;
                
                document.querySelectorAll(".product-card").forEach(card => {
                    if (selectedCat === "all" || card.dataset.category === selectedCat) {
                        card.style.display = "flex";
                    } else {
                        card.style.display = "none";
                    }
                });
            });
        });

        // Sidebar Toggle Handler
        const sidebar = document.getElementById("sidebar");
        const mainContent = document.getElementById("mainContent");
        const toggleSidebarBtn = document.getElementById("toggleSidebar");
        if(toggleSidebarBtn && sidebar) {
            toggleSidebarBtn.addEventListener("click", () => {
                sidebar.classList.toggle("collapsed");
                mainContent.classList.toggle("sidebar-collapsed");
            });
        }

        // Profile Dropdown Handler
        const profileBtn = document.getElementById("profileBtn");
        const profileMenu = document.getElementById("profileMenu");

        function toggleProfile(forceState) {
            const isActive = forceState !== undefined ? forceState : !profileMenu.classList.contains("active");
            if (profileMenu) profileMenu.classList.toggle("active", isActive);
        }

        if (profileBtn && profileMenu) {
            profileBtn.addEventListener("click", (e) => { 
                e.stopPropagation(); 
                toggleProfile(); 
            });
        }

        document.addEventListener("click", (e) => {
            if (profileMenu && profileBtn && !profileMenu.contains(e.target) && !profileBtn.contains(e.target)) {
                toggleProfile(false);
            }
        });

        // Dark Mode Functionality Script
        const darkModeToggle = document.getElementById("darkModeToggle");
        const darkModeIcon = document.getElementById("darkModeIcon");
        const darkModeText = document.getElementById("darkModeText");

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
            
            const profileMenu = document.getElementById("profileMenu");
            if (profileMenu) profileMenu.classList.remove("active");
        });

        // Notification Drawer Toggle
        const notificationBtn = document.getElementById("notificationBtn");
        const notificationPanel = document.getElementById("notificationPanel");
        const closeNotification = document.getElementById("closeNotification");
        if (notificationBtn && notificationPanel) {
            notificationBtn.addEventListener("click", (e) => {
                e.stopPropagation();
                notificationPanel.classList.toggle("open");
            });
            if (closeNotification) {
                closeNotification.addEventListener("click", () => {
                    notificationPanel.classList.remove("open");
                });
            }
        }

        // Navigation Switcher
        const navLinks = document.querySelectorAll(".nav-menu li");
        navLinks.forEach(li => {
            li.addEventListener("click", function(e) {
                e.preventDefault();
                navLinks.forEach(item => item.classList.remove("active"));
                this.classList.add("active");
                
                const targetId = this.dataset.target;
                document.querySelectorAll(".pos-content, .page-content").forEach(page => {
                    page.classList.remove("active");
                });
                document.getElementById(targetId).classList.add("active");
                if(targetId === 'summary-content') {
                    loadSalesSummary();
                } else if(targetId === 'online-content') {
                    updateBadge();
                    displayOnlineOrders();
                }
            });
        });

        // Payment Method Switcher for GCash QR
        if (paymentMethodInput) {
            paymentMethodInput.addEventListener("change", function() {
                gcashQRSection.style.display = (this.value === "GCash") ? "block" : "none";
            });
        }

                const flavors = ["Honey Garlic", "Sweet Chili", "Buffalo", "Garlic Parmesan", "Salted Egg", "Original", "Yangnyeom"];
        const riceOptions = ["Fried Rice", "Plain Rice"];
        const drinkOptions = ["Iced Tea", "Lemonade"];

        // Tracks how many units of each option have been assigned per group
        // while the customization modal is open, e.g.
        // currentAllocations.flavors = { "Honey Garlic": 3, "Buffalo": 2, "Garlic Parmesan": 2 }
        let currentAllocations = { flavors: {}, rice: {}, drinks: {} };

        function getProductConfiguration(id) {
            if (id === "barkada-7") return { pieces: 7, flavorCount: 3, riceCount: 2, drinkCount: 2 };
            if (id === "barkada-4") return { pieces: 4, flavorCount: 2, riceCount: 2, drinkCount: 2 };
            if (id === "chicken-3") return { pieces: 3, flavorCount: 1, riceCount: 0, drinkCount: 0 };
            if (id === "chicken-2") return { pieces: 2, flavorCount: 1, riceCount: 0, drinkCount: 0 };
            if (id === "chicken-pops") return { pieces: 0, flavorCount: 1, riceCount: 0, drinkCount: 0 };
            return { pieces: 0, flavorCount: 0, riceCount: 0, drinkCount: 0 };
        }

        document.querySelectorAll(".product-card").forEach(function (card) {
            card.addEventListener("click", function () {
                currentProduct = {
                    id: card.dataset.id,
                    name: card.dataset.name,
                    price: parseFloat(card.dataset.price),
                    description: card.querySelector("p")?.textContent.trim() || ""
                };
                openCustomization(currentProduct);
            });
        });

        // Renders a "distribute N across these options" stepper group, e.g.
        // spreading 7 pieces across however many flavors, or 2 drinks across
        // Iced Tea / Lemonade. distinctRequired (optional) additionally
        // requires exactly that many options to have a qty > 0 - used for
        // flavors, where "choose 3 flavors" means exactly 3 different ones.
        function renderAllocationGroup(containerId, instructionId, errorId, options, total, groupKey, distinctRequired, initial) {
            initial = initial || {};
            const container = document.getElementById(containerId);
            const instructionEl = document.getElementById(instructionId);
            const errorEl = document.getElementById(errorId);
            container.innerHTML = "";
            errorEl.textContent = "";
            currentAllocations[groupKey] = {};

            instructionEl.textContent = distinctRequired
                ? "Choose exactly " + distinctRequired + " flavor(s) totaling " + total + " pcs."
                : "Choose " + total + " total - mix and match.";

            options.forEach(function (opt) {
                currentAllocations[groupKey][opt] = initial[opt] || 0;

                const row = document.createElement("div");
                row.className = "alloc-row";

                const label = document.createElement("span");
                label.className = "alloc-label";
                label.textContent = opt;

                const stepper = document.createElement("div");
                stepper.className = "alloc-stepper";

                const minusBtn = document.createElement("button");
                minusBtn.type = "button";
                minusBtn.className = "alloc-btn";
                minusBtn.textContent = "\u2212";

                const qtyEl = document.createElement("span");
                qtyEl.className = "alloc-qty";
                qtyEl.textContent = String(currentAllocations[groupKey][opt]);

                const plusBtn = document.createElement("button");
                plusBtn.type = "button";
                plusBtn.className = "alloc-btn";
                plusBtn.textContent = "+";

                function currentSum() {
                    return Object.values(currentAllocations[groupKey]).reduce((a, b) => a + b, 0);
                }

                function refreshRow() {
                    qtyEl.textContent = currentAllocations[groupKey][opt];
                    row.classList.toggle("active", currentAllocations[groupKey][opt] > 0);
                }

                minusBtn.addEventListener("click", function () {
                    if (currentAllocations[groupKey][opt] > 0) {
                        currentAllocations[groupKey][opt]--;
                        refreshRow();
                        errorEl.textContent = "";
                    }
                });

                plusBtn.addEventListener("click", function () {
                    if (currentSum() >= total) {
                        errorEl.textContent = "You've already picked " + total + ". Remove one before adding another.";
                        return;
                    }
                    currentAllocations[groupKey][opt]++;
                    refreshRow();
                    errorEl.textContent = "";
                });

                row.classList.toggle("active", currentAllocations[groupKey][opt] > 0);

                stepper.appendChild(minusBtn);
                stepper.appendChild(qtyEl);
                stepper.appendChild(plusBtn);
                row.appendChild(label);
                row.appendChild(stepper);
                container.appendChild(row);
            });
        }

        function validateAllocation(groupKey, total, distinctRequired, errorId) {
            const qtyMap = currentAllocations[groupKey] || {};
            const sum = Object.values(qtyMap).reduce((a, b) => a + b, 0);
            const distinctCount = Object.values(qtyMap).filter(q => q > 0).length;
            const errorEl = document.getElementById(errorId);

            if (sum !== total) {
                errorEl.textContent = "Please select " + total + " total (currently " + sum + ").";
                return false;
            }
            if (distinctRequired && distinctCount !== distinctRequired) {
                errorEl.textContent = "Please choose exactly " + distinctRequired + " different flavors.";
                return false;
            }
            return true;
        }

        // Turns the "Name (N pcs)" strings saved on a cart item's `flavors`
        // array back into an allocation map, e.g. { "Honey Garlic": 3 }.
        // Falls back to a plain count of 1 per entry if there's no "(N pcs)" suffix.
        function parseAllocationStrings(entries) {
            const map = {};
            (entries || []).forEach(function (entry) {
                const match = /^(.*)\s\((\d+)\s*pcs?\)$/.exec(entry);
                if (match) {
                    map[match[1]] = (map[match[1]] || 0) + parseInt(match[2], 10);
                } else {
                    map[entry] = (map[entry] || 0) + 1;
                }
            });
            return map;
        }

        // Rice/drinks are saved as one array entry per unit (e.g. ["Rice","Rice"]),
        // so just tally occurrences to get the allocation map.
        function tallyEntries(entries) {
            const map = {};
            (entries || []).forEach(function (entry) {
                map[entry] = (map[entry] || 0) + 1;
            });
            return map;
        }

        function openCustomization(product, editIndex) {
            editingIndex = (typeof editIndex === "number") ? editIndex : null;
            const editItem = (editingIndex !== null) ? cart[editingIndex] : null;

            const config = getProductConfiguration(product.id);
            quantityValue = editItem ? editItem.quantity : 1;
            document.getElementById("modalQuantity").textContent = quantityValue;
            document.getElementById("modalProductName").textContent = product.name;
            document.getElementById("modalProductDescription").textContent = product.description;
            document.getElementById("modalProductPrice").textContent = formatMoney(product.price);

            const addBtn = document.getElementById("addToCart");
            if (addBtn) addBtn.textContent = editItem ? "Update Item" : "Add to Cart";

            currentAllocations = { flavors: {}, rice: {}, drinks: {} };

            // FLAVORS
            if (config.flavorCount > 1 && config.pieces > 0) {
                document.getElementById("flavorSection").style.display = "block";
                const initialFlavors = editItem ? parseAllocationStrings(editItem.flavors) : {};
                renderAllocationGroup("flavorSelectors", "flavorInstruction", "flavorError", flavors, config.pieces, "flavors", config.flavorCount, initialFlavors);
            } else if (config.flavorCount === 1) {
                document.getElementById("flavorSection").style.display = "block";
                document.getElementById("flavorInstruction").textContent = "Choose 1 flavor.";
                const flavorSelectors = document.getElementById("flavorSelectors");
                flavorSelectors.innerHTML = "";
                document.getElementById("flavorError").textContent = "";

                const preselected = editItem && editItem.flavors && editItem.flavors[0];

                const group = document.createElement("div");
                group.className = "flavor-chip-group";
                flavors.forEach(function (f) {
                    const chip = document.createElement("button");
                    chip.type = "button";
                    chip.className = "flavor-chip";
                    chip.textContent = f;
                    chip.dataset.flavor = f;
                    if (preselected && f === preselected) chip.classList.add("selected");
                    chip.addEventListener("click", function () {
                        group.querySelectorAll(".flavor-chip").forEach(c => c.classList.remove("selected"));
                        chip.classList.add("selected");
                        document.getElementById("flavorError").textContent = "";
                    });
                    group.appendChild(chip);
                });
                flavorSelectors.appendChild(group);
            } else {
                document.getElementById("flavorSection").style.display = "none";
            }

            // RICE
            if (config.riceCount > 0) {
                document.getElementById("riceSection").style.display = "block";
                const initialRice = editItem ? tallyEntries(editItem.rice) : {};
                renderAllocationGroup("riceSelectors", "riceInstruction", "riceError", riceOptions, config.riceCount, "rice", null, initialRice);
            } else {
                document.getElementById("riceSection").style.display = "none";
            }

            // DRINKS
            if (config.drinkCount > 0) {
                document.getElementById("drinkSection").style.display = "block";
                const initialDrinks = editItem ? tallyEntries(editItem.drinks) : {};
                renderAllocationGroup("drinkSelectors", "drinkInstruction", "drinkError", drinkOptions, config.drinkCount, "drinks", null, initialDrinks);
            } else {
                document.getElementById("drinkSection").style.display = "none";
            }

            document.getElementById("customizationModal").classList.add("active");
        }

        document.getElementById("cancelCustomization").addEventListener("click", () => {
            editingIndex = null;
            document.getElementById("customizationModal").classList.remove("active");
        });

        document.getElementById("decreaseQuantity").addEventListener("click", () => {
            if (quantityValue > 1) quantityValue--;
            document.getElementById("modalQuantity").textContent = quantityValue;
        });
        document.getElementById("increaseQuantity").addEventListener("click", () => {
            quantityValue++;
            document.getElementById("modalQuantity").textContent = quantityValue;
        });

        document.getElementById("addToCart").addEventListener("click", () => {
            if (!currentProduct) return;
            const config = getProductConfiguration(currentProduct.id);
            let selectedFlavors = [];
            let selectedRice = [];
            let selectedDrinks = [];

            if (config.flavorCount > 1 && config.pieces > 0) {
                if (!validateAllocation("flavors", config.pieces, config.flavorCount, "flavorError")) return;
                Object.keys(currentAllocations.flavors).forEach(f => {
                    const qty = currentAllocations.flavors[f];
                    if (qty > 0) selectedFlavors.push(f + " (" + qty + " pcs)");
                });
            } else if (config.flavorCount === 1) {
                const chosen = document.querySelector(".flavor-chip.selected");
                if (!chosen) {
                    document.getElementById("flavorError").textContent = "Please select a flavor.";
                    return;
                }
                selectedFlavors.push(chosen.dataset.flavor);
            }

            if (config.riceCount > 0) {
                if (!validateAllocation("rice", config.riceCount, null, "riceError")) return;
                Object.keys(currentAllocations.rice).forEach(r => {
                    const qty = currentAllocations.rice[r];
                    for (let i = 0; i < qty; i++) selectedRice.push(r);
                });
            }

            if (config.drinkCount > 0) {
                if (!validateAllocation("drinks", config.drinkCount, null, "drinkError")) return;
                Object.keys(currentAllocations.drinks).forEach(d => {
                    const qty = currentAllocations.drinks[d];
                    for (let i = 0; i < qty; i++) selectedDrinks.push(d);
                });
            }

            const itemData = {
                id: currentProduct.id,
                name: currentProduct.name,
                price: currentProduct.price,
                quantity: quantityValue,
                flavors: selectedFlavors,
                rice: selectedRice,
                drinks: selectedDrinks
            };

            if (editingIndex !== null) {
                cart[editingIndex] = itemData;
            } else {
                cart.push(itemData);
            }
            editingIndex = null;

            updateCart();
            document.getElementById("customizationModal").classList.remove("active");
        });

        function toggleFlavorChip(chip, group) {
            const max = parseInt(group.dataset.max, 10);
            const selectedCount = group.querySelectorAll(".flavor-chip.selected").length;
            const errorEl = document.getElementById("flavorError");

            if (chip.classList.contains("selected")) {
                chip.classList.remove("selected");
                errorEl.textContent = "";
                return;
            }

            if (selectedCount >= max) {
                errorEl.textContent = "You can only choose " + max + " flavor(s). Tap a selected one to swap it out.";
                return;
            }

            chip.classList.add("selected");
            errorEl.textContent = "";
        }

        function updateCart() {
            if (!cartItems) return;
            cartItems.innerHTML = "";

            if (cart.length === 0) {
                cartItems.innerHTML = `
                    <div class="empty-cart">
                        <i class="fas fa-cart-shopping"></i>
                        <h3>No items added</h3>
                        <p>Select a product to start an order</p>
                    </div>
                `;
                if (itemCount) itemCount.textContent = "0 items";
                updateSummary();
                return;
            }

            let totalItems = 0;
            cart.forEach(function (item, index) {
                totalItems += item.quantity;
                const itemTotal = item.price * item.quantity;
                const cartItem = document.createElement("div");
                cartItem.className = "cart-item";
                cartItem.innerHTML = `
                    <div class="cart-item-info">
                        <h4>${item.name}</h4>
                        <small>₱${item.price.toFixed(2)} x ${item.quantity}${item.flavors && item.flavors.length ? ' • ' + item.flavors.join(', ') : ''}${item.rice && item.rice.length ? ' • ' + item.rice.join(', ') : ''}${item.drinks && item.drinks.length ? ' • ' + item.drinks.join(', ') : ''}</small>
                    </div>
                    <div class="quantity-controls">
                        <button class="quantity-btn decrease" data-index="${index}">−</button>
                        <span style="font-size:12px; font-weight:600;">${item.quantity}</span>
                        <button class="quantity-btn increase" data-index="${index}">+</button>
                    </div>
                    <strong class="cart-item-total">₱${itemTotal.toFixed(2)}</strong>
                    <button class="edit-item" data-index="${index}" title="Edit item"><i class="fas fa-pen"></i></button>
                    <button class="remove-item" data-index="${index}"><i class="fas fa-trash"></i></button>
                `;
                cartItems.appendChild(cartItem);
            });

            itemCount.textContent = totalItems + (totalItems === 1 ? " item" : " items");
            addCartButtonEvents();
            updateSummary();
        }

        function addCartButtonEvents() {
            document.querySelectorAll(".increase").forEach(button => {
                button.addEventListener("click", function () {
                    cart[parseInt(this.dataset.index)].quantity++;
                    updateCart();
                });
            });
            document.querySelectorAll(".decrease").forEach(button => {
                button.addEventListener("click", function () {
                    const index = parseInt(this.dataset.index);
                    cart[index].quantity--;
                    if (cart[index].quantity <= 0) cart.splice(index, 1);
                    updateCart();
                });
            });
            document.querySelectorAll(".remove-item").forEach(button => {
                button.addEventListener("click", function () {
                    cart.splice(parseInt(this.dataset.index), 1);
                    updateCart();
                });
            });
            document.querySelectorAll(".edit-item").forEach(button => {
                button.addEventListener("click", function () {
                    const index = parseInt(this.dataset.index);
                    const item = cart[index];
                    currentProduct = {
                        id: item.id,
                        name: item.name,
                        price: item.price,
                        description: ""
                    };
                    openCustomization(currentProduct, index);
                });
            });
        }

        function calculateSummary() {
            let subtotal = 0;
            cart.forEach(item => subtotal += item.price * item.quantity);
            let discount = parseFloat(discountInput ? discountInput.value : 0) || 0;
            discount = Math.max(0, Math.min(100, discount));
            const discountAmount = subtotal * (discount / 100);
            const total = subtotal - discountAmount;
            const payment = parseFloat(paymentInput ? paymentInput.value : 0) || 0;
            const change = payment - total;
            return { subtotal, discount, discountAmount, total, payment, change };
        }

        function updateSummary() {
            const summary = calculateSummary();
            if (subtotalElement) subtotalElement.textContent = formatMoney(summary.subtotal);
            if (totalElement) totalElement.textContent = formatMoney(summary.total);
            if (changeElement) changeElement.textContent = summary.change >= 0 ? formatMoney(summary.change) : "₱0.00";
        }

        if (discountInput) discountInput.addEventListener("input", updateSummary);
        if (paymentInput) paymentInput.addEventListener("input", updateSummary);

        if (clearCartButton) {
            clearCartButton.addEventListener("click", function () {
                cart = [];
                if (paymentInput) paymentInput.value = "";
                if (discountInput) discountInput.value = 0;
                if (noteInput) noteInput.value = "";
                updateCart();
            });
        }

        if (completeButton) {
            completeButton.addEventListener("click", async function () {
                if (cart.length === 0) {
                    alert("Please add items to the order first.");
                    return;
                }
                const summary = calculateSummary();
                const method = paymentMethodInput ? paymentMethodInput.value : "Cash";

                if (method === "Cash") {
                    if (summary.payment <= 0) {
                        alert("Please enter the customer's payment.");
                        paymentInput.focus();
                        return;
                    }
                    if (summary.payment < summary.total) {
                        alert("Insufficient payment.");
                        paymentInput.focus();
                        return;
                    }
                } else {
                    summary.payment = summary.total;
                    summary.change = 0;
                }

                const customerName = (document.getElementById("walkinCustomerName")?.value || "Walk-in Customer").trim() || "Walk-in Customer";
                const orderType = document.getElementById("walkinOrderType")?.value || "Walk-In";
                const tableNumber = (walkinTableNumberInput?.value || "").trim();

                if (orderType === "Dine-In" && tableNumber === "") {
                    alert("Please enter the table number for this Dine-In order.");
                    walkinTableNumberInput.focus();
                    return;
                }

                const originalText = completeButton.innerHTML;
                completeButton.disabled = true;
                completeButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending to Kitchen...';

                try {
                    const r = await fetch('api.php?action=create_walkin_order', {
                        method: 'POST',
                        headers: {'Content-Type':'application/json'},
                        body: JSON.stringify({
                            customer_name: customerName,
                            order_type: orderType,
                            table_number: tableNumber,
                            payment_method: method,
                            payment_status: 'Paid',
                            discount: summary.discount,
                            total: summary.total,
                            items: cart.map(item => ({id:item.id, quantity:item.quantity, price:item.price, flavors:item.flavors || []}))
                        })
                    });
                    const d = await r.json();
                    if (!d.success) throw new Error(d.message || 'Could not create the order.');

                    const transaction = {
                        id: d.order_number,
                        date: new Date().toLocaleString(),
                        items: [...cart],
                        subtotal: summary.subtotal,
                        discount: summary.discount,
                        total: summary.total,
                        payment: summary.payment,
                        change: summary.change,
                        paymentMethod: method,
                        status: "Preparing",
                        orderType: orderType,
                        tableNumber: tableNumber,
                        customerName: customerName
                    };

                    showReceipt(transaction);
                    alert(`${d.order_number} sent to the kitchen. The kitchen will mark it Ready when finished.`);

                    cart = [];
                    if (paymentInput) paymentInput.value = "";
                    if (discountInput) discountInput.value = 0;
                    if (noteInput) noteInput.value = "";
                    if (walkinTableNumberInput) walkinTableNumberInput.value = "";
                    updateCart();
                    loadConnectedOrders();
                    refreshNextTransactionNumber();
                } catch (err) {
                    console.error(err);
                    alert(err.message || "Unable to send the walk-in order to the kitchen.");
                } finally {
                    completeButton.disabled = false;
                    completeButton.innerHTML = originalText;
                }
            });
        }

        function showReceipt(transaction) {
            const receiptOverlay = document.getElementById("receiptOverlay");
            document.getElementById("receiptTransactionNumber").textContent = transaction.id;
            document.getElementById("receiptDateTime").textContent = transaction.date;
            const tableRow = document.getElementById("receiptTableNumberRow");
            if (tableRow) {
                if (transaction.tableNumber) {
                    document.getElementById("receiptTableNumber").textContent = transaction.tableNumber;
                    tableRow.style.display = "flex";
                } else {
                    tableRow.style.display = "none";
                }
            }
            document.getElementById("receiptSubtotal").textContent = formatMoney(transaction.subtotal);
            document.getElementById("receiptTotal").textContent = formatMoney(transaction.total);
            document.getElementById("receiptPayment").textContent = formatMoney(transaction.payment);
            document.getElementById("receiptChange").textContent = formatMoney(transaction.change);

            const receiptItems = document.getElementById("receiptItems");
            receiptItems.innerHTML = "";
            transaction.items.forEach(item => {
                receiptItems.innerHTML += `<div style="margin:4px 0;"><span>${item.name} x${item.quantity}</span> <strong>${formatMoney(item.price * item.quantity)}</strong></div>`;
            });

            receiptOverlay.classList.add("active");
        }

        document.getElementById("closeReceipt").addEventListener("click", () => {
            document.getElementById("receiptOverlay").classList.remove("active");
        });
        document.getElementById("printReceipt").addEventListener("click", () => window.print());

        function renderSummaryRecords(period) {
            period = period || (salesPeriodDropdown ? salesPeriodDropdown.value : "weekly");
            const tbody = document.getElementById("transactionRecordsList");
            const now = new Date();
            const filtered = filterRecordsByPeriod(allSalesRecords, period, now);
            tbody.innerHTML = "";

            let totalSalesAmount = 0;
            let totalItemsSold = 0;

            if (filtered.length === 0) {
                tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;">No transaction records found.</td></tr>`;
            } else {
                filtered.forEach(rec => {
                    const total = Number(rec.total) || 0;
                    const itemsCount = Number(rec.items_count) || 0;
                    totalSalesAmount += total;
                    totalItemsSold += itemsCount;

                    tbody.innerHTML += `
                        <tr>
                            <td>${orderNumberFor(rec)}</td>
                            <td>${new Date(rec.created_at.replace(' ', 'T')).toLocaleString()}</td>
                            <td>${itemsCount} item(s)</td>
                            <td>${formatMoney(total)}</td>
                            <td>${rec.order_type || '-'}</td>
                            <td>${rec.payment_method || '-'}</td>
                            <td>${rec.payment_status || '-'}</td>
                            <td><span style="color:var(--success); font-weight:bold;">${rec.status}</span></td>
                        </tr>
                    `;
                });
            }

            const buckets = buildChartBuckets(filtered, period, now);
            renderSalesChart(buckets.labels, buckets.data, buckets.label);

            document.getElementById("summaryTotalSales").textContent = formatMoney(totalSalesAmount);
            document.getElementById("summaryTotalOrders").textContent = filtered.length;
            document.getElementById("summaryItemsSold").textContent = totalItemsSold;
        }

        let salesChartInstance = null;
        function renderSalesChart(labels, data, label) {
            const ctx = document.getElementById("salesChart")?.getContext("2d");
            if (!ctx) return;

            labels = labels || [];
            data = data || [];
            label = label || 'Sales (₱)';

            if (salesChartInstance) {
                salesChartInstance.data.labels = labels;
                salesChartInstance.data.datasets[0].data = data;
                salesChartInstance.data.datasets[0].label = label;
                salesChartInstance.update();
                return;
            }

            salesChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: label,
                        data: data,
                        borderColor: '#556b3d',
                        backgroundColor: 'rgba(85, 107, 61, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: document.body.classList.contains("dark-mode") ? '#334155' : '#edf0f2'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        let selectedOrderId = null;
        let currentFilter = "all";
        let onlineOrders = [];
        let orderSyncBusy = false;

        function saveOnlineOrders() {
            localStorage.setItem("onlineOrders", JSON.stringify(onlineOrders));
        }

        async function loadConnectedOrders() {
            try {
                if (orderSyncBusy) return;
                orderSyncBusy = true;
                const r = await fetch('api.php?action=orders&_=' + Date.now(), { cache: 'no-store' });
                const d = await r.json();
                if (!d.success) throw new Error(d.message);
                onlineOrders = d.orders.map(o => ({
                    id:o.id, customer:o.customer_name || o.account_name || 'Customer', phone:o.phone || '',
                    orderType:o.order_type || '', tableNumber:o.table_number || '',
                    date:o.created_at, payment:o.payment_method, payment_status:o.payment_status, status:o.status.toLowerCase(),
                    total:Number(o.total), item_count:(o.items ? o.items.split(',').filter(Boolean).length : 0), items:[], notes:''
                }));
                updateBadge(); displayOnlineOrders();
            } catch(e) { console.error('Cashier order sync failed:',e); }
            finally { orderSyncBusy = false; }
        }

        function updateBadge() {
            const badge = document.getElementById("onlineOrderBadge");
            if(badge) {
                const pending = onlineOrders.filter(o => o.status === "pending").length;
                badge.textContent = pending;
            }
        }

        function displayOnlineOrders() {
            const orderList = document.getElementById("onlineOrderList");
            if (!orderList) return;
            
            const filtered = onlineOrders.filter(order => {
                const matchesFilter = currentFilter === "all" || order.status === currentFilter;
                return matchesFilter;
            });

            orderList.innerHTML = "";
            if (filtered.length === 0) {
                orderList.innerHTML = `<div class="empty-orders" style="text-align:center; padding:30px;"><i class="fas fa-box-open" style="font-size:36px; margin-bottom:8px;"></i><h3 style="font-size:15px;">No Orders Found</h3></div>`;
                return;
            }

            filtered.forEach(order => {
                const total = Number(order.total || 0);
                const card = document.createElement("div");
                card.className = "online-order-card" + (selectedOrderId === order.id ? " selected" : "");
                card.innerHTML = `
                    <div class="order-card-top">
                        <div>
                            <h3>${order.id}</h3>
                            <small>${order.date}</small>
                        </div>
                        <span class="status-badge ${order.status}">${order.status}</span>
                    </div>
                    <div class="customer-name"><i class="fas fa-user"></i> ${order.customer}</div>
                    <div class="order-card-bottom">
                        <span>${order.item_count || 0} item(s)</span>
                        <strong>${formatMoney(total)}</strong>
                    </div>
                `;
                card.addEventListener("click", async () => {
                    selectedOrderId = order.id;
                    displayOnlineOrders();
                    try {
                        const r = await fetch(`api.php?action=order_details&id=${order.id}`);
                        const d = await r.json();
                        const detail = d.order || order;
                        detail.id = order.id; detail.status = order.status.toLowerCase(); detail.customer = order.customer; detail.phone = order.phone; detail.payment = order.payment; detail.notes = "";
                        detail.tableNumber = (d.order && d.order.table_number) || order.tableNumber || "";
                        displayOrderDetails(detail);
                    } catch(e) { displayOrderDetails(order); }
                });
                orderList.appendChild(card);
            });
        }

        function displayOrderDetails(order) {
            const orderDetails = document.getElementById("orderDetails");
            const total = order.items.reduce((acc, item) => acc + (item.price * item.quantity), 0);

            orderDetails.innerHTML = `
                <div class="details-header">
                    <div>
                        <h2>Order Details</h2>
                        <p>${order.id}</p>
                    </div>
                    <span class="status-badge ${order.status}">${order.status}</span>
                </div>
                <div class="customer-details">
                    <h3>Customer Information</h3>
                    <div class="detail-row"><i class="fas fa-user"></i><span>${order.customer}</span></div>
                    <div class="detail-row"><i class="fas fa-phone"></i><span>${order.phone}</span></div>
                    ${order.tableNumber ? `<div class="detail-row"><i class="fas fa-chair"></i><span>Table ${order.tableNumber}</span></div>` : ''}
                </div>
                <div class="order-items">
                    <h3>Order Items</h3>
                    ${order.items.map(item => `
                        <div class="item-row">
                            <div>
                                <strong style="font-size:13px;">${item.name}</strong>
                                <small>${item.quantity} × ${formatMoney(item.price)}</small>
                            </div>
                            <strong style="font-size:13px;">${formatMoney(item.quantity * item.price)}</strong>
                        </div>
                    `).join("")}
                </div>
                <div class="order-summary-block">
                    <div style="display:flex; justify-content:space-between; margin:4px 0; font-size:13px;"><span>Payment Method</span><strong>${order.payment}</strong></div>
                    <div style="display:flex; justify-content:space-between; margin:4px 0; font-size:16px;"><span>Total</span><strong style="color:#556b3d;">${formatMoney(total)}</strong></div>
                </div>
                <div class="order-notes-box">
                    <h3>Customer Notes</h3>
                    <p>${order.notes}</p>
                </div>
                <div class="order-actions">
                    ${getActionButton(order)}
                </div>
            `;

            const actionBtn = document.getElementById("updateOrderStatus");
            if (actionBtn) {
                actionBtn.addEventListener("click", async () => {
                    const flow = { pending: "Preparing", ready: "Completed" };
                    const nextStatus = flow[order.status];
                    if (!nextStatus) return;
                    try {
                        const r = await fetch('api.php?action=update_order_status',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({order_id:order.id,status:nextStatus})});
                        const d = await r.json();
                        if (!d.success) throw new Error(d.message);
                        await loadConnectedOrders();
                        const updated = onlineOrders.find(x => x.id === order.id);
                        if (updated) {
                            const rd = await fetch(`api.php?action=order_details&id=${order.id}`);
                            const dd = await rd.json();
                            const detail = dd.order || updated; detail.id=updated.id; detail.status=updated.status; detail.customer=updated.customer; detail.phone=updated.phone; detail.payment=updated.payment; detail.notes='';
                            displayOrderDetails(detail);
                        }
                    } catch(e) { alert(e.message); }
                });
            }
        }

        function getActionButton(order) {
            if (order.status === "pending") return `<button class="action-btn confirm-btn" id="updateOrderStatus"><i class="fas fa-check"></i> Confirm Order</button>`;
            if (order.status === "preparing") return `<div style="text-align:center; color:#2563eb; font-weight:600; font-size:13px; padding:10px;"><i class="fas fa-utensils"></i> Sent to Kitchen — waiting for kitchen to mark it Ready.</div>`;
            if (order.status === "ready") return `<button class="action-btn complete-btn-online" id="updateOrderStatus"><i class="fas fa-check-double"></i> Complete Order</button>`;
            return `<div style="text-align:center; color:#16a34a; font-weight:600; font-size:13px;"><i class="fas fa-circle-check"></i> This order has been completed.</div>`;
        }

        document.querySelectorAll(".filter-btn").forEach(btn => {
            btn.addEventListener("click", function() {
                document.querySelectorAll(".filter-btn").forEach(b => b.classList.remove("active"));
                this.classList.add("active");
                currentFilter = this.dataset.filter;
                displayOnlineOrders();
            });
        });

        function formatMoney(amount) {
            return "₱" + Number(amount || 0).toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        loadConnectedOrders();
        // Near-real-time shared order board: refresh every second without page reload.
        setInterval(() => {
            if (!document.hidden) loadConnectedOrders();
        }, 1000);

        // Live Clock Updater
        function updateClock() {
            const clockEl = document.getElementById("clock");
            if (clockEl) {
                const now = new Date();
                let hours = now.getHours();
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                const ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12 || 12;
                clockEl.textContent = `${hours}:${minutes}:${seconds} ${ampm}`;
            }
        }
        setInterval(updateClock, 1000);
        updateClock();
        updateBadge();
    });