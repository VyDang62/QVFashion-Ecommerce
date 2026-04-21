export function useProductHelpers(){
    const formatPrice = (price) => {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND',
        }).format(price);
    };

    const getTotalStock = (item) => {
        return item.variants?.reduce((total, v) => total + (Number(v.stock) || 0), 0) ?? 0;
    };

    return { formatPrice, getTotalStock };
}