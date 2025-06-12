@extends('admin.layouts.app')

@section('title', 'Kelola Produk')
@section('page_title', 'Produk & Layanan')

@section('content')
    <style>
        .status-badge {
            display: inline-flex !important;
            visibility: visible !important;
        }
    </style>
    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Kelola Produk & Layanan</h1>
                <p class="text-gray-600 mt-1">Atur harga, status, dan informasi produk</p>
            </div>
            <a href="{{ route('admin.products.create') }}"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200">
                <i class="fas fa-plus mr-2"></i>Tambah Produk
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <i class="fas fa-box text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Produk</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $products->count() }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <i class="fas fa-eye text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Produk Aktif</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ $products->where('is_active', true)->count() }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <i class="fas fa-shopping-cart text-yellow-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Pesanan</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $products->sum('orders') }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <i class="fas fa-star text-purple-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Terlaris</p>
                        <p class="text-lg font-bold text-gray-900">
                            {{ $products->sortByDesc('orders')->first()->name ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Daftar Produk</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Produk</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Kategori</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pesanan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($products as $product)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8">
                                            <div
                                                class="h-8 w-8 rounded-full bg-{{ $product->color ?? 'blue' }}-100 flex items-center justify-center">
                                                <i
                                                    class="{{ $product->icon ?? 'fas fa-star' }} text-{{ $product->color ?? 'blue' }}-600 text-sm"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $product->name }}</div>
                                            @if ($product->description)
                                                <div class="text-sm text-gray-500 truncate max-w-xs">
                                                    {{ Str::limit($product->description, 50) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        @if ($product->category == 'Website') bg-blue-100 text-blue-800
                                        @elseif($product->category == 'Wedding') bg-pink-100 text-pink-800
                                        @elseif($product->category == 'Software') bg-green-100 text-green-800
                                        @elseif($product->category == 'Mobile') bg-purple-100 text-purple-800
                                        @elseif($product->category == 'Marketing') bg-orange-100 text-orange-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ $product->category }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        @if ($product->show_price && $product->price > 0)
                                            <span class="font-medium">{{ $product->price_label ?? 'Rp' }}
                                                {{ number_format($product->price, 0, ',', '.') }}</span>
                                            @if ($product->original_price && $product->original_price > $product->price)
                                                <br>
                                                <span class="text-xs text-gray-500 line-through">
                                                    {{ $product->price_label ?? 'Rp' }}
                                                    {{ number_format($product->original_price, 0, ',', '.') }}
                                                </span>
                                                <span class="text-xs text-green-600 ml-1">
                                                    (-{{ round((($product->original_price - $product->price) / $product->original_price) * 100) }}%)
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-gray-500 italic">Konsultasi</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ $product->orders ?? 0 }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-3">
                                        <a href="{{ route('admin.products.show', $product->id) }}"
                                            class="text-blue-600 hover:text-blue-900 transition-colors"
                                            title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.products.edit', $product->id) }}"
                                            class="text-indigo-600 hover:text-indigo-900 transition-colors" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button
                                            onclick="toggleStatus({{ $product->id }}, {{ $product->is_active ? 'true' : 'false' }})"
                                            class="{{ $product->is_active ? 'text-yellow-600 hover:text-yellow-900' : 'text-green-600 hover:text-green-900' }} transition-colors"
                                            title="{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                            <i class="fas fa-toggle-{{ $product->is_active ? 'on' : 'off' }}"></i>
                                        </button>
                                        <button onclick="deleteProduct({{ $product->id }}, '{{ $product->name }}')"
                                            class="text-red-600 hover:text-red-900 transition-colors" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="text-gray-500">
                                        <i class="fas fa-box-open text-4xl mb-4"></i>
                                        <p class="text-lg font-medium">Belum ada produk</p>
                                        <p class="text-sm">Tambahkan produk pertama Anda</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Update Harga Massal</h3>
                <div class="space-y-4">
                    <select id="bulk-category" class="w-full border border-gray-300 rounded-md px-3 py-2">
                        <option value="">Pilih Kategori</option>
                        @foreach ($products->pluck('category')->unique() as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>
                    <div class="flex space-x-2">
                        <input type="number" id="bulk-discount" placeholder="Diskon %" min="0" max="100"
                            class="flex-1 border border-gray-300 rounded-md px-3 py-2">
                        <button onclick="applyBulkDiscount()"
                            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            Terapkan
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Export Data</h3>
                <div class="space-y-2">
                    <button onclick="exportData('excel')"
                        class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition-colors">
                        <i class="fas fa-file-excel mr-2"></i>Export ke Excel
                    </button>
                    <button onclick="exportData('pdf')"
                        class="w-full bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700 transition-colors">
                        <i class="fas fa-file-pdf mr-2"></i>Export ke PDF
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Statistik Cepat</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Produk Terlaris:</span>
                        <span
                            class="font-medium text-right">{{ $products->sortByDesc('orders')->first()->name ?? 'Belum ada' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Rata-rata Harga:</span>
                        <span class="font-medium">
                            @php
                                $avgPrice = $products->where('price', '>', 0)->avg('price');
                            @endphp
                            {{ $avgPrice ? 'Rp ' . number_format($avgPrice, 0, ',', '.') : 'N/A' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Total Pendapatan:</span>
                        <span class="font-medium text-green-600">
                            @php
                                $totalRevenue = $products->sum(function ($product) {
                                    return ($product->price ?? 0) * ($product->orders ?? 0);
                                });
                            @endphp
                            Rp {{ number_format($totalRevenue, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Produk Featured:</span>
                        <span class="font-medium">{{ $products->where('is_featured', true)->count() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // console.log('Products page loaded');
        // // Log setiap kali ada perubahan DOM
        // const observer = new MutationObserver(() => {
        //     console.log('DOM changed');
        // });
        // observer.observe(document.body, {
        //     childList: true,
        //     subtree: true
        // });

        // CSRF Token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        function toggleStatus(id, currentStatus) {
            const action = currentStatus ? 'menonaktifkan' : 'mengaktifkan';

            if (confirm(`Yakin ingin ${action} produk ini?`)) {
                const button = event.target.closest('button');
                const originalHtml = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                button.disabled = true;

                fetch(`/admin/products/toggle-visibility`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            id: id
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update button dan status secara real-time
                            const statusSpan = button.closest('tr').querySelector('td:nth-child(5) span');
                            const icon = button.querySelector('i');

                            if (data.new_status) {
                                statusSpan.className =
                                    'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800';
                                statusSpan.textContent = 'Aktif';
                                icon.className = 'fas fa-toggle-on';
                                button.className = 'text-yellow-600 hover:text-yellow-900 transition-colors';
                                button.title = 'Nonaktifkan';
                            } else {
                                statusSpan.className =
                                    'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800';
                                statusSpan.textContent = 'Nonaktif';
                                icon.className = 'fas fa-toggle-off';
                                button.className = 'text-green-600 hover:text-green-900 transition-colors';
                                button.title = 'Aktifkan';
                            }

                            // Update onclick
                            button.setAttribute('onclick', `toggleStatus(${id}, ${data.new_status})`);

                            showNotification(data.message, 'success');
                        } else {
                            showNotification('Gagal mengubah status produk', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Terjadi kesalahan sistem', 'error');
                    })
                    .finally(() => {
                        button.innerHTML = originalHtml;
                        button.disabled = false;
                    });
            }
        }

        function deleteProduct(id, name) {
            if (confirm(`Yakin ingin menghapus produk "${name}"?\n\nTindakan ini tidak dapat dibatalkan.`)) {
                const button = event.target.closest('button');
                const row = button.closest('tr');

                fetch(`/admin/products/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            row.style.transition = 'opacity 0.3s ease';
                            row.style.opacity = '0';
                            setTimeout(() => {
                                row.remove();
                                updateStats();
                            }, 300);
                            showNotification(data.message, 'success');
                        } else {
                            showNotification('Gagal menghapus produk', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Terjadi kesalahan sistem', 'error');
                    });
            }
        }

        function applyBulkDiscount() {
            const category = document.getElementById('bulk-category').value;
            const discount = document.getElementById('bulk-discount').value;

            if (!category) {
                showNotification('Pilih kategori terlebih dahulu', 'warning');
                return;
            }

            if (!discount || discount < 0 || discount > 100) {
                showNotification('Masukkan persentase diskon yang valid (0-100)', 'warning');
                return;
            }

            if (confirm(`Terapkan diskon ${discount}% untuk semua produk kategori ${category}?`)) {
                // Implementation for bulk discount
                showNotification('Fitur akan segera tersedia', 'info');
            }
        }

        function exportData(format) {
            showNotification(`Export ${format.toUpperCase()} akan segera tersedia`, 'info');
        }

        function updateStats() {
            // Update stats setelah delete
            const totalProducts = document.querySelectorAll('tbody tr').length;
            document.querySelector('.grid .bg-white:first-child .text-2xl').textContent = totalProducts;
        }

        function showNotification(message, type = 'info') {
            // Simple notification
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                warning: 'bg-yellow-500',
                info: 'bg-blue-500'
            };

            const notification = document.createElement('div');
            notification.className =
                `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-opacity`;
            notification.textContent = message;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>
@endpush
