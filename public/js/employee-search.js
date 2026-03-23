(() => {
    const d = document;
    const i = d.querySelector('[data-employee-search]');
    const r = d.querySelector('[data-employee-rows]');
    const c = d.querySelector('[data-employee-count]');
    const e = d.querySelector('[data-employee-empty]');
    const u = i && i.dataset.endpoint;
    if (!i || !r || !c || !e || !u) return;

    i.oninput = () => {
        const q = i.value.trim();
        const url = new URL(u, location.origin);
        url.searchParams.set('q', q);
        fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then((res) => res.json())
            .then((data) => {
                const list = data.employees || [];
                r.textContent = '';
                for (const emp of list) {
                    r.insertAdjacentHTML('beforeend', `<tr><td>${emp.emp_id ?? ''}</td><td>${emp.emp_name ?? ''}</td></tr>`);
                }
                c.textContent = q ? `Results for "${q}": ${list.length}` : `All employees: ${list.length}`;
                e.style.display = list.length ? 'none' : 'block';
            })
            .catch(() => {});
    };
})();
