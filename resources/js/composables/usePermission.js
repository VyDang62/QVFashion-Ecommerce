import { usePage } from "@inertiajs/vue3";
export function usePermission() {
    const page = usePage();
    
    // Kiểm tra User có quyền cụ thể hay không
    const can = (permission) => {
        return page.props.auth.permissions.includes(permission);
    };

    // Kiểm tra có bất kỳ quyền nào trong mảng (Dùng cho các nút gộp)
    const canAny = (permissions) => {
        return permissions.some(p => page.props.auth.permissions.includes(p));
    };

    return { can, canAny };
}