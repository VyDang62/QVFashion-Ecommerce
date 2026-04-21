export function useOrderStatus() {
    const statusMap = {
        0: { label: 'Đã hủy', class: 'bg-red-100 text-red-600' },
        1: { label: 'Chờ xử lý (COD)', class: 'bg-orange-100 text-orange-600' },
        2: { label: 'Chờ thanh toán', class: 'bg-blue-100 text-blue-600' },
        3: { label: 'Đã thanh toán', class: 'bg-green-100 text-green-600' },
        4: { label: 'Đang giao hàng', class: 'bg-indigo-100 text-indigo-600' },
        5: { label: 'Hoàn thành', class: 'bg-teal-100 text-teal-600' },
    };

    const getStatus = (status) => {
        return statusMap[status] || { label: 'Không xác định', class: 'bg-gray-100 text-gray-600', icon: 'fa-question' };
    };

    return { getStatus };
}