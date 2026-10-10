/*
 * Sweet Chicken POS - Customer Dashboard behavior
 * Extracted from customer_dashboard.php
 */

document.addEventListener("DOMContentLoaded", function () {
    let cart = [];
    let selectedProduct = null;
    let selectedQuantity = 1;
    let selectedFlavorButtons = [];

    /* HERO AUTO-SLIDE BACKGROUND LOGIC */
    const heroSection = document.getElementById("home");
    // Add paths to your slide images here
    const heroImages = [
        "images/668999135_122187738278731309_9045860258197357056_n.jpg",
        "images/524115301_122152772348731309_886394131801260326_n.jpg",
        // Add more image filenames here if available, e.g.:
        // "images/slide2.jpg",
        // "images/slide3.jpg"
    ];
    let currentHeroIndex = 0;

    function changeHeroBackground() {
        if (heroImages.length > 1) {
            currentHeroIndex = (currentHeroIndex + 1) % heroImages.length;
            heroSection.style.backgroundImage = `linear-gradient(rgba(0, 0, 0, 0.55), rgba(0, 0, 0, 0.55)), url("${heroImages[currentHeroIndex]}")`;
        }
    }

    if (heroImages.length > 1) {
        setInterval(changeHeroBackground, 3000); // Changes image every 5 seconds
    }

    /* PROFILE DROPDOWN HANDLER */
    const profileBtn = document.getElementById("profileBtn");
    const profileMenu = document.getElementById("profileMenu");
    const profileOverlay = document.getElementById("profileOverlay");

    function toggleProfile(forceState) {
        const isActive = forceState !== undefined ? forceState : !profileMenu.classList.contains("active");
        if (profileMenu) profileMenu.classList.toggle("active", isActive);
        if (profileOverlay) profileOverlay.classList.toggle("active", isActive);
    }

    if (profileBtn && profileMenu) {
        profileBtn.addEventListener("click", (e) => { 
            e.stopPropagation(); 
            toggleProfile(); 
        });
    }

    if (profileOverlay) {
        profileOverlay.addEventListener("click", () => {
            toggleProfile(false);
        });
    }

    document.addEventListener("click", (e) => {
        if (profileMenu && profileBtn && !profileMenu.contains(e.target) && !profileBtn.contains(e.target)) {
            toggleProfile(false);
        }
    });

    /* DARK MODE TOGGLE FUNCTIONALITY */
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
        toggleProfile(false);
    });

    /* MENU CATEGORY FILTER */
    const categoryButtons = document.querySelectorAll(".category-btn");
    const productCards = document.querySelectorAll(".product-card");

    categoryButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            const selectedCategory = this.getAttribute("data-category");
            categoryButtons.forEach(btn => btn.classList.remove("active"));
            this.classList.add("active");

            productCards.forEach(function (card) {
                const cardCategory = card.getAttribute("data-category");
                if (selectedCategory === "all" || cardCategory === selectedCategory) {
                    card.style.display = "";
                } else {
                    card.style.display = "none";
                }
            });
        });
    });

    // Keep customer menu prices/availability synchronized with Admin menu management.
    async function syncCustomerMenu() {
        try {
            const res = await fetch('api.php?action=menu');
            const data = await res.json();
            if (!data.success) return;
            const byCode = Object.fromEntries(data.menu.map(m => [m.code, m]));
            document.querySelectorAll('.product-card').forEach(card => {
                const item = byCode[card.dataset.id];
                if (!item) return;
                card.dataset.price = item.price;
                const priceEl = card.querySelector('.product-footer strong');
                if (priceEl) priceEl.textContent = `₱${Number(item.price).toFixed(2)}`;
                const btn = card.querySelector('.add-button');
                const unavailable = item.status !== 'Available' || Number(item.stock) <= 0;
                if (btn) { btn.disabled = unavailable; btn.innerHTML = unavailable ? '<i class="fas fa-ban"></i> Unavailable' : '<i class="fas fa-plus"></i> Add'; }
                card.style.opacity = unavailable ? '0.55' : '1';
            });
        } catch(e) { console.error('Menu sync failed', e); }
    }
    syncCustomerMenu();
    setInterval(syncCustomerMenu, 10000);

    const trackOrderBtn = document.getElementById("trackOrderBtn");
    const trackOrderModal = document.getElementById("trackOrderModal");
    const closeTrackModal = document.getElementById("closeTrackModal");
    let trackedOrderId = null;
    let customerSyncBusy = false;

    function updateTrackingTimeline(status) {
        const orderStatus = String(status || 'Pending').toLowerCase();
        const steps = [
            ['stepPlaced', true],
            ['stepPreparing', ['preparing','ready','completed'].includes(orderStatus)],
            ['stepReady', ['ready','completed'].includes(orderStatus)],
            ['stepCompleted', orderStatus === 'completed']
        ];
        steps.forEach(([id, done]) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.toggle('completed', done);
            el.classList.toggle('active', !done && (id === 'stepPreparing' || id === 'stepReady'));
        });
    }

    async function refreshTrackedOrder(showError = false) {
        if (!trackedOrderId || customerSyncBusy) return;
        customerSyncBusy = true;
        try {
            const response = await fetch(`api.php?action=order_details&id=${trackedOrderId}&_=${Date.now()}`, { cache: 'no-store' });
            const details = await response.json();
            if (!details.success || !details.order) return;
            const order = details.order;
            document.getElementById("trackModalOrderNum").textContent = `Order #${order.order_number}`;
            document.getElementById("trackStatusBadge").textContent = order.status || "Pending";
            document.getElementById("trackOrderType").textContent = order.order_type || "Dine-In";
            document.getElementById("trackPaymentMethod").textContent = order.payment_method || "Cash";
            document.getElementById("trackCustomerInfo").textContent = `${order.customer_name || order.account_name || ''} (${order.phone || ''})`;
            document.getElementById("trackTotal").textContent = `₱${Number(order.total).toFixed(2)}`;
            updateTrackingTimeline(order.status);

            const summaryContainer = document.getElementById("trackItemsSummary");
            if (summaryContainer && !summaryContainer.dataset.loaded) {
                summaryContainer.innerHTML = '';
                (order.items || []).forEach(item => {
                    const row = document.createElement('div'); row.className='track-item-row';
                    row.innerHTML = `<span>${item.quantity}x ${item.name}</span><strong>₱${(item.price*item.quantity).toFixed(2)}</strong>`;
                    summaryContainer.appendChild(row);
                });
                summaryContainer.dataset.loaded = '1';
            }
        } catch (e) {
            if (showError) alert('Unable to load order status.');
        } finally {
            customerSyncBusy = false;
        }
    }

    trackOrderBtn?.addEventListener("click", async function () {
        try {
            const saved = JSON.parse(localStorage.getItem('lastOrder') || 'null');
            const response = await fetch('api.php?action=orders&_=' + Date.now(), { cache: 'no-store' });
            const data = await response.json();
            if (!data.success || !data.orders.length) { alert("No orders found. Please place an order first."); return; }
            const order = (saved?.orderId && data.orders.find(o => Number(o.id) === Number(saved.orderId))) || data.orders[0];
            trackedOrderId = Number(order.id);
            const summaryContainer = document.getElementById("trackItemsSummary");
            if (summaryContainer) delete summaryContainer.dataset.loaded;
            trackOrderModal.classList.add("show");
            await refreshTrackedOrder(true);
        } catch (e) { alert('Unable to load order status.'); }
    });

    closeTrackModal?.addEventListener("click", () => {
        trackOrderModal.classList.remove("show");
    });

    trackOrderModal?.addEventListener("click", (e) => {
        if (e.target === trackOrderModal) trackOrderModal.classList.remove("show");
    });

    // Live customer tracking: status changes appear automatically while the tracker is open.
    setInterval(() => {
        if (!document.hidden && trackOrderModal?.classList.contains('show')) refreshTrackedOrder(false);
    }, 1000);

    const flavors = ["Honey Garlic", "Sweet Chili", "Buffalo", "Garlic Parmesan", "Salted Egg", "Original", "Yangnyeom"];

    const customModal = document.getElementById("customModal");
    const closeCustomModal = document.getElementById("closeCustomModal");
    const cancelCustomModal = document.getElementById("cancelCustomModal");
    const addToCartButton = document.getElementById("addToCart");
    const customProductName = document.getElementById("customProductName");
    const customProductDescription = document.getElementById("customProductDescription");
    const customProductPrice = document.getElementById("customProductPrice");
    const flavorOption = document.getElementById("flavorOption");
    const flavorSelectors = document.getElementById("flavorSelectors");
    const flavorError = document.getElementById("flavorError");
    const withOption = document.getElementById("withOption");
    const withSelectors = document.getElementById("withSelectors");
    const riceOption = document.getElementById("riceOption");
    const riceSelect = document.getElementById("riceSelect");
    const addDrinkOption = document.getElementById("addDrinkOption");
    const addDrinkSelect = document.getElementById("addDrinkSelect");
    const juiceOption = document.getElementById("juiceOption");
    const juiceSelect = document.getElementById("juiceSelect");
    const customQuantity = document.getElementById("customQuantity");
    const decreaseQuantity = document.getElementById("decreaseQuantity");
    const increaseQuantity = document.getElementById("increaseQuantity");
    const cartItems = document.getElementById("cartItems");
    const cartCount = document.getElementById("cartCount");
    const cartItemCount = document.getElementById("cartItemCount");
    const cartTotal = document.getElementById("cartTotal");
    const openCart = document.getElementById("openCart");
    const closeCart = document.getElementById("closeCart");
    const cartDrawer = document.getElementById("cartDrawer");
    const cartOverlay = document.getElementById("cartOverlay");

    openCart?.addEventListener("click", () => { cartDrawer.classList.add("open"); cartOverlay.classList.add("show"); });
    closeCart?.addEventListener("click", () => { cartDrawer.classList.remove("open"); cartOverlay.classList.remove("show"); });
    cartOverlay?.addEventListener("click", () => { cartDrawer.classList.remove("open"); cartOverlay.classList.remove("show"); });

    function openCustomization(card) {
        selectedProduct = {
            id: card.dataset.id,
            name: card.dataset.name,
            price: Number(card.dataset.price),
            flavors: Number(card.dataset.flavors || 0),
            rice: Number(card.dataset.rice || 0),
            drinks: Number(card.dataset.drinks || 0),
            isJuice: card.dataset.juice === "true"
        };

        selectedQuantity = 1;
        selectedFlavorButtons = [];
        customQuantity.textContent = "1";
        customProductName.textContent = selectedProduct.name;
        customProductDescription.textContent = card.querySelector("p")?.innerText || "";
        customProductPrice.textContent = `₱${selectedProduct.price.toFixed(2)}`;
        flavorSelectors.innerHTML = "";
        flavorError.textContent = "";
        withSelectors.innerHTML = "";
        addDrinkSelect.value = "";
        riceSelect.value = "";

        if (selectedProduct.flavors > 0) {
            flavorOption.style.display = "block";
            flavorSelectors.innerHTML = `
                <div class="flavor-selection-info">Choose ${selectedProduct.flavors} flavor(s)</div>
                <div class="flavor-button-grid">
                    ${flavors.map(flavor => `<button type="button" class="flavor-button" data-flavor="${flavor}">${flavor}</button>`).join("")}
                </div>
                <div class="selected-flavors-display"><strong>Selected:</strong> <span id="selectedFlavorsText">None</span></div>
            `;
            flavorSelectors.querySelectorAll(".flavor-button").forEach(button => {
                button.addEventListener("click", function () {
                    if (selectedFlavorButtons.length >= selectedProduct.flavors) return;
                    selectedFlavorButtons.push(this.dataset.flavor);
                    this.classList.add("selected");
                    updateSelectedFlavors();
                });
            });
        } else {
            flavorOption.style.display = "none";
        }

        if (selectedProduct.drinks > 0) {
            withOption.style.display = "block";
            withSelectors.innerHTML = `
                <select class="with-selector drink-rice-selector" style="margin-bottom:8px;"><option value="Iced Tea" selected>1. Iced Tea</option></select>
                <select class="with-selector drink-rice-selector" style="margin-bottom:8px;"><option value="Iced Tea" selected>2. Iced Tea</option></select>
                <select class="with-selector drink-rice-selector" style="margin-bottom:8px;"><option value="Chao Fan Rice" selected>3. Chao Fan Rice</option><option value="Plain Rice">3. Plain Rice</option></select>
                <select class="with-selector drink-rice-selector" style="margin-bottom:8px;"><option value="Chao Fan Rice" selected>4. Chao Fan Rice</option><option value="Plain Rice">4. Plain Rice</option></select>
            `;
        } else {
            withOption.style.display = "none";
        }

        addDrinkOption.style.display = "block";
        riceOption.style.display = "block";
        riceSelect.innerHTML = `<option value="">No Additional Rice</option><option value="Chao Fan Rice">Chao Fan Rice</option><option value="Plain Rice">Plain Rice</option>`;
        
        if (selectedProduct.isJuice) {
            juiceOption.style.display = "block";
        } else {
            juiceOption.style.display = "none";
        }

        customModal.classList.add("show");
    }

    function updateSelectedFlavors() {
        const textElem = document.getElementById("selectedFlavorsText");
        if (textElem) {
            textElem.textContent = selectedFlavorButtons.length > 0 ? selectedFlavorButtons.join(", ") : "None";
        }
    }

    document.querySelectorAll(".product-card").forEach(card => {
        card.querySelector(".add-button")?.addEventListener("click", () => openCustomization(card));
    });

    function closeCustomization() {
        customModal.classList.remove("show");
        selectedProduct = null;
        selectedFlavorButtons = [];
    }

    closeCustomModal?.addEventListener("click", closeCustomization);
    cancelCustomModal?.addEventListener("click", closeCustomization);

    decreaseQuantity?.addEventListener("click", () => {
        if (selectedQuantity > 1) {
            selectedQuantity--;
            customQuantity.textContent = selectedQuantity;
        }
    });

    increaseQuantity?.addEventListener("click", () => {
        selectedQuantity++;
        customQuantity.textContent = selectedQuantity;
    });

    addToCartButton?.addEventListener("click", () => {
        if (!selectedProduct) return;
        if (selectedProduct.flavors > 0 && selectedFlavorButtons.length !== selectedProduct.flavors) {
            flavorError.textContent = `Please choose all ${selectedProduct.flavors} flavor(s).`;
            return;
        }

        const withItems = [];
        document.querySelectorAll(".with-selector").forEach(select => withItems.push(select.value));

        const item = {
            id: selectedProduct.id + "-" + Date.now(),
            productId: selectedProduct.id,
            name: selectedProduct.name,
            price: selectedProduct.price,
            quantity: selectedQuantity,
            flavors: [...selectedFlavorButtons],
            withItems: [...withItems],
            addDrink: addDrinkSelect.value,
            addRice: riceSelect.value,
            juice: selectedProduct.isJuice ? juiceSelect.value : ""
        };

        cart.push(item);
        renderCart();
        closeCustomization();
    });

    function renderCart() {
        cartItems.innerHTML = "";
        if (cart.length === 0) {
            cartItems.innerHTML = `
                <div class="empty-cart">
                    <i class="fas fa-cart-shopping"></i>
                    <h3>Your cart is empty</h3>
                    <p>Add delicious food to get started.</p>
                </div>
            `;
            cartCount.textContent = "0";
            cartItemCount.textContent = "0 items";
            cartTotal.textContent = "₱0.00";
            return;
        }

        let total = 0;
        let itemCount = 0;

        cart.forEach((item, index) => {
            const itemTotal = item.price * item.quantity;
            total += itemTotal;
            itemCount += item.quantity;

            const itemElement = document.createElement("div");
            itemElement.className = "cart-item";
            itemElement.innerHTML = `
                <div class="cart-item-top">
                    <div>
                        <h4>${item.name}</h4>
                        ${item.flavors.length > 0 ? `<small>Flavors: ${item.flavors.join(", ")}</small>` : ""}
                        ${item.withItems.length > 0 ? `<small>With: ${item.withItems.join(", ")}</small>` : ""}
                        ${item.addDrink ? `<small>Add Drink: ${item.addDrink}</small>` : ""}
                        ${item.addRice ? `<small>Add Rice: ${item.addRice}</small>` : ""}
                    </div>
                    <strong>₱${itemTotal.toFixed(2)}</strong>
                </div>
                <div class="cart-controls">
                    <button class="minus-btn" data-index="${index}" type="button">−</button>
                    <span>${item.quantity}</span>
                    <button class="plus-btn" data-index="${index}" type="button">+</button>
                    <button class="remove-btn" data-index="${index}" type="button">Remove</button>
                </div>
            `;
            cartItems.appendChild(itemElement);
        });

        cartCount.textContent = itemCount;
        cartItemCount.textContent = `${itemCount} item${itemCount !== 1 ? "s" : ""}`;
        cartTotal.textContent = `₱${total.toFixed(2)}`;
        attachCartEvents();
    }

    function attachCartEvents() {
        document.querySelectorAll(".minus-btn").forEach(button => {
            button.addEventListener("click", function () {
                const index = Number(this.dataset.index);
                if (cart[index].quantity > 1) cart[index].quantity--;
                else cart.splice(index, 1);
                renderCart();
            });
        });

        document.querySelectorAll(".plus-btn").forEach(button => {
            button.addEventListener("click", function () {
                const index = Number(this.dataset.index);
                cart[index].quantity++;
                renderCart();
            });
        });

        document.querySelectorAll(".remove-btn").forEach(button => {
            button.addEventListener("click", function () {
                const index = Number(this.dataset.index);
                cart.splice(index, 1);
                renderCart();
            });
        });
    }

    /* CHECKOUT & PLACING ORDER */
    const checkoutButton = document.getElementById("checkoutButton");
    const checkoutModal = document.getElementById("checkoutModal");
    const closeCheckout = document.getElementById("closeCheckout");
    const backToCart = document.getElementById("backToCart");
    const checkoutTotal = document.getElementById("checkoutTotal");
    const checkoutOrderSummary = document.getElementById("checkoutOrderSummary");
    const paymentMethods = document.getElementById("paymentMethods");
    const gcashPayment = document.getElementById("gcashPayment");
    const gcashReference = document.getElementById("gcashReference");
    const orderTypeError = document.getElementById("orderTypeError");
    const paymentError = document.getElementById("paymentError");
    const customerPhone = document.getElementById("customerPhone");
    const customerName = document.getElementById("customerName");
    const placeOrderBtn = document.getElementById("placeOrderBtn");
    const successOverlay = document.getElementById("successOverlay");
    const closeSuccess = document.getElementById("closeSuccess");
    const orderNumber = document.getElementById("orderNumber");

    checkoutButton?.addEventListener("click", () => {
        if (cart.length === 0) {
            alert("Your cart is empty!");
            return;
        }
        renderCheckoutSummary();
        checkoutTotal.textContent = cartTotal.textContent;
        paymentMethods.innerHTML = "";
        gcashPayment.classList.remove("show");
        orderTypeError.textContent = "";
        paymentError.textContent = "";
        checkoutModal.classList.add("show");
    });

    function renderCheckoutSummary() {
        checkoutOrderSummary.innerHTML = "";
        cart.forEach(item => {
            const itemTotal = item.price * item.quantity;
            const itemElement = document.createElement("div");
            itemElement.className = "checkout-order-item";
            itemElement.innerHTML = `
                <div>
                    <strong>${item.name}</strong>
                    <small>Quantity: ${item.quantity}</small>
                    ${item.flavors.length > 0 ? `<small>Flavors: ${item.flavors.join(", ")}</small>` : ""}
                    ${item.withItems.length > 0 ? `<small>With: ${item.withItems.join(", ")}</small>` : ""}
                </div>
                <strong>₱${itemTotal.toFixed(2)}</strong>
            `;
            checkoutOrderSummary.appendChild(itemElement);
        });
    }

    document.querySelectorAll('input[name="orderType"]').forEach(radio => {
        radio.addEventListener("change", function () {
            orderTypeError.textContent = "";
            // Dine-In and Take-Out: pay with Cash at the counter or with GCash.
            paymentMethods.innerHTML = `
                <label class="payment-option"><input type="radio" name="paymentMethod" value="Cash"><span>💵 Cash</span></label>
                <label class="payment-option"><input type="radio" name="paymentMethod" value="GCash"><span>📱 GCash / Online Payment</span></label>
            `;
            attachPaymentEvents();
        });
    });

    function attachPaymentEvents() {
        document.querySelectorAll('input[name="paymentMethod"]').forEach(radio => {
            radio.addEventListener("change", function () {
                if (this.value === "GCash") gcashPayment.classList.add("show");
                else { gcashPayment.classList.remove("show"); gcashReference.value = ""; }
            });
        });
    }

    closeCheckout?.addEventListener("click", () => checkoutModal.classList.remove("show"));
    backToCart?.addEventListener("click", () => checkoutModal.classList.remove("show"));

    placeOrderBtn?.addEventListener("click", async () => {
    if (customerName.value.trim() === "") { alert("Please enter your full name."); customerName.focus(); return; }
    if (customerPhone.value.trim() === "") { alert("Please enter your phone number."); customerPhone.focus(); return; }
    
    const selectedOrderType = document.querySelector('input[name="orderType"]:checked');
    if (!selectedOrderType) { orderTypeError.textContent = "Please select an order type."; return; }
    const orderType = selectedOrderType.value;

    const selectedPayment = document.querySelector('input[name="paymentMethod"]:checked');
    if (!selectedPayment) { paymentError.textContent = "Please select a payment method."; return; }
    const paymentMethod = selectedPayment.value;

    if (paymentMethod === "GCash" && gcashReference.value.trim() === "") { alert("Please enter your GCash reference number."); gcashReference.focus(); return; }

    // Construct the payload to send to PHP backend
    const orderData = {
        customerName: customerName.value.trim(),
        phone: customerPhone.value.trim(),
        orderType: orderType,
        paymentMethod: paymentMethod,
        gcashReference: paymentMethod === "GCash" ? gcashReference.value.trim() : "N/A",
        items: cart,
        total: cart.reduce((sum, item) => sum + (item.price * item.quantity), 0)
    };

    try {
        placeOrderBtn.disabled = true;
        placeOrderBtn.textContent = "Processing...";

        const response = await fetch('place_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(orderData)
        });

        const result = await response.json();

        if (result.success) {
            // Store details locally just for immediate UI tracking reference
            const orderInfo = {
                ...orderData,
                orderId: Number(result.orderId || 0),
                orderNumber: result.orderNumber,
                date: new Date().toLocaleString(),
                status: "Pending"
            };
            localStorage.setItem("lastOrder", JSON.stringify(orderInfo));

            orderNumber.textContent = result.orderNumber;
            checkoutModal.classList.remove("show");
            cart = [];
            renderCart();
            successOverlay.classList.add("show");
        } else {
            alert("Failed to place order: " + (result.message || "Unknown error"));
        }
    } catch (error) {
        console.error("Order submission error:", error);
        alert("An error occurred while communicating with the server.");
    } finally {
        placeOrderBtn.disabled = false;
        placeOrderBtn.textContent = "Confirm Order";
    }
});
});
