// Books Page Logic
let allBooks = [];
let allCategories = [];
let bookToDelete = null;

document.addEventListener('DOMContentLoaded', async () => {
    await loadData();
    setupEventListeners();
});

async function loadData() {
    try {
        // Load books and categories
        const [booksResponse, categoriesResponse] = await Promise.all([
            API.get(API_CONFIG.ENDPOINTS.BOOKS),
            API.get(API_CONFIG.ENDPOINTS.CATEGORIES)
        ]);

        allBooks = booksResponse.data;
        allCategories = categoriesResponse.data;

        // Populate category selects
        populateCategorySelects();

        // Render books table
        renderBooksTable(allBooks);

    } catch (error) {
        Utils.showError(error);
    }
}

function setupEventListeners() {
    // Search
    document.getElementById('searchInput').addEventListener('input', filterBooks);

    // Filters
    document.getElementById('filterCategory').addEventListener('change', filterBooks);
    document.getElementById('filterStatus').addEventListener('change', filterBooks);

    // Form submit
    document.getElementById('bookForm').addEventListener('submit', handleFormSubmit);
}

function populateCategorySelects() {
    const selects = ['filterCategory', 'bookCategory'];

    selects.forEach(selectId => {
        const select = document.getElementById(selectId);
        const options = allCategories.map(cat =>
            `<option value="${cat.id}">${cat.name}</option>`
        ).join('');

        if (selectId === 'filterCategory') {
            select.innerHTML = '<option value="">Всі категорії</option>' + options;
        } else {
            select.innerHTML = '<option value="">Без категорії</option>' + options;
        }
    });
}

function filterBooks() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const categoryFilter = document.getElementById('filterCategory').value;
    const statusFilter = document.getElementById('filterStatus').value;

    const filtered = allBooks.filter(book => {
        const matchesSearch = book.title.toLowerCase().includes(searchTerm) ||
            book.author.toLowerCase().includes(searchTerm) ||
            book.isbn.toLowerCase().includes(searchTerm);

        const matchesCategory = !categoryFilter || book.category_id == categoryFilter;
        const matchesStatus = !statusFilter || book.status === statusFilter;

        return matchesSearch && matchesCategory && matchesStatus;
    });

    renderBooksTable(filtered);
}

function renderBooksTable(books) {
    const tbody = document.getElementById('booksTableBody');

    if (!books || books.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="px-6 py-12 text-center">
                    <i class="fas fa-book text-gray-300 text-5xl mb-3"></i>
                    <p class="text-gray-500">Книг не знайдено</p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = books.map(book => {
        const statusColors = {
            'available': 'bg-green-100 text-green-800',
            'damaged': 'bg-yellow-100 text-yellow-800',
            'lost': 'bg-red-100 text-red-800'
        };

        const statusText = {
            'available': 'Доступна',
            'damaged': 'Пошкоджена',
            'lost': 'Втрачена'
        };

        return `
            <tr class="table-row">
                <td class="px-6 py-4">
                    <div class="font-semibold text-gray-800">${book.title}</div>
                </td>
                <td class="px-6 py-4 text-gray-600">${book.author}</td>
                <td class="px-6 py-4 text-gray-600 font-mono text-sm">${book.isbn}</td>
                <td class="px-6 py-4 text-gray-600">${book.year}</td>
                <td class="px-6 py-4">
                    <span class="text-sm text-gray-600">${book.category_name || 'Без категорії'}</span>
                </td>
                <td class="px-6 py-4">
                    <span class="font-semibold ${book.copies_available > 0 ? 'text-green-600' : 'text-red-600'}">
                        ${book.copies_available} / ${book.copies_total}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <span class="px-3 py-1 rounded-full text-xs font-medium ${statusColors[book.status]}">
                        ${statusText[book.status]}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <div class="flex space-x-2">
                        <button onclick="editBook(${book.id})" 
                            class="text-blue-600 hover:text-blue-800 transition" title="Редагувати">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteBook(${book.id})" 
                            class="text-red-600 hover:text-red-800 transition" title="Видалити">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function openModal(mode = 'create', bookId = null) {
    const modal = document.getElementById('bookModal');
    const form = document.getElementById('bookForm');
    const title = document.getElementById('modalTitle');

    // Clear previous errors
    document.querySelectorAll('.error-message').forEach(el => el.remove());
    document.querySelectorAll('.border-red-500').forEach(el => {
        el.classList.remove('border-red-500');
        el.classList.add('border-gray-300');
    });

    if (mode === 'create') {
        title.textContent = 'Додати книгу';
        form.reset();
        document.getElementById('bookId').value = '';
    } else {
        title.textContent = 'Редагувати книгу';
        const book = allBooks.find(b => b.id === bookId);
        if (book) {
            document.getElementById('bookId').value = book.id;
            document.getElementById('bookTitle').value = book.title;
            document.getElementById('bookAuthor').value = book.author;
            document.getElementById('bookISBN').value = book.isbn;
            document.getElementById('bookYear').value = book.year;
            document.getElementById('bookCategory').value = book.category_id || '';
            document.getElementById('bookCopiesTotal').value = book.copies_total;
            document.getElementById('bookCopiesAvailable').value = book.copies_available;
            document.getElementById('bookStatus').value = book.status;
        }
    }

    modal.classList.add('active');
}

function closeModal() {
    document.getElementById('bookModal').classList.remove('active');
}

async function handleFormSubmit(e) {
    e.preventDefault();

    // Clear previous errors
    document.querySelectorAll('.error-message').forEach(el => el.remove());
    document.querySelectorAll('.border-red-500').forEach(el => {
        el.classList.remove('border-red-500');
        el.classList.add('border-gray-300');
    });

    const bookId = document.getElementById('bookId').value;
    const data = {
        title: document.getElementById('bookTitle').value,
        author: document.getElementById('bookAuthor').value,
        isbn: document.getElementById('bookISBN').value,
        year: parseInt(document.getElementById('bookYear').value),
        category_id: document.getElementById('bookCategory').value || null,
        copies_total: parseInt(document.getElementById('bookCopiesTotal').value),
        copies_available: parseInt(document.getElementById('bookCopiesAvailable').value),
        status: document.getElementById('bookStatus').value
    };

    try {
        if (bookId) {
            // Update
            await API.put(`${API_CONFIG.ENDPOINTS.BOOKS}/${bookId}`, data);
            Utils.showNotification('Книгу успішно оновлено!');
        } else {
            // Create
            await API.post(API_CONFIG.ENDPOINTS.BOOKS, data);
            Utils.showNotification('Книгу успішно додано!');
        }

        closeModal();
        await loadData();

    } catch (error) {
        // Check if error has validation errors
        if (error.errors) {
            // Display field-specific errors
            Object.keys(error.errors).forEach(field => {
                const fieldMap = {
                    'title': 'bookTitle',
                    'author': 'bookAuthor',
                    'isbn': 'bookISBN',
                    'year': 'bookYear'
                };

                const inputId = fieldMap[field];
                if (inputId) {
                    const input = document.getElementById(inputId);
                    if (input) {
                        // Add red border
                        input.classList.remove('border-gray-300');
                        input.classList.add('border-red-500');

                        // Add error message
                        const errorDiv = document.createElement('p');
                        errorDiv.className = 'error-message text-red-500 text-sm mt-1';
                        errorDiv.textContent = error.errors[field];
                        input.parentElement.appendChild(errorDiv);
                    }
                }
            });

            // Show general error at the top
            const form = document.getElementById('bookForm');
            const errorAlert = document.createElement('div');
            errorAlert.className = 'error-message bg-red-50 border border-red-500 text-red-700 px-4 py-3 rounded mb-4';
            errorAlert.innerHTML = `<strong>Помилка валідації:</strong> Перевірте правильність заповнення полів.`;
            form.insertBefore(errorAlert, form.firstChild);
        } else {
            Utils.showError(error);
        }
    }
}

function editBook(bookId) {
    openModal('edit', bookId);
}

function deleteBook(bookId) {
    bookToDelete = bookId;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
    bookToDelete = null;
}

async function confirmDelete() {
    if (!bookToDelete) return;

    try {
        await API.delete(`${API_CONFIG.ENDPOINTS.BOOKS}/${bookToDelete}`);
        Utils.showNotification('Книгу успішно видалено!');
        closeDeleteModal();
        await loadData();
    } catch (error) {
        Utils.showError(error);
    }
}

// Close modal on outside click
document.getElementById('bookModal').addEventListener('click', (e) => {
    if (e.target.id === 'bookModal') closeModal();
});

document.getElementById('deleteModal').addEventListener('click', (e) => {
    if (e.target.id === 'deleteModal') closeDeleteModal();
});