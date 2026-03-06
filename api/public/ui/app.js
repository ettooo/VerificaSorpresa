(function(){
  // utilities
  const output = document.getElementById('output');
  const statusEl = document.getElementById('status');
  const timeEl = document.getElementById('time');

  function show(text){ output.textContent = text; }
  function setMeta(status, ms){ statusEl.textContent = status; timeEl.textContent = ms + ' ms'; }

  function renderJson(data){
    if (Array.isArray(data)){
      const container = document.createElement('div');
      const title = document.createElement('h3');
      title.textContent = 'Prodotti:';
      container.appendChild(title);
      const list = document.createElement('ul');
      data.forEach(item => {
        const li = document.createElement('li');
        const attrs = Object.entries(item).map(([k,v]) => `<strong>${k}</strong>: <span class="val">${v}</span>`).join(', ');
        li.innerHTML = attrs;
        list.appendChild(li);
      });
      container.appendChild(list);
      output.innerHTML = '';
      output.appendChild(container);
    } else if (typeof data === 'object' && data !== null){
      const container = document.createElement('div');
      const list = document.createElement('ul');
      Object.entries(data).forEach(([k,v]) => {
        const li = document.createElement('li');
        li.innerHTML = `<strong>${k}</strong>: <span class="val">${v}</span>`;
        list.appendChild(li);
      });
      container.appendChild(list);
      output.innerHTML = '';
      output.appendChild(container);
    } else {
      show(String(data));
    }
  }

  async function callEndpoint(ep){
    try{
      const start = performance.now();
      const res = await fetch(ep, {cache: 'no-store', headers:{'Accept':'application/json'}});
      const ms = Math.round(performance.now() - start);
      setMeta(res.status + ' ' + res.statusText, ms);
      const ct = res.headers.get('content-type') || '';
      if (ct.includes('application/json')){
        const json = await res.json();
        renderJson(json);
      } else {
        const txt = await res.text();
        show(txt);
      }
    } catch (err){
      setMeta('ERR', 0);
      show(String(err));
    }
  }

  // query viewer setup
  const buttons = document.querySelectorAll('button[data-endpoint]');
  const callBtn = document.getElementById('callBtn');
  const endpointInput = document.getElementById('endpointInput');
  const autoRefresh = document.getElementById('autoRefresh');
  let intervalId = null;
  let lastEndpoint = endpointInput.value || '/q1';
  const params = new URLSearchParams(window.location.search);
  const epParam = params.get('ep');
  if (epParam) {
    lastEndpoint = epParam;
    endpointInput.value = epParam;
  }

  buttons.forEach(b => b.addEventListener('click', () => {
    const ep = b.getAttribute('data-endpoint');
    endpointInput.value = ep;
    lastEndpoint = ep;
    callEndpoint(ep);
  }));

  callBtn.addEventListener('click', () => {
    const ep = endpointInput.value.trim() || '/q1';
    lastEndpoint = ep;
    callEndpoint(ep);
  });

  endpointInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
      const ep = endpointInput.value.trim() || '/q1';
      lastEndpoint = ep;
      callEndpoint(ep);
    }
  });

  autoRefresh.addEventListener('change', () => {
    if (autoRefresh.checked){
      intervalId = setInterval(() => callEndpoint(lastEndpoint), 5000);
    } else {
      clearInterval(intervalId);
      intervalId = null;
    }
  });

  // run initial only if ep param exists
  if (epParam) {
    callEndpoint(lastEndpoint);
  }

  // check session on load
  async function checkSession(){
    const res = await fetch('/me',{credentials:'include'});
    if(res.ok){
      const me = await res.json();
      sessionSupplier = me;
      loggedInState();
    }
  }
  checkSession();

  // authentication helpers
  const loginForm = document.getElementById('loginForm');
  const registerForm = document.getElementById('registerForm');
  const showRegisterBtn = document.getElementById('showRegisterBtn');
  const cancelRegister = document.getElementById('cancelRegister');
  const authBlock = document.getElementById('authBlock');
  const regBlock = document.getElementById('regBlock');
  const loggedControls = document.getElementById('loggedControls');
  const loggedName = document.getElementById('loggedName');
  const logoutBtn = document.getElementById('logoutBtn');

  loginForm.addEventListener('submit', async e=>{
    e.preventDefault();
    const data = Object.fromEntries(new FormData(loginForm));
    const res = await fetch('/login',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data),credentials:'include'});
    if(res.ok){
      sessionSupplier = await res.json();
      loggedInState();
    } else {
      alert('Login fallito');
    }
  });
  showRegisterBtn.addEventListener('click',()=>{
    authBlock.classList.add('hidden');
    regBlock.classList.remove('hidden');
  });
  cancelRegister.addEventListener('click',()=>{
    regBlock.classList.add('hidden');
    authBlock.classList.remove('hidden');
  });
  registerForm.addEventListener('submit', async e=>{
    e.preventDefault();
    const data = Object.fromEntries(new FormData(registerForm));
    const res = await fetch('/register',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data),credentials:'include'});
    if(res.ok){
      sessionSupplier = await res.json();
      loggedInState();
    } else {
      alert('Registrazione fallita');
    }
  });
  logoutBtn.addEventListener('click', async ()=>{
    await fetch('/logout',{method:'POST',credentials:'include'});
    sessionSupplier = null;
    loggedOutState();
  });

  async function loggedInState(){
    // re-read session from server to ensure cookie registered
    await checkSession();
    authBlock.classList.add('hidden');
    regBlock.classList.add('hidden');
    loggedControls.classList.remove('hidden');
    loggedName.textContent = sessionSupplier.fnome;
    // set current supplier to self
    currentSupplier = sessionSupplier.fid;
    supplierSelect.value = currentSupplier;
    loadSuppliers();
    renderSupplierCatalog();
  }
  function loggedOutState(){
    authBlock.classList.remove('hidden');
    regBlock.classList.add('hidden');
    loggedControls.classList.add('hidden');
    currentSupplier = null;
    supplierSelect.disabled = false;
    supplierSelect.value = '';
    supplierCatalog.innerHTML='';
    loadSuppliers();
  }

  // tab management
  const tabButtons = document.querySelectorAll('header .tabs button');
  const dashboardSection = document.getElementById('dashboardSection');
  const querySection = document.getElementById('querySection');
  const supplierSection = document.getElementById('supplierSection');
  const adminSection = document.getElementById('adminSection');
  
  tabButtons.forEach(btn => btn.addEventListener('click', () => {
    tabButtons.forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    const tab = btn.getAttribute('data-tab');
    dashboardSection.classList.toggle('hidden', tab!=='dashboard');
    querySection.classList.toggle('hidden', tab!=='query');
    supplierSection.classList.toggle('hidden', tab!=='supplier');
    adminSection.classList.toggle('hidden', tab!=='admin');
  }));

  // dashboard card click handlers
  const dashboardCards = document.querySelectorAll('.clickable-card');
  dashboardCards.forEach(card => {
    card.addEventListener('click', () => {
      const endpoint = card.getAttribute('data-endpoint');
      endpointInput.value = endpoint;
      lastEndpoint = endpoint;
      callEndpoint(endpoint);
      // switch to query tab
      tabButtons.forEach(b => b.classList.remove('active'));
      tabButtons[1].classList.add('active'); // query tab is second
      dashboardSection.classList.add('hidden');
      querySection.classList.remove('hidden');
    });
  });

  // authentication state
  let sessionSupplier = null;

  // supplier management
  let currentSupplier = null;
  let supPage = 1;
  const supPer = 5;
  const supplierSelect = document.getElementById('supplierSelect');
  const addCatalogItemBtn = document.getElementById('addCatalogItemBtn');
  const supplierCatalog = document.getElementById('supplierCatalog');
  const prevPageSup = document.getElementById('prevPageSup');
  const nextPageSup = document.getElementById('nextPageSup');
  const pageInfoSup = document.getElementById('pageInfoSup');
  const detailDialog = document.getElementById('detailDialog');
  const formDialog = document.getElementById('formDialog');

  async function loadSuppliers(page=1){
    const res = await fetch(`/suppliers?page=${page}&per_page=${supPer}`,{credentials:'include'});
    const json = await res.json();
    supplierSelect.innerHTML = '<option value="">-- seleziona fornitore --</option>';
    json.data.forEach(s=>{
      const opt = document.createElement('option');
      opt.value = s.fid;
      opt.textContent = s.fnome;
      supplierSelect.appendChild(opt);
    });
    if(sessionSupplier){
      supplierSelect.value = sessionSupplier.fid;
      supplierSelect.disabled = true;
      document.getElementById('chooseSupplierControls').classList.add('hidden');
    } else {
      supplierSelect.disabled = false;
      document.getElementById('chooseSupplierControls').classList.remove('hidden');
    }
    // pagination not implemented for supplier list in UI yet
  }

  supplierSelect.addEventListener('change', () => {
    currentSupplier = supplierSelect.value || null;
    supPage = 1;
    renderSupplierCatalog();
  });

  addCatalogItemBtn.addEventListener('click', () => {
    if (!currentSupplier) return;
    showFormDialog('add');
  });

  prevPageSup.addEventListener('click', ()=>{
    if (supPage>1){ supPage--; renderSupplierCatalog(); }
  });
  nextPageSup.addEventListener('click', ()=>{
    supPage++; renderSupplierCatalog();
  });

  async function renderSupplierCatalog(){
    if (!currentSupplier){ supplierCatalog.innerHTML=''; addCatalogItemBtn.classList.add('hidden'); return; }
    // only show add button if logged in as this supplier
    if(sessionSupplier && sessionSupplier.fid === currentSupplier){
      addCatalogItemBtn.classList.remove('hidden');
    } else {
      addCatalogItemBtn.classList.add('hidden');
    }
    const res = await fetch(`/suppliers/${currentSupplier}/catalog?page=${supPage}&per_page=${supPer}`,{credentials:'include'});
    const json = await res.json();
    const table = document.createElement('table');
    table.innerHTML = `<thead><tr><th>PID</th><th>Nome</th><th>Colore</th><th>Costo</th><th>Azioni</th></tr></thead>`;
    const tbody = document.createElement('tbody');
    json.data.forEach(item=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${item.pid}</td><td>${item.pnome}</td><td>${item.colore}</td><td>${item.costo}</td>`;
      const actions = document.createElement('td');
      const viewBtn = document.createElement('button'); viewBtn.textContent='Dettagli'; viewBtn.className='small';
      viewBtn.addEventListener('click',()=>showDetail(`/suppliers/${currentSupplier}/catalog/${item.pid}`));
      actions.append(viewBtn);
      // allow edit/delete only if logged in as this supplier
      if(sessionSupplier && sessionSupplier.fid === currentSupplier){
        const editBtn = document.createElement('button'); editBtn.textContent='Modifica'; editBtn.className='small';
        editBtn.addEventListener('click',()=>showFormDialog('edit',item));
        const delBtn = document.createElement('button'); delBtn.textContent='Elimina'; delBtn.className='small';
        delBtn.addEventListener('click',()=>deleteCatalogItem(item.pid));
        actions.append(editBtn,delBtn);
      }
      tr.appendChild(actions);
      tbody.appendChild(tr);
    });
    table.appendChild(tbody);
    supplierCatalog.innerHTML = '';
    supplierCatalog.appendChild(table);
    pageInfoSup.textContent = `Pagina ${json.page} di ${Math.ceil(json.total/json.per_page)}`;
    prevPageSup.disabled = json.page<=1;
    nextPageSup.disabled = json.page*json.per_page >= json.total;
  }

  async function deleteCatalogItem(pid){
    if (!currentSupplier) return;
    if(!confirm('Elimina pezzo dal catalogo?'))return;
    await fetch(`/suppliers/${currentSupplier}/catalog/${pid}`,{method:'DELETE',credentials:'include'});
    renderSupplierCatalog();
  }

  function showDetail(url){
    fetch(url).then(r=>r.json()).then(obj=>{
      detailDialog.innerHTML = '<pre>'+JSON.stringify(obj,null,2)+'</pre><button id="closeDetail">Chiudi</button>';
      detailDialog.showModal();
      document.getElementById('closeDetail').addEventListener('click',()=>detailDialog.close());
    });
  }

  function showFormDialog(mode,item){
    formDialog.innerHTML='';
    const form = document.createElement('form'); form.className='dialog-form';
    form.innerHTML += `<label>PID: <input name="pid" value="${item?item.pid:''}" ${mode==='edit'?'readonly':''}></label>`;
    form.innerHTML += `<label>Nome: <input name="pnome" value="${item?item.pnome:''}"></label>`;
    form.innerHTML += `<label>Colore: <input name="colore" value="${item?item.colore:''}"></label>`;
    form.innerHTML += `<label>Costo: <input name="costo" type="number" step="0.01" value="${item?item.costo:''}"></label>`;
    form.innerHTML += `<div><button type="submit">OK</button><button type="button" id="cancelForm">Annulla</button></div>`;
    formDialog.appendChild(form);
    formDialog.showModal();
    document.getElementById('cancelForm').addEventListener('click',()=>formDialog.close());
    form.addEventListener('submit', async e=>{
      e.preventDefault();
      const data = Object.fromEntries(new FormData(form));
      const url = `/suppliers/${currentSupplier}/catalog` + (mode==='edit'?`/${item.pid}`:'');
      const method = mode==='edit'?'PUT':'POST';
      await fetch(url,{method,headers:{'Content-Type':'application/json'},body:JSON.stringify(data),credentials:'include'});
      formDialog.close();
      renderSupplierCatalog();
    });
  }

  loadSuppliers();

  // admin section
  let adminType = 'suppliers';
  let adminPage = 1;
  const prevPageAdmin = document.getElementById('prevPageAdmin');
  const nextPageAdmin = document.getElementById('nextPageAdmin');
  const pageInfoAdmin = document.getElementById('pageInfoAdmin');
  const adminContent = document.getElementById('adminContent');
  document.querySelectorAll('.admin-tabs button').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.admin-tabs button').forEach(b=>b.classList.remove('active'));
      btn.classList.add('active');
      adminType = btn.getAttribute('data-admintab');
      adminPage = 1;
      loadAdminPage();
    });
  });
  prevPageAdmin.addEventListener('click',()=>{ if(adminPage>1){adminPage--; loadAdminPage();}});
  nextPageAdmin.addEventListener('click',()=>{ adminPage++; loadAdminPage();});

  async function loadAdminPage(){
    let url = '/' + (adminType==='suppliers'?'suppliers':'pieces') + `?page=${adminPage}&per_page=10`;
    const res = await fetch(url,{credentials:'include'});
    const json = await res.json();
    const table = document.createElement('table');
    let headers = [];
    if(adminType==='suppliers') headers=['fid','fnome','indirizzo']; else headers=['pid','pnome','colore'];
    table.innerHTML = '<thead><tr>'+headers.map(h=>`<th>${h}</th>`).join('')+'<th>Azioni</th></tr></thead>';
    const tbody = document.createElement('tbody');
    json.data.forEach(item=>{
      const tr = document.createElement('tr');
      headers.forEach(h=> tr.innerHTML += `<td>${item[h]}</td>`);
      const td = document.createElement('td');
      const b = document.createElement('button'); b.textContent='Dettagli';b.className='small';
      b.addEventListener('click',()=> showDetail(`/${adminType}/${item[headers[0]]}`));
      td.appendChild(b);
      tr.appendChild(td);
      tbody.appendChild(tr);
    });
    table.appendChild(tbody);
    adminContent.innerHTML='';
    adminContent.appendChild(table);
    pageInfoAdmin.textContent = `Pagina ${json.page} di ${Math.ceil(json.total/json.per_page)}`;
    prevPageAdmin.disabled = json.page<=1;
    nextPageAdmin.disabled = json.page*json.per_page >= json.total;
  }

})();
