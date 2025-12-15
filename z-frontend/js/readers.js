// Readers Page Logic
let allReaders = [];
let readerToDelete = null;

document.addEventListener('DOMContentLoaded', async () => {
    await loadReaders();
    setupEventListeners();
});

async function loadReaders() {
    try {
        const response = await API.get(API_CONFIG.ENDPOINTS.READERS);
        allReaders = response.data;
        renderReadersGrid(allReaders);
    } catch (error) {
        Utils.showError(error);
    }
}

function setupEventListeners() {
    document.getElementById('searchInput').addEventListener('input', filterReaders);
    document.getElementById('readerForm').addEventListener('submit', handleFormSubmit);
}

function filterReaders() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    
    const filtered = allReaders.filter(reader => 
        reader.name.toLowerCase().includes(searchTerm) ||
        reader.card_number.toLowerCase().includes(searchTerm) ||
        reader.email.toLowerCase().includes(searchTerm) ||
        reader.phone.includes(searchTerm)
    );
    
    renderReadersGrid(filtered);
}

function renderReadersGrid(readers) {
    const grid = document.getElementById('readersGrid');
    
    if (!readers || readers.length === 0) {
        grid.innerHTML = `
            <div class="col-span-full text-center py-12">
                <i class="fas fa-users text-gray-300 text-6xl mb-4"></i>
                <p class="text-gray-500 text-lg">Читачів не знайдено</p>
            </div>
        `;
        return;
    }
    
    grid.innerHTML = readers.map(reader => {
        const activeLoans = reader.active_loans || 0;
        const cardGradients = [
            'from-blue-500 to-purple-600',
            'from-purple-500 to-pink-600',
            'from-cyan-500 to-blue-600',
            'from-indigo-500 to-purple-600'
        ];
        const gradient = cardGradients[Math.floor(Math.random() * cardGradients.length)];
        
        return `
            <div class="reader-card bg-white rounded-xl shadow-md overflow-hidden">
                <div class="bg-gradient-to-r ${gradient} p-6 text-white">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-3xl"></i>
                        </div>
                        <div class="text-right">
                            <p class="text-xs opacity-80">Активних видач</p>
                            <p class="text-2xl font-bold">${activeLoans}</p>
                        </div>
                    </div>
                    <h3 class="text-xl font-bold truncate">${reader.name}</h3>
                    <p class="text-sm opacity-90 font-mono">${reader.card_number}</p>
                </div>
                
                <div class="p-6">
                    <div class="space-y-3 mb-4">
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-phone w-5 text-blue-500"></i>
                            <span class="text-sm ml-2">${reader.phone}</span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-envelope w-5 text-blue-500"></i>
                            <span class="text-sm ml-2 truncate">${reader.email}</span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-calendar w-5 text-blue-500"></i>
                            <span class="text-sm ml-2">${Utils.formatDate(reader.registration_date)}</span>
                        </div>
                    </div>
                    
                    <div class="flex space-x-2 pt-4 border-t border-gray-200">
                        <button onclick="editReader(${reader.id})" 
                            class="flex-1 bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-600 transition flex items-center justify-center space-x-2">
                            <i class="fas fa-edit"></i>
                            <span>Редагувати</span>
                        </button>
                        <button onclick="deleteReader(${reader.id})" 
                            class="flex-1 bg-red-500 text-white py-2 rounded-lg hover:bg-red-600 transition flex items-center justify-center space-x-2">
                            <i class="fas fa-trash"></i>
                            <span>Видалити</span>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function openModal(mode = 'create', readerId = null) {
    const modal = document.getElementById('readerModal');
    const form = document.getElementById('readerForm');
    const title = document.getElementById('modalTitle');
    
    form.reset();
    
    if (mode === 'create') {
        title.textContent = 'Додати читача';
        document.getElementById('readerId').value = '';
    } else {
        title.textContent = 'Редагувати читача';
        const reader = allReaders.find(r => r.id === readerId);
        if (reader) {
            document.getElementById('readerId').value = reader.id;
            document.getElementById('readerName').value = reader.name;
            document.getElementById('readerCardNumber').value = reader.card_number;
            document.getElementById('readerPhone').value = reader.phone;
            document.getElementById('readerEmail').value = reader.email;
        }
    }
    
    modal.classList.add('active');
}

function closeModal() {
    document.getElementById('readerModal').classList.remove('active');
}

async function handleFormSubmit(e) {
    e.preventDefault();
    
    const readerId = document.getElementById('readerId').value;
    const data = {
        name: document.getElementById('readerName').value,
        card_number: document.getElementById('readerCardNumber').value.toUpperCase(),
        phone: document.getElementById('readerPhone').value,
        email: document.getElementById('readerEmail').value
    };
    
    try {
        if (readerId) {
            await API.put(`${API_CONFIG.ENDPOINTS.READERS}/${readerId}`, data);
            Utils.showNotification('Читача успішно оновлено!');
        } else {
            await API.post(API_CONFIG.ENDPOINTS.READERS, data);
            Utils.showNotification('Читача успішно додано!');
        }
        
        closeModal();
        await loadReaders();
    } catch (error) {
        Utils.showError(error);
    }
}

function editReader(readerId) {
    openModal('edit', readerId);
}

function deleteReader(readerId) {
    readerToDelete = readerId;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
    readerToDelete = null;
}

async function confirmDelete() {
    if (!readerToDelete) return;
    
    try {
        await API.delete(`${API_CONFIG.ENDPOINTS.READERS}/${readerToDelete}`);
        Utils.showNotification('Читача успішно видалено!');
        closeDeleteModal();
        await loadReaders();
    } catch (error) {
        Utils.showError(error);
    }
}

// Close modals on outside click
document.getElementById('readerModal').addEventListener('click', (e) => {
    if (e.target.id === 'readerModal') closeModal();
});

document.getElementById('deleteModal').addEventListener('click', (e) => {
    if (e.target.id === 'deleteModal') closeDeleteModal();
});
