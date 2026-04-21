export function useFormatter() {
    
    /**
     * Định dạng ngày tháng: DD/MM/YYYY
     * @param {string|Date} dateString 
     * @returns {string}
     */
    const formatDate = (dateString) => {
        if (!dateString) return '---';
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('vi-VN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
        }).format(date);
    };

    /**
     * Định dạng ngày giờ: HH:mm DD/MM/YYYY
     * @param {string|Date} dateString 
     * @returns {string}
     */
    const formatDateTime = (dateString) => {
        if (!dateString) return '---';
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('vi-VN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        }).format(date);
    };

    /**
     * Định dạng tiền tệ VND
     * @param {number} amount 
     * @returns {string}
     */
    const formatPrice = (amount) => {
        if (amount === undefined || amount === null) return '0 ₫';
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND',
            maximumFractionDigits: 0
        }).format(amount);
    };

    return {
        formatDate,
        formatDateTime,
        formatPrice
    };
}