// Dashboard Logic
document.addEventListener('DOMContentLoaded', async () => {
    await loadDashboardData();
});

async function loadDashboardData() {
    try {
        // Load all data in parallel
        const [books, readers, activeLoans, overdueLoans, popularCategories] = await Promise.all([
            API.get(API_CONFIG.ENDPOINTS.BOOKS),
            API.get(API_CONFIG.ENDPOINTS.READERS),
            API.get(API_CONFIG.ENDPOINTS.LOANS_ACTIVE),
            API.get(API_CONFIG.ENDPOINTS.LOANS_OVERDUE),
            API.get(API_CONFIG.ENDPOINTS.CATEGORIES_POPULAR + '?limit=5')
        ]);
        
        // Update stats
        updateStats(books.data, readers.data, activeLoans.data, overdueLoans.data);
        
        // Update popular categories
        renderPopularCategories(popularCategories.data);
        
        // Load recent loans
        const allLoans = await API.get(API_CONFIG.ENDPOINTS.LOANS);
        renderRecentLoans(allLoans.data.slice(0, 5));
        
        // Render overdue books
        renderOverdueBooks(overdueLoans.data);
        
    } catch (error) {
        Utils.showError(error);
    }
}

function updateStats(books, readers, activeLoans, overdueLoans) {
    document.getElementById('totalBooks').textContent = books.length;
    document.getElementById('totalReaders').textContent = readers.length;
    document.getElementById('activeLoans').textContent = activeLoans.length;
    document.getElementById('overdueLoans').textContent = overdueLoans.length;
}

function renderPopularCategories(categories) {
    const container = document.getElementById('popularCategories');
    
    if (!categories || categories.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-center py-4">Немає даних</p>';
        return;
    }
    
    container.innerHTML = categories.map((category, index) => {
        const colors = [
            'from-blue-500 to-blue-600',
            'from-purple-500 to-purple-600',
            'from-pink-500 to-pink-600',
            'from-indigo-500 to-indigo-600',
            'from-cyan-500 to-cyan-600'
        ];
        
        return `
            <div class="flex items-center justify-between p-4 rounded-lg bg-gradient-to-r ${colors[index % colors.length]} text-white transition hover:shadow-lg">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center font-bold">
                        ${index + 1}
                    </div>
                    <div>
                        <p class="font-semibold">${category.name}</p>
                        <p class="text-sm opacity-80">${category.floor_location || 'Не вказано'}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold">${category.loans_count || 0}</p>
                    <p class="text-xs opacity-80">видач</p>
                </div>
            </div>
        `;
    }).join('');
}

function renderRecentLoans(loans) {
    const container = document.getElementById('recentLoans');
    
    if (!loans || loans.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-center py-4">Немає даних</p>';
        return;
    }
    
    container.innerHTML = loans.map(loan => {
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
        
        return `
            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-blue-300 transition">
                <div class="flex-1">
                    <p class="font-semibold text-gray-800">${loan.title}</p>
                    <p class="text-sm text-gray-600 mt-1">
                        <i class="fas fa-user mr-1"></i>${loan.reader_name}
                    </p>
                </div>
                <div class="text-right">
                    <span class="px-3 py-1 rounded-full text-xs font-medium ${statusColors[loan.status]}">
                        ${statusText[loan.status]}
                    </span>
                    <p class="text-xs text-gray-500 mt-1">${Utils.formatDate(loan.loan_date)}</p>
                </div>
            </div>
        `;
    }).join('');
}

function renderOverdueBooks(overdueLoans) {
    const container = document.getElementById('overdueBooks');
    
    if (!overdueLoans || overdueLoans.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-check-circle text-green-500 text-5xl mb-3"></i>
                <p class="text-gray-600">Немає прострочених книг!</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = `
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Книга</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Читач</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата видачі</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата повернення</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Прострочено</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                ${overdueLoans.map(loan => {
                    const returnDate = new Date(loan.return_date);
                    const today = new Date();
                    const daysOverdue = Math.floor((today - returnDate) / (1000 * 60 * 60 * 24));
                    
                    return `
                        <tr class="hover:bg-red-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">${loan.title}</div>
                                <div class="text-sm text-gray-500">${loan.card_number}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">${loan.reader_name}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                ${Utils.formatDate(loan.loan_date)}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                ${Utils.formatDate(loan.return_date)}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    ${daysOverdue} ${daysOverdue === 1 ? 'день' : daysOverdue < 5 ? 'дні' : 'днів'}
                                </span>
                            </td>
                        </tr>
                    `;
                }).join('')}
            </tbody>
        </table>
    `;
}
