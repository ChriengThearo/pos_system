@extends('layouts.ecommerce')

@section('title', 'China Store')

@section('content')
    <section class="card china-sticky-panel">
        <h1 class="headline">China Store</h1>

        <div class="search-grid" style="margin-top: 14px;">
            <div>
                <label for="china-search">Search products</label>
                <input
                    id="china-search"
                    type="text"
                    placeholder="e.g. headphones, lamp, cable"
                    autocomplete="off"
                >
            </div>
            <button type="button" class="btn btn-primary" id="china-load-btn">Load Products</button>
        </div>
    </section>

    <section class="card">
        <div id="china-message" class="flash" style="display: none;"></div>

        <div id="china-loading" style="display: none; margin: 10px 0 14px;">
            <div style="display: inline-flex; align-items: center; gap: 10px;">
                <span
                    aria-hidden="true"
                    style="
                        width: 16px;
                        height: 16px;
                        border: 2px solid #d6dfeb;
                        border-top-color: #e36414;
                        border-radius: 50%;
                        display: inline-block;
                        animation: china-spin .7s linear infinite;
                    "
                ></span>
                <span class="subtle" id="china-loading-text">Loading products...</span>
            </div>
        </div>

        <div id="china-products-grid" class="grid grid-4"></div>
        <div id="china-auto-load-status" class="subtle" style="display: none; margin-top: 14px; text-align: center;"></div>
        <div id="china-scroll-sentinel" style="height: 1px;"></div>
    </section>

    <style>
        @keyframes china-spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .china-sticky-panel {
            position: sticky;
            top: 8px;
            z-index: 30;
        }

        .china-product-card {
            border: 1px solid var(--border);
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            padding: 12px;
            display: grid;
            gap: 10px;
        }

        .china-product-image {
            width: 100%;
            aspect-ratio: 1 / 1;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #e5ebf3;
            background: #f3f7fb;
        }

        .china-product-name {
            margin: 0;
            font-size: .95rem;
            line-height: 1.35;
            min-height: 2.6em;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const PAGE_SIZE = 10;
            const PRELOAD_OFFSET_PX = 1200;
            const loadBtn = document.getElementById('china-load-btn');
            const searchInput = document.getElementById('china-search');
            const loading = document.getElementById('china-loading');
            const loadingText = document.getElementById('china-loading-text');
            const autoLoadStatus = document.getElementById('china-auto-load-status');
            const scrollSentinel = document.getElementById('china-scroll-sentinel');
            const messageBox = document.getElementById('china-message');
            const productsGrid = document.getElementById('china-products-grid');
            const csrfToken = '{{ csrf_token() }}';
            const placeholderImageUrl = '{{ route('china-store.image') }}';

            const pageCache = new Map();
            const inFlightPageRequests = new Map();
            let displayedProducts = [];
            let displayedCount = 0;
            let currentPage = 0;
            let nextPage = 1;
            let currentQuery = '';
            let totalAvailable = 0;
            let hasMore = false;
            let isLoadingInitial = false;
            let isLoadingMore = false;
            let scrollTicking = false;
            let activeRequestToken = 0;

            const formatMoney = (value) => {
                const amount = Number(value || 0);
                return '$' + amount.toFixed(2);
            };

            const setLoading = (state, text = 'Loading...', mode = 'initial') => {
                if (mode === 'initial') {
                    isLoadingInitial = state;
                    loading.style.display = state ? '' : 'none';
                    loadBtn.disabled = state;
                    loadBtn.textContent = state ? 'Loading...' : 'Load Products';
                    loadingText.textContent = text;
                }

                if (mode === 'more') {
                    isLoadingMore = state;
                    autoLoadStatus.style.display = state ? '' : 'none';
                    autoLoadStatus.textContent = state ? text : '';
                }
            };

            const showMessage = (text, type = 'success') => {
                messageBox.style.display = '';
                messageBox.textContent = text;
                messageBox.classList.remove('success', 'error');

                if (type === 'success') {
                    messageBox.classList.add('success');
                    return;
                }

                if (type === 'error') {
                    messageBox.classList.add('error');
                    return;
                }

                messageBox.style.color = '#7a5514';
                messageBox.style.background = '#fff3cd';
                messageBox.style.borderColor = '#f3d182';
            };

            const resetMessageStyle = () => {
                messageBox.style.color = '';
                messageBox.style.background = '';
                messageBox.style.borderColor = '';
            };

            const clearMessage = () => {
                messageBox.style.display = 'none';
                messageBox.textContent = '';
                messageBox.classList.remove('success', 'error');
                resetMessageStyle();
            };

            const refreshAutoLoadState = () => {
                if (isLoadingMore) {
                    autoLoadStatus.style.display = '';
                    return;
                }

                if (hasMore && displayedCount > 0) {
                    autoLoadStatus.style.display = '';
                    autoLoadStatus.textContent = 'Loading...';
                    return;
                }

                autoLoadStatus.style.display = 'none';
                autoLoadStatus.textContent = '';
            };

            const setEmptyGrid = (text = 'No products found.') => {
                productsGrid.innerHTML = '';
                const empty = document.createElement('p');
                empty.className = 'subtle';
                empty.textContent = text;
                productsGrid.appendChild(empty);
            };

            const resetGrid = () => {
                productsGrid.innerHTML = '';
                displayedProducts = [];
                displayedCount = 0;
            };

            const createProductCard = (product) => {
                const card = document.createElement('article');
                card.className = 'china-product-card';

                const image = document.createElement('img');
                image.className = 'china-product-image';
                image.src = String(product.image || placeholderImageUrl);
                image.alt = String(product.name || 'CJ Product');
                image.loading = 'lazy';
                image.onerror = () => {
                    if (image.src !== placeholderImageUrl) {
                        image.src = placeholderImageUrl;
                    }
                };

                const title = document.createElement('h3');
                title.className = 'china-product-name';
                title.textContent = String(product.name || 'Unnamed product');

                const price = document.createElement('p');
                price.className = 'subtle';
                price.style.margin = '0';
                price.textContent = 'Cost Price: ' + formatMoney(product.cost_price);

                const importBtn = document.createElement('button');
                importBtn.type = 'button';
                importBtn.className = 'btn btn-primary';
                importBtn.textContent = 'Import to Stock';
                if (!product.image_token) {
                    importBtn.disabled = true;
                    importBtn.textContent = 'Image unavailable';
                }
                importBtn.addEventListener('click', () => importProduct(product, importBtn));

                card.appendChild(image);
                card.appendChild(title);
                card.appendChild(price);
                card.appendChild(importBtn);

                return card;
            };

            const appendProducts = (products) => {
                if (!Array.isArray(products) || products.length === 0) {
                    return 0;
                }

                if (displayedCount === 0) {
                    productsGrid.innerHTML = '';
                }

                const fragment = document.createDocumentFragment();
                products.forEach((product) => {
                    displayedProducts.push(product);
                    fragment.appendChild(createProductCard(product));
                });

                productsGrid.appendChild(fragment);
                displayedCount += products.length;

                return products.length;
            };

            const parseMeta = (payload, page) => {
                const meta = payload.meta && typeof payload.meta === 'object' ? payload.meta : {};
                const total = Number(meta.total_available || 0);
                const fetched = Number(meta.fetched || 0);
                const current = Number(meta.current_page || page);
                const nPage = Number(meta.next_page || (current + 1));
                const canLoadMore = Boolean(meta.has_more);

                return { total, fetched, current, nextPage: nPage, canLoadMore };
            };

            const shouldAutoLoadByScroll = () => {
                const scrollTop = window.scrollY || window.pageYOffset || 0;
                const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
                const fullHeight = Math.max(
                    document.body.scrollHeight || 0,
                    document.documentElement.scrollHeight || 0
                );

                return (scrollTop + viewportHeight + PRELOAD_OFFSET_PX) >= fullHeight;
            };

            const cacheKey = (query, page) => `${query}::${page}::${PAGE_SIZE}`;

            const requestProductsPage = async (query, page) => {
                const key = cacheKey(query, page);

                if (pageCache.has(key)) {
                    return pageCache.get(key);
                }

                if (inFlightPageRequests.has(key)) {
                    return inFlightPageRequests.get(key);
                }

                const params = new URLSearchParams();
                params.set('page', String(page));
                params.set('per_page', String(PAGE_SIZE));
                if (query !== '') {
                    params.set('q', query);
                }

                const endpoint = '{{ route('china-store.products') }}';
                const url = params.toString() !== '' ? endpoint + '?' + params.toString() : endpoint;

                const promise = fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).then(async (response) => {
                    if (!response.ok) {
                        throw new Error('Failed to fetch products.');
                    }

                    const payload = await response.json();
                    pageCache.set(key, payload);
                    return payload;
                }).finally(() => {
                    inFlightPageRequests.delete(key);
                });

                inFlightPageRequests.set(key, promise);
                return promise;
            };

            const prefetchNextPage = () => {
                if (!hasMore || nextPage < 1) {
                    return;
                }

                const key = cacheKey(currentQuery, nextPage);
                if (pageCache.has(key) || inFlightPageRequests.has(key)) {
                    return;
                }

                requestProductsPage(currentQuery, nextPage).catch(() => {
                    // ignore prefetch errors, normal request path will handle errors
                });
            };

            const applyLoadedPage = (payload, page, resetList) => {
                const products = Array.isArray(payload.products) ? payload.products : [];

                if (resetList) {
                    resetGrid();
                }

                const appended = appendProducts(products);
                const meta = parseMeta(payload, page);
                currentPage = meta.current;
                nextPage = meta.nextPage;
                totalAvailable = meta.total;
                hasMore = meta.canLoadMore && appended > 0;

                if (displayedCount === 0) {
                    setEmptyGrid('No products found.');
                    showMessage('No products found.', 'warning');
                    return;
                }

                if (resetList) {
                    if (payload.source === 'cj' && totalAvailable > 0) {
                        showMessage(
                            'Showing ' + displayedCount + ' of ' + totalAvailable + ' product(s). More will load automatically.',
                            'success'
                        );
                    } else {
                        showMessage('Loaded ' + displayedCount + ' product(s). More will load automatically.', 'success');
                    }
                } else if (!hasMore) {
                    showMessage('Loaded all available products (' + displayedCount + ').', 'success');
                }
            };

            const fetchProductsPage = async (page, resetList) => {
                if ((resetList && isLoadingInitial) || (!resetList && (isLoadingInitial || isLoadingMore || !hasMore))) {
                    return;
                }

                const requestToken = activeRequestToken;
                const mode = resetList ? 'initial' : 'more';
                setLoading(true, 'Loading...', mode);

                try {
                    const payload = await requestProductsPage(currentQuery, page);
                    if (requestToken !== activeRequestToken) {
                        return;
                    }

                    applyLoadedPage(payload, page, resetList);
                    refreshAutoLoadState();
                    prefetchNextPage();
                } catch (error) {
                    if (requestToken !== activeRequestToken) {
                        return;
                    }

                    if (resetList) {
                        resetGrid();
                        setEmptyGrid('No products found.');
                    }

                    hasMore = false;
                    showMessage('Unable to load products right now.', 'error');
                    refreshAutoLoadState();
                } finally {
                    if (requestToken !== activeRequestToken) {
                        return;
                    }

                    setLoading(false, 'Loading...', mode);
                    refreshAutoLoadState();

                    if (hasMore && shouldAutoLoadByScroll()) {
                        window.setTimeout(() => {
                            tryAutoLoadNext();
                        }, 40);
                    }
                }
            };

            const tryAutoLoadNext = () => {
                if (!hasMore || isLoadingInitial || isLoadingMore) {
                    return;
                }

                fetchProductsPage(nextPage, false);
            };

            const setupInfiniteScroll = () => {
                if (!scrollSentinel || !('IntersectionObserver' in window)) {
                    return;
                }

                const observer = new IntersectionObserver((entries) => {
                    const [entry] = entries;
                    if (!entry || !entry.isIntersecting) {
                        return;
                    }

                    tryAutoLoadNext();
                }, {
                    root: null,
                    rootMargin: '0px 0px 1200px 0px',
                    threshold: 0,
                });

                observer.observe(scrollSentinel);
            };

            const setupScrollPrefetch = () => {
                window.addEventListener('scroll', () => {
                    if (scrollTicking) {
                        return;
                    }

                    scrollTicking = true;
                    window.requestAnimationFrame(() => {
                        scrollTicking = false;

                        if (shouldAutoLoadByScroll()) {
                            tryAutoLoadNext();
                        }
                    });
                }, { passive: true });
            };

            const startNewSearch = () => {
                clearMessage();
                currentQuery = searchInput.value.trim();
                activeRequestToken += 1;
                currentPage = 0;
                nextPage = 1;
                totalAvailable = 0;
                hasMore = false;
                pageCache.clear();
                refreshAutoLoadState();
                fetchProductsPage(1, true);
            };

            const importProduct = async (product, button) => {
                clearMessage();
                button.disabled = true;
                const defaultText = button.textContent;
                button.textContent = 'Importing...';

                try {
                    const response = await fetch('{{ route('china-store.import') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            name: product.name,
                            image: product.image_token,
                            cost_price: product.cost_price
                        })
                    });

                    const payload = await response.json();

                    if (!response.ok || payload.status === 'error') {
                        throw new Error(payload.message || 'Import failed.');
                    }

                    if (payload.status === 'skipped') {
                        showMessage(payload.message || 'Product already exists.', 'warning');
                        button.textContent = 'Exists';
                        return;
                    }

                    showMessage(payload.message || 'Product imported successfully.', 'success');
                    button.textContent = 'Imported';
                } catch (error) {
                    showMessage(error.message || 'Import failed.', 'error');
                    button.disabled = false;
                    button.textContent = defaultText;
                }
            };

            resetGrid();
            refreshAutoLoadState();
            setupInfiniteScroll();
            setupScrollPrefetch();

            loadBtn.addEventListener('click', startNewSearch);
            searchInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    startNewSearch();
                }
            });
        });
    </script>
@endsection
