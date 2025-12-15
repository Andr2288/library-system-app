// Categories Page Logic
let allCategories = [];
let categoryToDelete = null;

document.addEventListener('DOMContentLoaded', async () => {
    await loadCategories();
    setupEventListeners();
});

async function loadCategories() {
    try {
        const response = await API.get(API_CONFIG.ENDPOINTS.CATEGORIES);
        allCategories = response.data;
        renderCategoriesGrid(allCategories);
    } catch (error) {
        Utils.showError(error);
    }
}

function setupEventListeners() {
    document.getElementById('searchInput').addEventListener('input', filterCategories);
    document.getElementById('categoryForm').addEventListener('submit', handleFormSubmit);
}

function filterCategories() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    
    const filtered = allCategories.filter(category => 
        category.name.toLowerCase().includes(searchTerm) ||
        (category.description && category.description.toLowerCase().includes(searchTerm)) ||
        (category.floor_location && category.floor_location.toLowerCase().includes(searchTerm))
    );
    
    renderCategoriesGrid(filtered);
}

function renderCategoriesGrid(categories) {
    const grid = document.getElementById('categoriesGrid');
    
    if (!categories || categories.length === 0) {
        grid.innerHTML = `
            <div class="col-span-full text-center py-12">
                <i class="fas fa-folder-open text-gray-300 text-6xl mb-4"></i>
                <p class="text-gray-500 text-lg">Категорій не знайдено</p>
            </div>
        `;
        return;
    }
    
    const gradients = [
        { bg: 'from-blue-500 to-cyan-500', icon: 'text-blue-600', border: 'border-blue-500' },
        { bg: 'from-purple-500 to-pink-500', icon: 'text-purple-600', border: 'border-purple-500' },
        { bg: 'from-green-500 to-teal-500', icon: 'text-green-600', border: 'border-green-500' },
        { bg: 'from-orange-500 to-red-500', icon: 'text-orange-600', border: 'border-orange-500' },
        { bg: 'from-indigo-500 to-purple-500', icon: 'text-indigo-600', border: 'border-indigo-500' },
        { bg: 'from-pink-500 to-rose-500', icon: 'text-pink-600', border: 'border-pink-500' }
    ];
    
    grid.innerHTML = categories.map((category, index) => {
        const style = gradients[index % gradients.length];
        const booksCount = category.books_count || 0;
        
        return `
            <div class="category-card bg-white rounded-xl shadow-md overflow-hidden border-t-4 ${style.border}">
                <div class="p-6">
                    <!-- Header -->
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-gradient-to-br ${style.bg} rounded-lg flex items-center justify-center">
                                <i class="fas fa-folder text-white text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">${category.name}</h3>
                                <p class="text-sm text-gray-500">${booksCount} ${booksCount === 1 ? 'книга' : booksCount < 5 ? 'книги' : 'книг'}</p>
                            </div>
                        </div>
                        <div class="flex space-x-1">
                            <button onclick="editCategory(${category.id})" 
                                class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Редагувати">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteCategory(${category.id})" 
                                class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Видалити">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Description -->
                    <div class="mb-4">
                        <p class="text-gray-600 text-sm line-clamp-2">
                            ${category.description || 'Опис відсутній'}
                        </p>
                    </div>
                    
                    <!-- Location -->
                    ${category.floor_location ? `
                        <div class="flex items-center text-sm text-gray-500 bg-gray-50 px-3 py-2 rounded-lg">
                            <i class="fas fa-map-marker-alt ${style.icon} mr-2"></i>
                            <span>${category.floor_location}</span>
                        </div>
                    ` : ''}
                </div>
                
                <!-- Footer Stats -->
                <div class="bg-gradient-to-r ${style.bg} bg-opacity-10 px-6 py-3 flex items-center justify-between">
                    <div class="flex items-center space-x-2 text-sm ${style.icon}">
                        <i class="fas fa-book"></i>
                        <span class="font-medium">${booksCount} книг у фонді</span>
                    </div>
                    <button onclick="viewCategoryBooks(${category.id})" 
                        class="text-sm ${style.icon} font-medium hover:underline">
                        Переглянути →
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

function openModal(mode = 'create', categoryId = null) {
    const modal = document.getElementById('categoryModal');
    const form = document.getElementById('categoryForm');
    const title = document.getElementById('modalTitle');
    
    form.reset();
    
    if (mode === 'create') {
        title.textContent = 'Додати категорію';
        document.getElementById('categoryId').value = '';
    } else {
        title.textContent = 'Редагувати категорію';
        const category = allCategories.find(c => c.id === categoryId);
        if (category) {
            document.getElementById('categoryId').value = category.id;
            document.getElementById('categoryName').value = category.name;
            document.getElementById('categoryDescription').value = category.description || '';
            document.getElementById('categoryLocation').value = category.floor_location || '';
        }
    }
    
    modal.classList.add('active');
}

function closeModal() {
    document.getElementById('categoryModal').classList.remove('active');
}

async function handleFormSubmit(e) {
    e.preventDefault();
    
    const categoryId = document.getElementById('categoryId').value;
    const data = {
        name: document.getElementById('categoryName').value,
        description: document.getElementById('categoryDescription').value,
        floor_location: document.getElementById('categoryLocation').value
    };
    
    try {
        if (categoryId) {
            await API.put(`${API_CONFIG.ENDPOINTS.CATEGORIES}/${categoryId}`, data);
            Utils.showNotification('Категорію успішно оновлено!');
        } else {
            await API.post(API_CONFIG.ENDPOINTS.CATEGORIES, data);
            Utils.showNotification('Категорію успішно додано!');
        }
        
        closeModal();
        await loadCategories();
    } catch (error) {
        Utils.showError(error);
    }
}

function editCategory(categoryId) {
    openModal('edit', categoryId);
}

function deleteCategory(categoryId) {
    categoryToDelete = categoryId;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
    categoryToDelete = null;
}

async function confirmDelete() {
    if (!categoryToDelete) return;
    
    try {
        await API.delete(`${API_CONFIG.ENDPOINTS.CATEGORIES}/${categoryToDelete}`);
        Utils.showNotification('Категорію успішно видалено!');
        closeDeleteModal();
        await loadCategories();
    } catch (error) {
        Utils.showError(error);
    }
}

function viewCategoryBooks(categoryId) {
    // Redirect to books page with category filter
    window.location.href = `books.html?category=${categoryId}`;
}

// Close modals on outside click
document.getElementById('categoryModal').addEventListener('click', (e) => {
    if (e.target.id === 'categoryModal') closeModal();
});

document.getElementById('deleteModal').addEventListener('click', (e) => {
    if (e.target.id === 'deleteModal') closeDeleteModal();
});
