// Loans Page Logic
let allLoans = [];
let allBooks = [];
let allReaders = [];
let allCategories = [];
let currentTab = 'all';
let loanToReturn = null;
let loanToDelete = null;

document.addEventListener('DOMContentLoaded', async () => {
    await loadData();
    setupEventListeners();
    setDefaultDates();
});

async function loadData() {
    try {
        // Load all data in parallel
        const [loansRes, booksRes, readersRes, categoriesRes] = await Promise.all([
            API.get(API_CONFIG.ENDPOINTS.LOANS),
            API.get(API_CONFIG.ENDPOINTS.BOOKS),
            API.get(API_CONFIG.ENDPOINTS.READERS),
            API.get(API_CONFIG.ENDPOINTS.CATEGORIES)
        ]);
        
        allLoans = loansRes.data;
        allBooks = booksRes.data;
        allReaders = readersRes.data;
        allCategories = categoriesRes.data;
        
        populateSelects();
        filterAndRenderLoans();
    } catch (error) {
        Utils.showError(error);
    }
}

function setupEventListeners() {
    document.getElementById('searchInput').addEventListener('input', filterAndRenderLoans);
    document.getElementById('loanForm').addEventListener('submit', handleFormSubmit);
    document.getElementById('loanBook').addEventListener('change', updateCategoryFromBook);
}

function setDefaultDates() {
    const now = new Date();
    const loanDate = now.toISOString().slice(0, 16);
    
    const returnDate = new Date();
    returnDate.setDate(returnDate.getDate() + 30);
    const returnDateStr = returnDate.toISOString().slice(0, 16);
    
    document.getElementById('loanDate').value = loanDate;
    document.getElementById('returnDate').value = returnDateStr;
}

function populateSelects() {
    // Populate books (only available ones)
    const booksSelect = document.getElementById('loanBook');
    const availableBooks = allBooks.filter(book => book.copies_available > 0 && book.status === 'available');
    booksSelect.innerHTML = '<option value="">Оберіть книгу...</option>' + 
        availableBooks.map(book => `
            <option value="${book.id}" data-category="${book.category_id}">
                ${book.title} - ${book.author} (доступно: ${book.copies_available})
            </option>
        `).join('');
    
    // Populate readers
    const readersSelect = document.getElementById('loanReader');
    readersSelect.innerHTML = '<option value="">Оберіть читача...</option>' + 
        allReaders.map(reader => `
            <option value="${reader.id}">${reader.name} (${reader.card_number})</option>
        `).join('');
    
    // Populate categories
    const categoriesSelect = document.getElementById('loanCategory');
    categoriesSelect.innerHTML = '<option value="">Оберіть категорію...</option>' + 
        allCategories.map(cat => `
            <option value="${cat.id}">${cat.name}</option>
        `).join('');
}

function updateCategoryFromBook() {
    const bookSelect = document.getElementById('loanBook');
    const categorySelect = document.getElementById('loanCategory');
    const selectedOption = bookSelect.options[bookSelect.selectedIndex];
    
    if (selectedOption && selectedOption.dataset.category) {
        categorySelect.value = selectedOption.dataset.category;
    }
}

function changeTab(tab) {
    currentTab = tab;
    
    // Update tab buttons
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
        btn.classList.add('text-gray-700');
    });
    
    const activeTab = document.getElementById(`tab${tab.charAt(0).toUpperCase() + tab.slice(1)}`);
    if (activeTab) {
        activeTab.classList.add('active');
        activeTab.classList.remove('text-gray-700');
    }
    
    filterAndRenderLoans();
}

function filterAndRenderLoans() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    
    let filtered = allLoans;
    
    // Filter by tab
    if (currentTab === 'active') {
        filtered = filtered.filter(loan => loan.status === 'active');
    } else if (currentTab === 'overdue') {
        filtered = filtered.filter(loan => loan.status === 'overdue' || 
            (loan.status === 'active' && new Date(loan.return_date) < new Date()));
    } else if (currentTab === 'returned') {
        filtered = filtered.filter(loan => loan.status === 'returned');
    }
    
    // Filter by search
    if (searchTerm) {
        filtered = filtered.filter(loan => 
            loan.title.toLowerCase().includes(searchTerm) ||
            loan.reader_name.toLowerCase().includes(searchTerm) ||
            loan.card_number.toLowerCase().includes(searchTerm)
        );
    }
    
    renderLoansTable(filtered);
}

function renderLoansTable(loans) {
    const tbody = document.getElementById('loansTableBody');
    
    if (!loans || loans.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-12 text-center">
                    <i class="fas fa-inbox text-gray-300 text-5xl mb-3"></i>
                    <p class="text-gray-500">Видач не знайдено</p>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = loans.map(loan => {
        const statusColors = {
            'active': 'bg-green-100 text-green-800',
            'returned': 'bg-gray-100 text-gray-800',
            'overdue': 'bg-red-100 text-red-800'
        };
        
        const statusText = {
            'active': 'Активна',
            'returned': 'Повернено',
            'overdue': 'Прострочено'
        };
        
        // Check if overdue
        const isOverdue = loan.status === 'active' && new Date(loan.return_date) < new Date();
        const displayStatus = isOverdue ? 'overdue' : loan.status;
        
        return `
            <tr class="table-row">
                <td class="px-6 py-4">
                    <div class="font-semibold text-gray-800">${loan.title}</div>
                    <div class="text-sm text-gray-500">${loan.author || 'Автор невідомий'}</div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-gray-800">${loan.reader_name}</div>
                    <div class="text-sm text-gray-500 font-mono">${loan.card_number}</div>
                </td>
                <td class="px-6 py-4 text-gray-600">
                    ${Utils.formatDateTime(loan.loan_date)}
                </td>
                <td class="px-6 py-4 text-gray-600">
                    ${Utils.formatDateTime(loan.return_date)}
                    ${loan.actual_return_date ? `<br><span class="text-xs text-green-600">Повернено: ${Utils.formatDateTime(loan.actual_return_date)}</span>` : ''}
                </td>
                <td class="px-6 py-4">
                    <span class="px-3 py-1 rounded-full text-xs font-medium ${statusColors[displayStatus]}">
                        ${statusText[displayStatus]}
                    </span>
                    ${isOverdue ? `<div class="text-xs text-red-600 mt-1">Прострочено на ${Math.floor((new Date() - new Date(loan.return_date)) / (1000 * 60 * 60 * 24))} днів</div>` : ''}
                </td>
                <td class="px-6 py-4">
                    <div class="flex space-x-2">
                        ${loan.status === 'active' ? `
                            <button onclick="returnLoan(${loan.id})" 
                                class="text-green-600 hover:text-green-800 transition" title="Повернути">
                                <i class="fas fa-undo"></i>
                            </button>
                        ` : ''}
                        <button onclick="deleteLoan(${loan.id})" 
                            class="text-red-600 hover:text-red-800 transition" title="Видалити">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function openModal(mode = 'create') {
    const modal = document.getElementById('loanModal');
    const form = document.getElementById('loanForm');
    
    form.reset();
    setDefaultDates();
    modal.classList.add('active');
}

function closeModal() {
    document.getElementById('loanModal').classList.remove('active');
}

async function handleFormSubmit(e) {
    e.preventDefault();
    
    const data = {
        book_id: parseInt(document.getElementById('loanBook').value),
        reader_id: parseInt(document.getElementById('loanReader').value),
        category_id: parseInt(document.getElementById('loanCategory').value),
        loan_date: document.getElementById('loanDate').value.replace('T', ' ') + ':00',
        return_date: document.getElementById('returnDate').value.replace('T', ' ') + ':00',
        status: 'active'
    };
    
    try {
        await API.post(API_CONFIG.ENDPOINTS.LOANS, data);
        Utils.showNotification('Видачу успішно створено!');
        closeModal();
        await loadData();
    } catch (error) {
        Utils.showError(error);
    }
}

function returnLoan(loanId) {
    loanToReturn = loanId;
    document.getElementById('returnModal').classList.add('active');
}

function closeReturnModal() {
    document.getElementById('returnModal').classList.remove('active');
    loanToReturn = null;
}

async function confirmReturn() {
    if (!loanToReturn) return;
    
    try {
        const now = new Date().toISOString().slice(0, 19).replace('T', ' ');
        await API.put(`${API_CONFIG.ENDPOINTS.LOANS}/${loanToReturn}`, {
            status: 'returned',
            actual_return_date: now
        });
        Utils.showNotification('Книгу успішно повернено!');
        closeReturnModal();
        await loadData();
    } catch (error) {
        Utils.showError(error);
    }
}

function deleteLoan(loanId) {
    loanToDelete = loanId;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
    loanToDelete = null;
}

async function confirmDelete() {
    if (!loanToDelete) return;
    
    try {
        await API.delete(`${API_CONFIG.ENDPOINTS.LOANS}/${loanToDelete}`);
        Utils.showNotification('Видачу успішно видалено!');
        closeDeleteModal();
        await loadData();
    } catch (error) {
        Utils.showError(error);
    }
}

// Close modals on outside click
document.getElementById('loanModal').addEventListener('click', (e) => {
    if (e.target.id === 'loanModal') closeModal();
});

document.getElementById('returnModal').addEventListener('click', (e) => {
    if (e.target.id === 'returnModal') closeReturnModal();
});

document.getElementById('deleteModal').addEventListener('click', (e) => {
    if (e.target.id === 'deleteModal') closeDeleteModal();
});
