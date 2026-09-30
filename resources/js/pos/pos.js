const configElement = document.getElementById('posConfig');

if (configElement) {
    const config = JSON.parse(configElement.textContent);
    const searchInput = document.getElementById('searchProduct');
    const resultsElement = document.getElementById('productResults');
    const cartBody = document.getElementById('cartBody');
    const paymentInput = document.getElementById('payment');
    const productResults = new Map();
    let cart = (config.initialItems || []).map((item) => ({
        ...item,
        id: Number(item.id),
        price: Number(item.price),
        qty: Number(item.qty),
        stock: Number(item.stock),
    }));
    let searchTimeout;
    let selectedPayment = 'tunai';

    const formatRupiah = (value) => new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value || 0);

    const numericValue = (id) => Math.max(0, Number.parseFloat(document.getElementById(id).value) || 0);

    const totals = () => {
        const subtotal = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
        const discountPercent = Math.min(100, numericValue('discountPercent'));
        const discountAmount = numericValue('discountAmount');
        const totalDiscount = subtotal * discountPercent / 100 + discountAmount;
        const grandTotal = Math.max(0, subtotal - totalDiscount + numericValue('tax') + numericValue('otherFee'));

        return { subtotal, grandTotal };
    };

    const createCell = (text, className = '') => {
        const cell = document.createElement('td');
        cell.className = className;
        cell.textContent = text;
        return cell;
    };

    const updateChange = () => {
        const { grandTotal } = totals();
        const difference = (Number.parseFloat(paymentInput.value) || 0) - grandTotal;
        const isShort = difference < 0;
        const changeBox = document.getElementById('changeBox');
        const changeLabel = document.querySelector('.change-label');

        changeBox.classList.toggle('short-payment', isShort);
        changeLabel.textContent = isShort ? 'UANG KURANG' : 'KEMBALIAN';
        document.getElementById('change').textContent = formatRupiah(Math.abs(difference));
    };

    const updateQuickPayments = (grandTotal) => {
        const container = document.getElementById('quickPayButtons');
        const roundedPayment = Math.ceil(grandTotal / 10000) * 10000;
        const amounts = [...new Set([grandTotal, roundedPayment, 50000, 100000])]
            .filter((amount) => amount >= grandTotal && amount > 0)
            .slice(0, 4);

        container.replaceChildren(...amounts.map((amount) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'rounded border border-gray-300 px-2 py-1 text-xs text-gray-700 hover:bg-gray-100';
            button.textContent = formatRupiah(amount);
            button.addEventListener('click', () => {
                paymentInput.value = amount;
                updateChange();
            });

            return button;
        }));
    };

    const updateTotal = () => {
        const totalQty = cart.reduce((sum, item) => sum + item.qty, 0);
        const { subtotal, grandTotal } = totals();

        document.getElementById('totalQty').textContent = totalQty;
        document.getElementById('itemCount').textContent = `${totalQty} Item`;
        document.getElementById('subtotal').textContent = formatRupiah(subtotal);
        document.getElementById('grandTotal').textContent = formatRupiah(grandTotal);
        updateChange();
        updateQuickPayments(grandTotal);
    };

    const renderCart = () => {
        cartBody.replaceChildren();

        if (cart.length === 0) {
            const emptyRow = document.createElement('tr');
            const emptyCell = createCell('Keranjang masih kosong. Silakan cari atau scan barang.', 'px-3 py-8 text-center text-gray-400');
            emptyCell.colSpan = 6;
            emptyRow.append(emptyCell);
            cartBody.append(emptyRow);
            updateTotal();
            return;
        }

        cart.forEach((item, index) => {
            const row = document.createElement('tr');
            row.append(createCell(String(index + 1), 'px-3 py-3'));

            const productCell = document.createElement('td');
            productCell.className = 'px-3 py-3';
            const productName = document.createElement('strong');
            productName.textContent = item.name;
            const productCode = document.createElement('div');
            productCode.className = 'text-xs text-gray-500';
            productCode.textContent = `${item.code} · Stok ${item.stock} ${item.unit || ''}`;
            productCell.append(productName, productCode);
            row.append(productCell);
            row.append(createCell(formatRupiah(item.price), 'px-3 py-3 text-right'));

            const quantityCell = document.createElement('td');
            quantityCell.className = 'px-3 py-3 text-center';
            const quantityControl = document.createElement('div');
            quantityControl.className = 'flex items-center justify-center gap-1';
            const decreaseButton = document.createElement('button');
            decreaseButton.type = 'button';
            decreaseButton.className = 'h-8 w-8 rounded border border-gray-300';
            decreaseButton.textContent = '-';
            decreaseButton.addEventListener('click', () => changeQty(item.id, -1));
            const quantityInput = document.createElement('input');
            quantityInput.type = 'number';
            quantityInput.min = '1';
            quantityInput.max = item.stock;
            quantityInput.value = item.qty;
            quantityInput.className = 'w-14 rounded border border-gray-300 px-1 py-1 text-center';
            quantityInput.setAttribute('aria-label', `Jumlah ${item.name}`);
            quantityInput.addEventListener('change', () => updateQty(item.id, quantityInput.value));
            const increaseButton = document.createElement('button');
            increaseButton.type = 'button';
            increaseButton.className = 'h-8 w-8 rounded border border-gray-300';
            increaseButton.textContent = '+';
            increaseButton.disabled = item.qty >= item.stock;
            increaseButton.addEventListener('click', () => changeQty(item.id, 1));
            quantityControl.append(decreaseButton, quantityInput, increaseButton);
            quantityCell.append(quantityControl);
            row.append(quantityCell);
            row.append(createCell(formatRupiah(item.price * item.qty), 'px-3 py-3 text-right font-semibold'));

            const removeCell = document.createElement('td');
            removeCell.className = 'px-2 py-3 text-center';
            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'rounded px-2 py-1 text-red-700 hover:bg-red-50';
            removeButton.textContent = 'Hapus';
            removeButton.setAttribute('aria-label', `Hapus ${item.name} dari keranjang`);
            removeButton.addEventListener('click', () => removeItem(item.id));
            removeCell.append(removeButton);
            row.append(removeCell);
            cartBody.append(row);
        });

        updateTotal();
    };

    const renderResults = (products) => {
        productResults.clear();
        resultsElement.replaceChildren();

        if (products.length === 0) {
            const emptyResult = document.createElement('div');
            emptyResult.className = 'px-4 py-3 text-sm text-gray-500';
            emptyResult.textContent = 'Produk tidak ditemukan.';
            resultsElement.append(emptyResult);
        }

        products.forEach((product) => {
            productResults.set(Number(product.id), product);
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'flex w-full items-center justify-between gap-4 border-b border-gray-100 px-4 py-3 text-left hover:bg-blue-50 disabled:cursor-not-allowed disabled:opacity-50';
            button.disabled = Number(product.stock) < 1;

            const details = document.createElement('span');
            const name = document.createElement('strong');
            name.className = 'block text-sm text-gray-800';
            name.textContent = product.name;
            const code = document.createElement('span');
            code.className = 'block text-xs text-gray-500';
            code.textContent = `${product.code} · Stok ${product.stock} ${product.unit || ''}`;
            details.append(name, code);
            const price = document.createElement('span');
            price.className = 'whitespace-nowrap text-sm font-semibold text-gray-800';
            price.textContent = formatRupiah(product.price);
            button.append(details, price);
            button.addEventListener('click', () => addToCart(Number(product.id)));
            resultsElement.append(button);
        });

        resultsElement.classList.remove('hidden');
    };

    const addToCart = (productId) => {
        const product = productResults.get(productId);
        if (!product || Number(product.stock) < 1) {
            return;
        }

        const existing = cart.find((item) => item.id === productId);
        if (existing) {
            if (existing.qty >= Number(product.stock)) {
                window.alert(`Stok ${product.name} tidak mencukupi.`);
                return;
            }
            existing.qty += 1;
        } else {
            cart.push({ ...product, id: productId, price: Number(product.price), qty: 1 });
        }

        searchInput.value = '';
        resultsElement.classList.add('hidden');
        searchInput.focus();
        renderCart();
    };

    const searchProduct = async () => {
        const keyword = searchInput.value.trim();
        if (!keyword) {
            resultsElement.classList.add('hidden');
            return;
        }

        try {
            const response = await fetch(`${config.searchUrl}?q=${encodeURIComponent(keyword)}`, {
                headers: { Accept: 'application/json' },
            });
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.message || 'Pencarian produk gagal.');
            }

            renderResults(result.data || []);
            if ((result.data || []).length === 1 && String(result.data[0].barcode) === keyword) {
                addToCart(Number(result.data[0].id));
            }
        } catch (error) {
            window.alert(error.message || 'Pencarian produk gagal.');
        }
    };

    const changeQty = (productId, amount) => {
        const item = cart.find((cartItem) => cartItem.id === productId);
        if (!item) {
            return;
        }

        item.qty += amount;
        if (item.qty < 1) {
            removeItem(productId);
            return;
        }

        item.qty = Math.min(item.qty, Number(item.stock));
        renderCart();
    };

    const updateQty = (productId, quantity) => {
        const item = cart.find((cartItem) => cartItem.id === productId);
        if (!item) {
            return;
        }

        const parsedQuantity = Number.parseInt(quantity, 10);
        item.qty = Math.min(Number(item.stock), Math.max(1, parsedQuantity || 1));
        renderCart();
    };

    const removeItem = (productId) => {
        cart = cart.filter((item) => item.id !== productId);
        renderCart();
    };

    const submitTransaction = async (url, payload, successRedirect) => {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                },
                body: JSON.stringify(payload),
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                const validationMessage = result.errors ? Object.values(result.errors).flat()[0] : null;
                throw new Error(validationMessage || result.message || 'Transaksi gagal diproses.');
            }

            window.location.href = successRedirect(result);
        } catch (error) {
            window.alert(error.message || 'Transaksi gagal diproses.');
        }
    };

    const paymentPayload = () => ({
        items: cart.map((item) => ({ product_id: item.id, qty: item.qty })),
        customer_id: document.getElementById('customerSelect').value || null,
        discount_percent: numericValue('discountPercent'),
        discount_amount: numericValue('discountAmount'),
        tax: numericValue('tax'),
        other_fee: numericValue('otherFee'),
        paid_amount: Number.parseFloat(paymentInput.value) || 0,
        payment_method: selectedPayment,
    });

    window.searchProduct = searchProduct;
    window.calculateTotal = updateTotal;
    window.calculateChange = updateChange;
    window.changeQty = changeQty;
    window.updateQty = updateQty;
    window.removeItem = removeItem;
    window.selectPayment = (button) => {
        selectedPayment = button.dataset.method;
        document.getElementById('paymentMethod').value = selectedPayment;
        document.querySelectorAll('.payment-method-btn').forEach((methodButton) => {
            methodButton.classList.toggle('active', methodButton === button);
            methodButton.classList.toggle('bg-blue-600', methodButton === button);
            methodButton.classList.toggle('text-white', methodButton === button);
            methodButton.classList.toggle('bg-gray-100', methodButton !== button);
            methodButton.classList.toggle('text-gray-700', methodButton !== button);
        });
        if (selectedPayment !== 'tunai') {
            paymentInput.value = totals().grandTotal;
        }
        updateChange();
    };
    window.processPayment = () => {
        if (cart.length === 0) {
            window.alert('Keranjang masih kosong.');
            return;
        }
        const payload = paymentPayload();
        const url = config.completeUrl || config.storeUrl;
        submitTransaction(url, payload, (result) => config.receiptUrl.replace('__ID__', result.data.transaction_id));
    };
    window.holdTransaction = () => {
        if (cart.length === 0) {
            window.alert('Tidak ada transaksi untuk ditahan.');
            return;
        }
        const payload = paymentPayload();
        if (config.transactionId) {
            payload.transaction_id = config.transactionId;
        }
        submitTransaction(config.holdUrl, payload, () => config.heldUrl);
    };
    window.cancelTransaction = async () => {
        if (!window.confirm('Batalkan transaksi ini?')) {
            return;
        }
        if (config.cancelUrl) {
            try {
                const response = await fetch(config.cancelUrl, {
                    method: 'DELETE',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': config.csrfToken },
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Transaksi gagal dibatalkan.');
                }
                window.location.href = config.posUrl;
            } catch (error) {
                window.alert(error.message || 'Transaksi gagal dibatalkan.');
            }
            return;
        }
        cart = [];
        paymentInput.value = '';
        ['discountPercent', 'discountAmount', 'tax', 'otherFee'].forEach((id) => {
            document.getElementById(id).value = 0;
        });
        renderCart();
    };

    searchInput.addEventListener('input', () => {
        window.clearTimeout(searchTimeout);
        searchTimeout = window.setTimeout(searchProduct, 250);
    });
    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            searchProduct();
        }
    });
    paymentInput.addEventListener('input', updateChange);
    ['discountPercent', 'discountAmount', 'tax', 'otherFee'].forEach((id) => {
        document.getElementById(id).addEventListener('input', updateTotal);
    });
    document.addEventListener('click', (event) => {
        if (!resultsElement.contains(event.target) && event.target !== searchInput) {
            resultsElement.classList.add('hidden');
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'F2') {
            event.preventDefault();
            searchInput.focus();
        } else if (event.key === 'F4') {
            event.preventDefault();
            paymentInput.focus();
        } else if (event.key === 'Escape') {
            window.cancelTransaction();
        }
    });

    document.getElementById('currentDate').textContent = new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date());

    renderCart();
}