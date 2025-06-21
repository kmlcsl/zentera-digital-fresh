@extends('admin.layouts.app')

@section('title', 'Kelola Pesanan Dokumen')
@section('page_title', 'Manajemen Pesanan Dokumen')

@section('content')
    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Manajemen Pesanan Dokumen</h1>
                <p class="text-gray-600 mt-1">Monitor dan kelola semua pesanan customer untuk layanan dokumen</p>
            </div>
            <div class="flex space-x-2">
                <button onclick="exportOrders()"
                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors duration-200">
                    <i class="fas fa-download mr-2"></i>Export
                </button>
                <a href="{{ route('admin.orders.create') }}"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200 inline-flex items-center">
                    <i class="fas fa-plus mr-2"></i>Pesanan Baru
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="bg-white rounded-lg shadow p-6 flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <i class="fas fa-file-alt text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-xs sm:text-sm font-medium text-gray-600">Total Pesanan</p>
                    <p class="text-lg sm:text-2xl font-bold text-gray-900">{{ $stats['total_orders'] }}</p>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6 flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <i class="fas fa-clock text-yellow-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-xs sm:text-sm font-medium text-gray-600">Pending</p>
                    <p class="text-lg sm:text-2xl font-bold text-gray-900">{{ $stats['pending_orders'] }}</p>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6 flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <i class="fas fa-credit-card text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-xs sm:text-sm font-medium text-gray-600">Sudah Bayar</p>
                    <p class="text-lg sm:text-2xl font-bold text-gray-900">{{ $stats['paid_orders'] }}</p>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6 flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <i class="fas fa-check text-green-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-xs sm:text-sm font-medium text-gray-600">Selesai</p>
                    <p class="text-lg sm:text-2xl font-bold text-gray-900">{{ $stats['completed_orders'] }}</p>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6 flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <i class="fas fa-money-bill-wave text-purple-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-xs sm:text-sm font-medium text-gray-600">Total Revenue</p>
                    <p class="text-lg sm:text-2xl font-bold text-gray-900">Rp
                        {{ number_format($stats['total_revenue'] ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <!-- Search & Filters -->
        <div class="bg-white rounded-lg shadow p-6">
            <form method="GET" action="{{ route('admin.orders.filter') }}"
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cari Pesanan</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="w-full border border-gray-300 rounded-md px-3 py-2" placeholder="Nama, No. Order, HP...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status Pembayaran</label>
                    <select name="payment_status" class="w-full border border-gray-300 rounded-md px-3 py-2">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>Pending
                        </option>
                        <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Sudah Bayar
                        </option>
                        <option value="completed" {{ request('payment_status') == 'completed' ? 'selected' : '' }}>Selesai
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Layanan</label>
                    <select name="service_type" class="w-full border border-gray-300 rounded-md px-3 py-2">
                        <option value="">Semua Layanan</option>
                        <option value="repair" {{ request('service_type') == 'repair' ? 'selected' : '' }}>Perbaikan
                            Dokumen</option>
                        <option value="plagiarism" {{ request('service_type') == 'plagiarism' ? 'selected' : '' }}>Cek
                            Plagiarisme</option>
                        <option value="format" {{ request('service_type') == 'format' ? 'selected' : '' }}>Format & Daftar
                            Isi</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                        class="w-full border border-gray-300 rounded-md px-3 py-2">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 w-full">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Daftar Pesanan Dokumen</h2>
            </div>
            <div class="overflow-x-auto">
                <div class="inline-block min-w-full px-4 sm:px-0">
                    <table class="min-w-full divide-y divide-gray-200 table-auto">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Order</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Layanan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Harga</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Dokumen</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Bukti Transfer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($orders as $order)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">#{{ $order->order_number }}</div>
                                        <div class="text-xs text-gray-500">{{ $order->created_at->format('d M Y, H:i') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $order->customer_name }}</div>
                                        <div class="text-xs text-gray-500">{{ $order->customer_phone }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <i class="{{ $order->service_icon }} mr-2 text-gray-400"></i>
                                            <div>
                                                <div class="text-sm text-gray-900">{{ $order->service_name }}</div>
                                                <div class="text-xs text-gray-500">{{ ucfirst($order->service_type) }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $order->formatted_price }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div id="status-{{ $order->id }}">
                                            {!! $order->status_badge !!}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($order->document_path)
                                            <div class="flex space-x-2">
                                                @if ($order->is_google_drive && $order->google_drive_file_id)
                                                    <!-- GOOGLE DRIVE FILES -->
                                                    <a href="{{ $order->google_drive_view_url }}" target="_blank"
                                                        class="text-blue-600 hover:text-blue-800 text-sm">
                                                        <i class="fab fa-google-drive mr-1"></i>Drive
                                                    </a>
                                                    <button
                                                        onclick="previewFile('{{ $order->google_drive_view_url }}', 'document', true, '{{ $order->google_drive_file_id }}')"
                                                        class="text-green-600 hover:text-green-800 text-sm">
                                                        <i class="fas fa-eye mr-1"></i>Preview
                                                    </button>
                                                @else
                                                    <!-- LOCAL FILES -->
                                                    <a href="{{ Storage::url($order->document_path) }}" target="_blank"
                                                        class="text-blue-600 hover:text-blue-800 text-sm">
                                                        <i class="fas fa-file-download mr-1"></i>Download
                                                    </a>
                                                    <button
                                                        onclick="previewFile('{{ Storage::url($order->document_path) }}', 'document', false)"
                                                        class="text-green-600 hover:text-green-800 text-sm">
                                                        <i class="fas fa-eye mr-1"></i>Preview
                                                    </button>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-gray-400 text-sm">No file</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($order->payment_proof || $order->payment_proof_google_drive_file_id)
                                            <div class="flex space-x-2">
                                                @if ($order->payment_proof_is_google_drive && $order->payment_proof_google_drive_file_id)
                                                    <!-- GOOGLE DRIVE PAYMENT PROOF -->
                                                    <a href="{{ $order->payment_proof_google_drive_view_url }}"
                                                        target="_blank"
                                                        class="text-green-600 hover:text-green-800 text-sm">
                                                        <i class="fab fa-google-drive mr-1"></i>Drive
                                                    </a>
                                                    <button
                                                        onclick="previewFile('{{ $order->payment_proof_google_drive_view_url }}', 'payment', true, '{{ $order->payment_proof_google_drive_file_id }}')"
                                                        class="text-blue-600 hover:text-blue-800 text-sm">
                                                        <i class="fas fa-search-plus mr-1"></i>Zoom
                                                    </button>
                                                @else
                                                    <!-- LOCAL PAYMENT PROOF -->
                                                    <a href="{{ Storage::url($order->payment_proof) }}" target="_blank"
                                                        class="text-green-600 hover:text-green-800 text-sm">
                                                        <i class="fas fa-image mr-1"></i>Lihat
                                                    </a>
                                                    <button
                                                        onclick="previewFile('{{ Storage::url($order->payment_proof) }}', 'payment', false)"
                                                        class="text-blue-600 hover:text-blue-800 text-sm">
                                                        <i class="fas fa-search-plus mr-1"></i>Zoom
                                                    </button>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-gray-400 text-sm">Belum upload</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <button onclick="viewOrderDetails({{ $order->id }})"
                                                class="text-indigo-600 hover:text-indigo-900" title="Detail Order">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button onclick="updatePaymentStatus({{ $order->id }})"
                                                class="text-green-600 hover:text-green-900" title="Update Status">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="https://wa.me/{{ preg_replace('/^0/', '62', $order->customer_phone) }}?text={{ urlencode('Halo ' . $order->customer_name . ', terkait order #' . $order->order_number . ' untuk layanan ' . $order->service_name) }}"
                                                target="_blank" class="text-green-600 hover:text-green-900"
                                                title="WhatsApp">
                                                <i class="fab fa-whatsapp"></i>
                                            </a>
                                            <button onclick="deleteOrder({{ $order->id }})"
                                                class="text-red-600 hover:text-red-900" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center">
                                        <div class="text-gray-500">
                                            <i class="fas fa-inbox text-4xl mb-4"></i>
                                            <p class="text-lg font-medium">Belum ada pesanan</p>
                                            <p class="text-sm">Pesanan akan muncul di sini setelah customer upload dokumen
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            @if ($orders->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $orders->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Detail Order -->
    <div id="orderDetailModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex justify-center items-center h-full p-4">
            <div class="bg-white rounded-lg max-w-2xl w-full max-h-full overflow-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-gray-900">Detail Pesanan</h3>
                        <button onclick="closeModal('orderDetailModal')" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <div id="orderDetailContent">
                        <!-- Content akan diisi via JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Update Payment Status -->
    <div id="updateStatusModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex justify-center items-center h-full p-4">
            <div class="bg-white rounded-lg max-w-md w-full">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-gray-900">Update Status Pembayaran</h3>
                        <button onclick="closeModal('updateStatusModal')" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <form id="updateStatusForm">
                        <input type="hidden" id="updateOrderId">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status Pembayaran</label>
                            <select id="paymentStatus"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="pending">Pending</option>
                                <option value="paid">Sudah Bayar</option>
                                <option value="completed">Selesai</option>
                            </select>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="closeModal('updateStatusModal')"
                                class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">
                                Batal
                            </button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Update Status
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal File Preview -->
    <div id="filePreviewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex justify-center items-center h-full p-4">
            <div class="bg-white rounded-lg max-w-4xl w-full max-h-full overflow-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-gray-900">Preview File</h3>
                        <button onclick="closeModal('filePreviewModal')" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <div id="filePreviewContent" class="text-center">
                        <!-- Content akan diisi via JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* Minimal CSS - hanya yang benar-benar diperlukan */
        [id^="status-"] {
            min-height: 24px;
        }
    </style>
@endpush

@push('scripts')
    <script>
        // Global variables
        let currentOrderId = null;

        // View Order Details
        function viewOrderDetails(orderId) {
            fetch(`/admin/orders/${orderId}/details`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const order = data.order;

                        // Storage info display
                        const storageInfo = order.is_google_drive ?
                            '<span class="text-blue-600"><i class="fab fa-google-drive mr-1"></i>Google Drive</span>' :
                            '<span class="text-gray-600"><i class="fas fa-server mr-1"></i>Local Server</span>';

                        document.getElementById('orderDetailContent').innerHTML = `
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Informasi Order</h4>
                            <div class="space-y-2 text-sm">
                                <div><span class="font-medium">Order ID:</span> #${order.order_number}</div>
                                <div><span class="font-medium">Tanggal:</span> ${order.created_at}</div>
                                <div><span class="font-medium">Layanan:</span> ${order.service_name}</div>
                                <div><span class="font-medium">Harga:</span> ${order.price}</div>
                                <div><span class="font-medium">Status:</span>
                                    <span class="px-2 py-1 rounded-full text-xs ${getStatusClass(order.payment_status)}">
                                        ${getStatusText(order.payment_status)}
                                    </span>
                                </div>
                                <div><span class="font-medium">Storage:</span> ${storageInfo}</div>
                            </div>
                        </div>

                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Informasi Customer</h4>
                            <div class="space-y-2 text-sm">
                                <div><span class="font-medium">Nama:</span> ${order.customer_name}</div>
                                <div><span class="font-medium">HP:</span> ${order.customer_phone}</div>
                                <div><span class="font-medium">Bayar:</span> ${order.paid_at || 'Belum bayar'}</div>
                            </div>
                        </div>
                    </div>

                    ${order.notes ? `
                                                <div>
                                                    <h4 class="font-semibold text-gray-800 mb-2">Catatan</h4>
                                                    <div class="bg-gray-50 p-3 rounded-md text-sm">
                                                        ${order.notes}
                                                    </div>
                                                </div>
                                            ` : ''}

                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center">
                            <h4 class="font-semibold text-gray-800 mb-2">Dokumen</h4>
                            ${order.document_path ? `
                                                        <div class="space-y-2">
                                                            <a href="${order.document_path}" target="_blank"
                                                               class="bg-blue-100 text-blue-600 px-4 py-2 rounded-md hover:bg-blue-200 inline-block">
                                                                <i class="${order.is_google_drive ? 'fab fa-google-drive' : 'fas fa-download'} mr-2"></i>
                                                                ${order.is_google_drive ? 'Buka di Google Drive' : 'Download File'}
                                                            </a>
                                                            <br>
                                                            <button onclick="previewFile('${order.document_path}', 'document', ${order.is_google_drive}, '${order.google_drive_file_id || ''}')"
                                                                    class="bg-green-100 text-green-600 px-4 py-2 rounded-md hover:bg-green-200">
                                                                <i class="fas fa-eye mr-2"></i>Preview
                                                            </button>
                                                        </div>
                                                    ` : '<span class="text-gray-400">Tidak ada file</span>'}
                        </div>

                        <div class="text-center">
                            <h4 class="font-semibold text-gray-800 mb-2">Bukti Transfer</h4>
                            ${order.payment_proof ? `
                                                        <a href="${order.payment_proof}" target="_blank"
                                                           class="bg-green-100 text-green-600 px-4 py-2 rounded-md hover:bg-green-200 inline-block">
                                                            <i class="fas fa-image mr-2"></i>Lihat Bukti
                                                        </a>
                                                    ` : '<span class="text-gray-400">Belum upload</span>'}
                        </div>
                    </div>
                </div>
            `;

                        document.getElementById('orderDetailModal').classList.remove('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Gagal memuat detail order');
                });
        }

        // Update Payment Status
        function updatePaymentStatus(orderId) {
            currentOrderId = orderId;
            document.getElementById('updateOrderId').value = orderId;
            document.getElementById('updateStatusModal').classList.remove('hidden');
        }

        // Simple status badge generator
        function generateStatusBadge(status) {
            const badges = {
                'pending': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Menunggu Pembayaran</span>',
                'paid': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Sedang Diproses</span>',
                'completed': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Selesai</span>'
            };

            return badges[status] ||
                '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Unknown</span>';
        }

        // Simple status updater
        function updateStatus(orderId, newStatus) {
            const statusElement = document.getElementById(`status-${orderId}`);
            if (statusElement) {
                statusElement.innerHTML = generateStatusBadge(newStatus);
            }
        }

        // Handle Update Status Form - CLEAN VERSION
        document.getElementById('updateStatusForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const orderId = document.getElementById('updateOrderId').value;
            const paymentStatus = document.getElementById('paymentStatus').value;

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...';

            fetch(`/admin/orders/${orderId}/update-payment-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    },
                    body: JSON.stringify({
                        payment_status: paymentStatus
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Simple update - no complex protection needed since app.blade.php is fixed
                        updateStatus(orderId, paymentStatus);

                        closeModal('updateStatusModal');
                        showNotification('Status berhasil diupdate!', 'success');
                    } else {
                        throw new Error(data.error || 'Update failed');
                    }

                    // Reset form
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert(`Terjadi kesalahan: ${error.message}`);

                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
        });

        // Preview File
        function previewFile(fileUrl, type, isGoogleDrive = false, googleDriveFileId = null) {
            const content = document.getElementById('filePreviewContent');

            // GOOGLE DRIVE FILES (Documents or Payment Proofs)
            if (isGoogleDrive && googleDriveFileId) {
                const googlePreviewUrl = `https://drive.google.com/file/d/${googleDriveFileId}/preview`;
                const googleViewUrl = `https://drive.google.com/file/d/${googleDriveFileId}/view`;
                const googleDownloadUrl = `https://drive.google.com/uc?export=download&id=${googleDriveFileId}`;

                // Determine file type display
                let fileTypeInfo = '';
                if (type === 'payment') {
                    fileTypeInfo = '<p class="text-sm text-gray-600">Bukti Transfer disimpan di Google Drive</p>';
                } else {
                    fileTypeInfo = '<p class="text-sm text-gray-600">Dokumen disimpan di Google Drive</p>';
                }

                content.innerHTML = `
        <div class="text-center">
            <div class="mb-4">
                <div class="flex items-center justify-center mb-2">
                    <i class="fab fa-google-drive text-2xl text-blue-600 mr-2"></i>
                    ${fileTypeInfo}
                </div>

                <div class="border rounded-md bg-white p-2 shadow-sm">
                    <iframe src="${googlePreviewUrl}"
                            width="100%" height="450px"
                            class="border-0 rounded"
                            onerror="showGoogleDriveError('${googleViewUrl}', '${googleDownloadUrl}', '${type}');">
                    </iframe>
                </div>
            </div>

            <div class="flex justify-center space-x-2 mt-4">
                <a href="${googleViewUrl}" target="_blank"
                   class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 flex items-center">
                    <i class="fab fa-google-drive mr-2"></i>Buka di Google Drive
                </a>
                <a href="${googleDownloadUrl}" target="_blank"
                   class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 flex items-center">
                    <i class="fas fa-download mr-2"></i>Download
                </a>
            </div>

            <div class="mt-3 text-xs text-gray-500">
                <p>📁 File ID: ${googleDriveFileId}</p>
                <p>☁️ Disimpan aman di Google Drive (${type === 'payment' ? 'Payment Proofs' : 'Documents'})</p>
            </div>
        </div>
    `;
            }
            // PAYMENT IMAGES (Local or Google Drive images)
            else if (type === 'payment') {
                content.innerHTML = `
        <div class="text-center">
            <div class="mb-4">
                <div class="flex items-center justify-center mb-2">
                    <i class="fas fa-receipt text-2xl text-green-600 mr-2"></i>
                    <p class="text-sm text-gray-600">Bukti Transfer</p>
                </div>
                <img src="${fileUrl}" alt="Bukti Pembayaran"
                     class="max-w-full max-h-96 mx-auto rounded-md shadow-lg"
                     onerror="showImageError('${fileUrl}');">
            </div>
            <div class="mt-4">
                <a href="${fileUrl}" target="_blank"
                   class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 mr-2">
                    <i class="fas fa-external-link-alt mr-2"></i>Buka di Tab Baru
                </a>
                <a href="${fileUrl}" download
                   class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                    <i class="fas fa-download mr-2"></i>Download
                </a>
            </div>
        </div>
    `;
            }
            // FALLBACK untuk file lainnya
            else {
                const extension = fileUrl.split('.').pop().toLowerCase();
                content.innerHTML = `
        <div class="text-center py-8">
            <i class="fas fa-file-alt text-6xl text-gray-400 mb-4"></i>
            <p class="text-gray-700 text-lg font-medium mb-2">File .${extension.toUpperCase()}</p>
            <p class="text-gray-600 mb-4">Preview menggunakan browser default</p>
            <div class="space-x-2">
                <a href="${fileUrl}" target="_blank"
                   class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    <i class="fas fa-external-link-alt mr-2"></i>Buka di Tab Baru
                </a>
                <a href="${fileUrl}" download
                   class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                    <i class="fas fa-download mr-2"></i>Download
                </a>
            </div>
        </div>
    `;
            }

            document.getElementById('filePreviewModal').classList.remove('hidden');
        }

        // Updated Google Drive error handler
        function showGoogleDriveError(viewUrl, downloadUrl, fileType = 'document') {
            const content = document.getElementById('filePreviewContent');
            const typeDisplay = fileType === 'payment' ? 'Bukti Transfer' : 'Dokumen';

            content.innerHTML = `
    <div class="text-center py-8">
        <i class="fab fa-google-drive text-6xl text-blue-400 mb-4"></i>
        <p class="text-gray-700 text-lg font-medium mb-2">Google Drive Preview</p>
        <p class="text-gray-600 mb-4">Preview tidak dapat dimuat, silakan buka langsung</p>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4 max-w-md mx-auto">
            <p class="text-blue-800 text-sm">
                <i class="fas fa-info-circle mr-2"></i>
                ${typeDisplay} tersimpan aman di Google Drive dan dapat diakses kapan saja
            </p>
        </div>
        <div class="space-x-2">
            <a href="${viewUrl}" target="_blank"
               class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                <i class="fab fa-google-drive mr-2"></i>Buka di Google Drive
            </a>
            <a href="${downloadUrl}" target="_blank"
               class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                <i class="fas fa-download mr-2"></i>Download
            </a>
        </div>
    </div>
`;
        }

        function showImageError(fileUrl) {
            const content = document.getElementById('filePreviewContent');
            content.innerHTML = `
    <div class="text-center py-8">
        <i class="fas fa-image text-6xl text-gray-400 mb-4"></i>
        <p class="text-gray-600 mb-4">Gagal memuat gambar</p>
        <div class="space-x-2">
            <a href="${fileUrl}" target="_blank"
               class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                <i class="fas fa-external-link-alt mr-2"></i>Buka di Tab Baru
            </a>
            <a href="${fileUrl}" download
               class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                <i class="fas fa-download mr-2"></i>Download
            </a>
        </div>
    </div>
`;
        }

        // Delete Order
        function deleteOrder(orderId) {
            if (confirm('Apakah Anda yakin ingin menghapus pesanan ini?')) {
                fetch(`/admin/orders/${orderId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Pesanan berhasil dihapus', 'success');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            alert('Gagal menghapus pesanan: ' + data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat menghapus pesanan');
                    });
            }
        }

        // Export Orders
        function exportOrders() {
            const params = new URLSearchParams(window.location.search);
            window.open(`/admin/orders/export?${params.toString()}`, '_blank');
        }

        // Close Modal
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        // Helper Functions
        function getStatusClass(status) {
            const classes = {
                'pending': 'bg-yellow-100 text-yellow-800',
                'paid': 'bg-blue-100 text-blue-800',
                'completed': 'bg-green-100 text-green-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        }

        function getStatusText(status) {
            const texts = {
                'pending': 'Menunggu Pembayaran',
                'paid': 'Sedang Diproses',
                'completed': 'Selesai'
            };
            return texts[status] || 'Unknown';
        }

        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className =
                `notification-toast fixed top-4 right-4 z-50 px-4 py-2 rounded-md text-white ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
            notification.textContent = message;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Modal event handlers
        document.addEventListener('click', function(e) {
            const modals = ['orderDetailModal', 'updateStatusModal', 'filePreviewModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (e.target === modal) {
                    closeModal(modalId);
                }
            });
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modals = ['orderDetailModal', 'updateStatusModal', 'filePreviewModal'];
                modals.forEach(modalId => {
                    closeModal(modalId);
                });
            }
        });
    </script>
@endpush
