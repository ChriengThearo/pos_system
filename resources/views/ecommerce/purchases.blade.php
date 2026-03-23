@extends('layouts.ecommerce')

@section('title', 'Purchases')

@section('content')
    @php
        $purchaseNo = $purchaseData['purchase_no'] ?? null;
        $purchaseDate = $purchaseData['purchase_date'] ?? null;
        $status = $purchaseData['status'] ?? null;
        $memo = $purchaseData['memo'] ?? null;
        $supplierName = $supplier['SUPPLIER_NAME'] ?? $supplier['NAME'] ?? $supplier['SUP_NAME'] ?? null;
        $supplierPhone = $supplier['PHONE'] ?? $supplier['TEL'] ?? $supplier['PHONE_NO'] ?? null;
        $supplierCity = $supplier['CITY'] ?? $supplier['COUNTRY_CITY'] ?? $supplier['PROVINCE'] ?? $supplier['TOWN'] ?? $supplier['DISTRICT'] ?? null;
        $supplierAddress = $supplier['ADDRESS'] ?? $supplier['ADDR'] ?? null;
        $supplierEmail = $supplier['EMAIL'] ?? $supplier['EMAIL_ADDRESS'] ?? $supplier['EMAILADDR'] ?? null;
        $employeeName = $employee['EMPLOYEE_NAME'] ?? $employee['employee_name'] ?? null;
        $isNew = $isNew ?? false;
        $isCompleted = ! $isNew && mb_strtolower((string) ($status ?? '')) === 'completed';
        $prefillItem = isset($prefillItem) ? $prefillItem : null;
        $staffUser = \App\Support\StaffAuth::user();
    @endphp

    <section class="card" style="padding: 0; overflow: hidden;">
        <div style="display: flex; align-items: center; gap: 16px; padding: 18px; background: #5d7fa8; color: #fff;">
            <div style="width: 64px; height: 64px; border-radius: 16px; background: #ffffff; color: #5d7fa8; display: flex; align-items: center; justify-content: center; font-weight: 800; font-family: 'Space Grotesk', sans-serif;">
                PUR
            </div>
            <div>
                <h1 class="headline" style="color: #fff;">Create Purchases</h1>
                <div style="opacity: .9; font-size: .95rem;">Main purchase form with purchase detail subform.</div>
            </div>
        </div>
        <div style="padding: 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
            <form method="GET" action="{{ route('purchases.index') }}" class="actions">
                <label for="purchase_no" style="margin: 0; font-size: .85rem;">Search by Purchase No:</label>
                <select id="purchase_no" name="purchase_no" onchange="this.form.submit()">
                    <option value="">Select purchase</option>
                    @foreach($purchaseNos as $purchaseRow)
                        <option value="{{ $purchaseRow->purchase_no }}" @selected((string) $selectedPurchaseNo === (string) $purchaseRow->purchase_no)>
                            #{{ $purchaseRow->purchase_no }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-muted">Load</button>
            </form>
            @if($purchaseNo)
                <span class="chip">Purchase #{{ $purchaseNo }}</span>
            @endif
        </div>
        @if(\App\Support\StaffAuth::can('purchases.manage'))
            <div class="actions" style="justify-content: flex-end; padding: 0 16px 16px;">
                <a href="{{ route('purchases.index', ['new' => 1]) }}" class="btn btn-primary">New Purchase</a>
            </div>
        @endif
    </section>

    @if(! $purchaseNo && ! $isNew)
        <section class="card">
            <div class="subtle">No purchase selected. Choose a purchase number to view the form.</div>
        </section>
    @else
        <section class="card">
            <div class="actions" style="justify-content: space-between;">
                <h2 style="margin-top: 0;">Purchase (Main Form)</h2>
                <span class="chip">{{ $isNew ? 'In Process' : ($status ?: 'N/A') }}</span>
            </div>

            <div class="grid grid-2" style="margin-top: 12px;">
                <div style="display: grid; gap: 10px;">
                    <div>
                        <label for="supplier_name">Supplier</label>
                        @if($isNew)
                            <select id="new-supplier-id" name="supplier_id" form="new-purchase-form" required>
                                <option value="">Select supplier</option>
                                @foreach($suppliers as $supplierRow)
                                    <option value="{{ $supplierRow->supplier_id }}"
                                            data-address="{{ $supplierRow->address }}"
                                            data-city="{{ $supplierRow->city }}"
                                            data-phone="{{ $supplierRow->phone }}"
                                            data-email="{{ $supplierRow->email }}">
                                        {{ $supplierRow->supplier_name }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input id="supplier_name" type="text" value="{{ $supplierName ?: 'N/A' }}" readonly>
                        @endif
                    </div>
                    <div>
                        <label for="supplier_address">Address</label>
                        <input id="supplier_address" type="text" value="{{ $isNew ? '' : ($supplierAddress ?: 'N/A') }}" readonly>
                    </div>
                    <div>
                        <label for="supplier_city">City</label>
                        <input id="supplier_city" type="text" value="{{ $isNew ? '' : ($supplierCity ?: 'N/A') }}" readonly>
                    </div>
                    <div>
                        <label for="supplier_phone">Phone</label>
                        <input id="supplier_phone" type="text" value="{{ $isNew ? '' : ($supplierPhone ?: 'N/A') }}" readonly>
                    </div>
                    <div>
                        <label for="supplier_email">Email</label>
                        <input id="supplier_email" type="text" value="{{ $isNew ? '' : ($supplierEmail ?: 'N/A') }}" readonly>
                    </div>
                </div>

                <div style="display: grid; gap: 10px;">
                    <div>
                        <label for="purchase_no_field">Purchase No</label>
                        <input id="purchase_no_field" type="text" value="{{ $isNew ? 'Auto' : $purchaseNo }}" readonly>
                    </div>
                    <div>
                        <label for="purchase_date">Purchase Date</label>
                        <input id="purchase_date" type="text" value="{{ $isNew ? now()->format('Y-m-d H:i') : ($purchaseDate ? \Illuminate\Support\Carbon::parse($purchaseDate)->format('Y-m-d H:i') : 'N/A') }}" readonly>
                    </div>
                    <div>
                        <label for="buyer">Buyer</label>
                        <input id="buyer" type="text" value="{{ $isNew ? ($staffUser['employee_name'] ?? 'N/A') : ($employeeName ?: 'N/A') }}" readonly>
                    </div>
                    <div>
                        <label for="purchase_status">Purchase Status</label>
                        <input id="purchase_status" type="text" value="{{ $isNew ? 'In Process' : ($status ?: 'N/A') }}" readonly>
                    </div>
                    <div>
                        <label for="purchase_memo">Purchase Memo</label>
                        @if($isNew)
                            <input id="purchase_memo" type="text" name="purchase_memo" form="new-purchase-form" value="">
                        @else
                            <input id="purchase_memo" type="text" value="{{ $memo ?: 'N/A' }}" readonly>
                        @endif
                    </div>
                </div>
            </div>

            @if($isNew)
                <form id="new-purchase-form" method="POST" action="{{ route('purchases.store') }}" class="actions" style="margin-top: 12px;">
                    @csrf
                    @if($prefillItem)
                        <input type="hidden" id="prefill-product-no" name="prefill_product_no" value="{{ $prefillItem->product_no }}">
                    @endif
                </form>
            @endif
        </section>

        <section class="card">
            <div class="actions" style="justify-content: space-between;">
                <h2 style="margin-top: 0;">Purchase Details (Sub Form)</h2>
                <span class="chip" @if($isNew) id="purchase-new-item-count" @endif>{{ $isNew ? ($prefillItem ? 1 : 0) : number_format((int) $itemCount) }} items</span>
            </div>

            @if($isCompleted)
                <div class="flash error" style="margin-top: 12px;">Can't Update. Purchase have Completed</div>
            @endif

            @if($isNew)
                <select id="purchase-item-product-template" style="display: none;">
                    <option value="">Select product</option>
                    @foreach($products as $product)
                        <option value="{{ $product->product_no }}">{{ $product->product_name }}</option>
                    @endforeach
                </select>
                <div class="table-wrap" style="margin-top: 12px;">
                    <table>
                        <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Product Name</th>
                            <th style="text-align: right;">Qty</th>
                            <th style="text-align: right;">Unit Cost</th>
                            <th style="text-align: right;">Amount</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if($prefillItem)
                            @php
                                $prefillAmount = (float) $prefillItem->qty * (float) ($prefillItem->unit_cost ?? 0);
                            @endphp
                            <tr id="prefill-item-row" data-unit-cost="{{ (float) ($prefillItem->unit_cost ?? 0) }}">
                                <td class="subtle" style="text-align: center;">Auto</td>
                                <td>{{ $prefillItem->product_name ?: $prefillItem->product_no }}</td>
                                <td style="text-align: right;">
                                    <input
                                        id="prefill-qty-input"
                                        type="number"
                                        name="prefill_qty"
                                        form="new-purchase-form"
                                        min="1"
                                        max="9999"
                                        value="{{ (int) $prefillItem->qty }}"
                                        required
                                        style="text-align: right; width: 110px;"
                                    >
                                </td>
                                <td style="text-align: right;">${{ number_format((float) ($prefillItem->unit_cost ?? 0), 2) }}</td>
                                <td style="text-align: right;" id="prefill-amount">${{ number_format($prefillAmount, 2) }}</td>
                                <td style="text-align: center;">
                                    <button type="button" class="btn btn-danger" id="prefill-remove-btn">Remove</button>
                                </td>
                            </tr>
                        @endif
                            <tr id="purchase-items-empty" @if($prefillItem) style="display: none;" @endif>
                                <td colspan="6" class="subtle">No items added yet.</td>
                            </tr>
                        </tbody>
                        <tbody id="purchase-items-new"></tbody>
                    </table>
                </div>
                <div class="actions" style="justify-content: flex-end; margin-top: 12px;">
                    <button type="button" class="btn btn-muted" id="add-purchase-item" disabled>Add Item</button>
                    <button type="submit" class="btn btn-primary" id="save-purchase-items" disabled form="new-purchase-form">Save Items</button>
                </div>
            @else
                <form method="POST" action="{{ route('purchases.items.store', ['purchaseNo' => $purchaseNo]) }}" id="purchase-items-form">
                    @csrf
                    <select id="purchase-item-product-template" style="display: none;">
                        <option value="">Select product</option>
                        @foreach($products as $product)
                            <option value="{{ $product->product_no }}">{{ $product->product_name }}</option>
                        @endforeach
                    </select>
                    <div class="table-wrap" style="margin-top: 12px;">
                        @if($detailMode === 'structured')
                            <table>
                                <thead>
                                <tr>
                                    <th>Photo</th>
                                    <th>Product Name</th>
                                    <th style="text-align: right;">Qty</th>
                                    <th style="text-align: right;">Unit Cost</th>
                                    <th style="text-align: right;">Amount</th>
                                    <th>Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($details as $item)
                                    <tr>
                                        <td class="subtle" style="text-align: center;">--</td>
                                        <td>{{ $item->product_name ?? 'N/A' }}</td>
                                        <td style="text-align: right;">{{ number_format((float) $item->qty) }}</td>
                                        <td style="text-align: right;">${{ number_format((float) $item->unit_cost, 2) }}</td>
                                        <td style="text-align: right;">${{ number_format((float) $item->amount, 2) }}</td>
                                        <td class="subtle" style="text-align: center;">--</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="subtle" id="purchase-items-empty">No purchase details found.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                                <tbody id="purchase-items-new"></tbody>
                            </table>
                        @else
                            <table>
                                <thead>
                                <tr>
                                    @foreach($detailHeaders as $column)
                                        <th>{{ $column }}</th>
                                    @endforeach
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($details as $row)
                                    @php $rowData = (array) $row; @endphp
                                    <tr>
                                        @foreach($detailHeaders as $column)
                                            @php
                                                $value = $rowData[$column]
                                                    ?? $rowData[strtolower($column)]
                                                    ?? null;
                                            @endphp
                                            <td>{{ $value ?? 'N/A' }}</td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ max(count($detailHeaders), 1) }}" class="subtle">No purchase details found.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        @endif
                    </div>

                    @if($detailMode === 'structured')
                        <div class="actions" style="justify-content: flex-end; margin-top: 12px;">
                            @if($isCompleted)
                                <button type="button" class="btn btn-muted" disabled>Add Item</button>
                                <button type="button" class="btn btn-primary" disabled>Save Items</button>
                            @else
                                <button type="button" class="btn btn-muted" id="add-purchase-item">Add Item</button>
                                <button type="submit" class="btn btn-primary" id="save-purchase-items" disabled>Save Items</button>
                            @endif
                        </div>
                    @endif
                </form>
            @endif
        </section>

        <section class="card">
            <div class="grid grid-3">
                <article class="stat">
                    <div class="label">Items</div>
                    <div class="value">{{ $isNew ? 0 : number_format((int) $itemCount) }}</div>
                </article>
                <article class="stat">
                    <div class="label">Sub Total</div>
                    <div class="value">${{ number_format((float) $subtotal, 2) }}</div>
                </article>
                <article class="stat">
                    <div class="label">Status</div>
                    <div class="value">{{ $isNew ? 'In Process' : ($status ?: 'N/A') }}</div>
                </article>
            </div>
        </section>

        <script>
            (() => {
                const supplierSelect = document.getElementById('new-supplier-id');
                const addressField = document.getElementById('supplier_address');
                const cityField = document.getElementById('supplier_city');
                const phoneField = document.getElementById('supplier_phone');
                const emailField = document.getElementById('supplier_email');

                if (!supplierSelect) {
                    return;
                }

                const updateSupplierInfo = () => {
                    const selected = supplierSelect.options[supplierSelect.selectedIndex];
                    if (addressField) addressField.value = selected?.dataset?.address || '';
                    if (cityField) cityField.value = selected?.dataset?.city || '';
                    if (phoneField) phoneField.value = selected?.dataset?.phone || '';
                    if (emailField) emailField.value = selected?.dataset?.email || '';
                };

                supplierSelect.addEventListener('change', updateSupplierInfo);
                updateSupplierInfo();
            })();

            (() => {
                const prefillRow = document.getElementById('prefill-item-row');
                if (!prefillRow) {
                    return;
                }

                const qtyInput = document.getElementById('prefill-qty-input');
                const amountCell = document.getElementById('prefill-amount');
                const removeBtn = document.getElementById('prefill-remove-btn');
                const productNoInput = document.getElementById('prefill-product-no');
                const emptyRow = document.getElementById('purchase-items-empty');
                const chip = document.getElementById('purchase-new-item-count');
                const unitCost = Number(prefillRow.dataset.unitCost || '0');

                const formatCurrency = (value) => `$${value.toFixed(2)}`;

                const updateAmount = () => {
                    if (!qtyInput || !amountCell) {
                        return;
                    }
                    const rawQty = Number(qtyInput.value || '1');
                    const safeQty = Math.max(1, Math.min(9999, Number.isFinite(rawQty) ? rawQty : 1));
                    qtyInput.value = String(safeQty);
                    amountCell.textContent = formatCurrency(unitCost * safeQty);
                };

                qtyInput?.addEventListener('input', updateAmount);
                qtyInput?.addEventListener('change', updateAmount);

                removeBtn?.addEventListener('click', () => {
                    if (productNoInput) {
                        productNoInput.value = '';
                    }
                    if (qtyInput) {
                        qtyInput.required = false;
                        qtyInput.disabled = true;
                    }
                    prefillRow.remove();
                    if (emptyRow) {
                        emptyRow.style.display = '';
                    }
                    document.dispatchEvent(new Event('purchase-prefill-updated'));
                });

                updateAmount();
            })();

            (() => {
                const purchaseCompleted = @json($isCompleted);
                if (purchaseCompleted) {
                    return;
                }

                const isNewPurchase = @json($isNew);
                const supplierSelect = document.getElementById('new-supplier-id');
                const activeFormId = isNewPurchase ? 'new-purchase-form' : 'purchase-items-form';
                const addButton = document.getElementById('add-purchase-item');
                const rowsContainer = document.getElementById('purchase-items-new');
                const saveButton = document.getElementById('save-purchase-items');
                const emptyRow = document.getElementById('purchase-items-empty');
                const productTemplate = document.getElementById('purchase-item-product-template');
                const chip = document.getElementById('purchase-new-item-count');
                const costEndpoint = @json(route('products.cost'));

                if (!addButton || !rowsContainer || !productTemplate) {
                    return;
                }

                let rowIndex = 0;

                const updateState = () => {
                    const hasRows = rowsContainer.querySelectorAll('tr').length > 0;
                    const hasPrefill = Boolean(document.getElementById('prefill-item-row'));
                    const totalItems = (hasRows ? rowsContainer.querySelectorAll('tr').length : 0) + (hasPrefill ? 1 : 0);
                    const hasSupplier = !isNewPurchase || (supplierSelect && supplierSelect.value !== '');
                    addButton.disabled = !hasSupplier;
                    if (saveButton) {
                        saveButton.disabled = !(hasSupplier && totalItems > 0);
                    }
                    if (emptyRow) {
                        emptyRow.style.display = totalItems > 0 ? 'none' : '';
                    }
                    if (chip && isNewPurchase) {
                        chip.textContent = `${totalItems} items`;
                    }
                };

                const formatCurrency = (value) => {
                    if (Number.isNaN(value)) {
                        return 'Auto';
                    }
                    return `$${value.toFixed(2)}`;
                };

                const updateRowTotals = (row, costValue) => {
                    const qtyInput = row.querySelector('input[name$="[qty]"]');
                    const amountCell = row.querySelector('.js-amount');
                    if (!qtyInput || !amountCell) {
                        return;
                    }
                    const qty = Math.max(1, Number(qtyInput.value || 1));
                    if (Number.isNaN(costValue)) {
                        amountCell.textContent = 'Auto';
                        return;
                    }
                    amountCell.textContent = formatCurrency(costValue * qty);
                };

                const resetRowCost = (row) => {
                    const costInput = row.querySelector('input[name$="[unit_cost]"]');
                    const amountCell = row.querySelector('.js-amount');
                    if (costInput) {
                        costInput.value = '';
                    }
                    if (amountCell) {
                        amountCell.textContent = 'Auto';
                    }
                };

                const fetchCost = async (row) => {
                    const select = row.querySelector('select');
                    const qtyInput = row.querySelector('input[name$="[qty]"]');
                    const costInput = row.querySelector('input[name$="[unit_cost]"]');
                    if (!select || !qtyInput || !costInput) {
                        return;
                    }

                    const productNo = select.value;
                    if (!productNo) {
                        resetRowCost(row);
                        return;
                    }

                    const url = new URL(costEndpoint, window.location.origin);
                    url.searchParams.set('product_no', productNo);
                    url.searchParams.set('qty', qtyInput.value || '1');

                    try {
                        const response = await fetch(url.toString(), {
                            headers: { 'Accept': 'application/json' },
                        });
                        if (!response.ok) {
                            resetRowCost(row);
                            return;
                        }
                        const data = await response.json();
                        const cost = Number(data.unit_cost || 0);
                        if (!Number.isNaN(cost)) {
                            costInput.value = cost.toFixed(2);
                        }
                        updateRowTotals(row, cost);
                    } catch (error) {
                        resetRowCost(row);
                    }
                };

                const buildRow = () => {
                    const row = document.createElement('tr');

                    const photoCell = document.createElement('td');
                    photoCell.className = 'subtle';
                    photoCell.style.textAlign = 'center';
                    photoCell.textContent = 'Auto';

                    const productCell = document.createElement('td');
                    const productSelect = productTemplate.cloneNode(true);
                    productSelect.id = '';
                    productSelect.style.display = '';
                    productSelect.name = `items[${rowIndex}][product_no]`;
                    if (activeFormId) {
                        productSelect.setAttribute('form', activeFormId);
                    }
                    productSelect.required = true;
                    productCell.appendChild(productSelect);

                    const qtyCell = document.createElement('td');
                    qtyCell.style.textAlign = 'right';
                    const qtyInput = document.createElement('input');
                    qtyInput.type = 'number';
                    qtyInput.name = `items[${rowIndex}][qty]`;
                    qtyInput.min = '1';
                    qtyInput.max = '9999';
                    qtyInput.value = '1';
                    if (activeFormId) {
                        qtyInput.setAttribute('form', activeFormId);
                    }
                    qtyInput.required = true;
                    qtyInput.style.textAlign = 'right';
                    qtyCell.appendChild(qtyInput);

                    const costCell = document.createElement('td');
                    costCell.style.textAlign = 'right';
                    const costInput = document.createElement('input');
                    costInput.type = 'number';
                    costInput.name = `items[${rowIndex}][unit_cost]`;
                    costInput.min = '0';
                    costInput.step = '0.01';
                    costInput.placeholder = 'Auto';
                    if (activeFormId) {
                        costInput.setAttribute('form', activeFormId);
                    }
                    costInput.style.textAlign = 'right';
                    costCell.appendChild(costInput);

                    const amountCell = document.createElement('td');
                    amountCell.style.textAlign = 'right';
                    amountCell.className = 'subtle js-amount';
                    amountCell.textContent = 'Auto';

                    const actionCell = document.createElement('td');
                    const removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.className = 'btn btn-danger js-remove-row';
                    removeButton.textContent = 'Remove';
                    actionCell.appendChild(removeButton);

                    row.append(photoCell, productCell, qtyCell, costCell, amountCell, actionCell);
                    rowIndex += 1;
                    return row;
                };

                addButton.addEventListener('click', () => {
                    rowsContainer.appendChild(buildRow());
                    updateState();
                });

                rowsContainer.addEventListener('change', (event) => {
                    const target = event.target;
                    if (!(target instanceof HTMLElement)) {
                        return;
                    }
                    if (target.tagName === 'SELECT') {
                        const row = target.closest('tr');
                        if (row) {
                            fetchCost(row);
                        }
                    }
                });

                rowsContainer.addEventListener('input', (event) => {
                    const target = event.target;
                    if (!(target instanceof HTMLElement)) {
                        return;
                    }
                    if (target.tagName === 'INPUT') {
                        const row = target.closest('tr');
                        if (!row) {
                            return;
                        }
                        const costInput = row.querySelector('input[name$="[unit_cost]"]');
                        if (costInput && costInput.value !== '') {
                            updateRowTotals(row, Number(costInput.value || 0));
                        } else {
                            fetchCost(row);
                        }
                    }
                });

                rowsContainer.addEventListener('click', (event) => {
                    const target = event.target;
                    if (target instanceof HTMLElement && target.classList.contains('js-remove-row')) {
                        const row = target.closest('tr');
                        if (row) {
                            row.remove();
                            updateState();
                        }
                    }
                });

                if (supplierSelect && isNewPurchase) {
                    supplierSelect.addEventListener('change', updateState);
                }
                document.addEventListener('purchase-prefill-updated', updateState);
                updateState();
            })();
        </script>
    @endif
@endsection
