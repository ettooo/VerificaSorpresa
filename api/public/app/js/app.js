const API_BASE = '/';

let currentRole = 'admin'; // 'admin' or 'supplier'
let currentSupplierId = null;
let currentOffset = 0;
const LIMIT = 10;

// DOM Elements
const piecesBody = document.getElementById('pieces-body');
const sectionTitle = document.getElementById('section-title');
const btnAddPiece = document.getElementById('btn-add-piece');
const btnAdminView = document.getElementById('btn-admin-view');
const btnSupplierView = document.getElementById('btn-supplier-view');
const supplierSelection = document.getElementById('supplier-selection');
const supplierSelect = document.getElementById('supplier-select');
const btnLoginSupplier = document.getElementById('btn-login-supplier');
const userRoleSpan = document.getElementById('current-user-role');
const pieceDialog = document.getElementById('piece-dialog');
const pieceForm = document.getElementById('piece-form');
const paginationDiv = document.getElementById('pagination');

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadPieces();
    loadSuppliers();
    setupEventListeners();
});

function setupEventListeners() {
    btnAdminView.addEventListener('click', () => switchRole('admin'));
    btnSupplierView.addEventListener('click', () => switchRole('supplier'));

    btnLoginSupplier.addEventListener('click', () => {
        currentSupplierId = supplierSelect.value;
        if (currentSupplierId) {
            userRoleSpan.innerText = `Supplier: ${currentSupplierId}`;
            supplierSelection.style.display = 'none';
            loadPieces();
        }
    });

    btnAddPiece.addEventListener('click', () => {
        pieceForm.reset();
        document.getElementById('dialog-title').innerText = 'Nuovo Pezzo';
        document.getElementById('cost-group').style.display = currentRole === 'supplier' ? 'block' : 'none';
        document.getElementById('p-pid').disabled = false;
        pieceDialog.showModal();
    });

    document.getElementById('btn-close-dialog').addEventListener('click', () => pieceDialog.close());

    pieceForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(pieceForm);
        const data = Object.fromEntries(formData.entries());
        
        try {
            if (document.getElementById('dialog-title').innerText.includes('Modifica')) {
                await fetch(`${API_BASE}pieces/${data.pid}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
            } else {
                await fetch(`${API_BASE}pieces`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
            }

            // Se è fornitore, aggiorna anche il catalogo (costo)
            if (currentRole === 'supplier' && data.costo) {
                await fetch(`${API_BASE}suppliers/catalog`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        fid: currentSupplierId,
                        pid: data.pid,
                        costo: data.costo
                    })
                });
            }

            pieceDialog.close();
            loadPieces();
        } catch (err) {
            alert('Errore durante il salvataggio');
        }
    });
}

function switchRole(role) {
    currentRole = role;
    btnAdminView.classList.toggle('active', role === 'admin');
    btnSupplierView.classList.toggle('active', role === 'supplier');
    
    if (role === 'admin') {
        userRoleSpan.innerText = 'Admin';
        supplierSelection.style.display = 'none';
        currentSupplierId = null;
    } else {
        supplierSelection.style.display = 'block';
    }
    loadPieces();
}

async function loadSuppliers() {
    try {
        const res = await fetch(`${API_BASE}suppliers`);
        const json = await res.json();
        const suppliers = json.data;
        supplierSelect.innerHTML = suppliers.map(s => `<option value="${s.fid}">${s.fnome} (${s.fid})</option>`).join('');
    } catch (err) {
        console.error('Error loading suppliers', err);
    }
}

async function loadPieces(offset = 0) {
    currentOffset = offset;
    try {
        const res = await fetch(`${API_BASE}pieces?limit=${LIMIT}&offset=${offset}`);
        const json = await res.json();
        const pieces = json.data.items;
        const total = json.data.pagination.total;

        renderPieces(pieces);
        renderPagination(total);
    } catch (err) {
        console.error('Error loading pieces', err);
    }
}

function renderPieces(pieces) {
    piecesBody.innerHTML = pieces.map(p => `
        <tr>
            <td>${p.pid}</td>
            <td>${p.pnome}</td>
            <td>${p.colore}</td>
            <td>
                <button class="btn btn-secondary btn-sm" onclick="viewDetails('${p.pid}')">Dettagli</button>
                <button class="btn btn-primary btn-sm" onclick="editPiece('${p.pid}')">Modifica</button>
                <button class="btn btn-danger btn-sm" onclick="deletePiece('${p.pid}')">Elimina</button>
            </td>
        </tr>
    `).join('');
}

function renderPagination(total) {
    const pages = Math.ceil(total / LIMIT);
    let html = '';
    for (let i = 0; i < pages; i++) {
        const offset = i * LIMIT;
        html += `<button class="${offset === currentOffset ? 'active' : ''}" onclick="loadPieces(${offset})">${i + 1}</button>`;
    }
    paginationDiv.innerHTML = html;
}

window.viewDetails = async (id) => {
    try {
        const res = await fetch(`${API_BASE}pieces/${id}`);
        const json = await res.json();
        const p = json.data;
        
        pieceForm.reset();
        document.getElementById('dialog-title').innerText = 'Dettagli Pezzo';
        document.getElementById('p-pid').value = p.pid;
        document.getElementById('p-pid').disabled = true;
        document.getElementById('p-nome').value = p.pnome;
        document.getElementById('p-nome').disabled = true;
        document.getElementById('p-colore').value = p.colore;
        document.getElementById('p-colore').disabled = true;
        document.getElementById('cost-group').style.display = 'none';
        document.getElementById('btn-save-piece').style.display = 'none';
        
        pieceDialog.showModal();
    } catch (err) {
        alert('Errore caricamento dettagli');
    }
};

window.editPiece = async (id) => {
    try {
        const res = await fetch(`${API_BASE}pieces/${id}`);
        const json = await res.json();
        const p = json.data;
        
        pieceForm.reset();
        document.getElementById('dialog-title').innerText = 'Modifica Pezzo';
        document.getElementById('p-pid').value = p.pid;
        document.getElementById('p-pid').disabled = true;
        document.getElementById('p-nome').value = p.pnome;
        document.getElementById('p-nome').disabled = false;
        document.getElementById('p-colore').value = p.colore;
        document.getElementById('p-colore').disabled = false;
        document.getElementById('btn-save-piece').style.display = 'block';

        if (currentRole === 'supplier') {
            document.getElementById('cost-group').style.display = 'block';
        } else {
            document.getElementById('cost-group').style.display = 'none';
        }
        
        pieceDialog.showModal();
    } catch (err) {
        alert('Errore caricamento per modifica');
    }
};

window.deletePiece = async (id) => {
    if (!confirm('Eliminare il pezzo?')) return;
    try {
        await fetch(`${API_BASE}pieces/${id}`, { method: 'DELETE' });
        loadPieces();
    } catch (err) {
        alert('Errore eliminazione');
    }
};
