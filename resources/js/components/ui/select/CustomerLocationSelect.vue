<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import CustomerSingleSelect from './CustomerSingleSelect.vue';

const props = defineProps({
    errors: { type: Object, default: () => ({}) }
});

const provinceId = defineModel('province_id');
const districtId = defineModel('district_id');
const wardCode = defineModel('ward_code');

const provinceName = defineModel('province');
const districtName = defineModel('district');
const wardName = defineModel('ward');

const provinces = ref([]);
const districts = ref([]);
const wards = ref([]);

const initialize = async () => {
    try {
        const resP = await axios.get('/api/ghn/provinces');
        provinces.value = resP.data.data;

        if (provinceId.value) {
            const resD = await axios.get(`/api/ghn/districts/${provinceId.value}`);
            districts.value = resD.data.data;

            if (districtId.value) {
                const resW = await axios.get(`/api/ghn/wards/${districtId.value}`);
                wards.value = resW.data.data;
            }
        }
    } catch (err) {
        console.error("Lỗi khởi tạo địa chỉ từ ID database:", err);
    }
};

onMounted(initialize);

const onProvinceChange = async (id) => {
    const p = provinces.value.find(i => i.ProvinceID == id);
    provinceName.value = p ? p.ProvinceName : '';
    
    districtId.value = null; districtName.value = '';
    wardCode.value = null; wardName.value = '';
    districts.value = []; wards.value = [];

    if (id) {
        const res = await axios.get(`/api/ghn/districts/${id}`);
        districts.value = res.data.data;
    }
};

const onDistrictChange = async (id) => {
    const d = districts.value.find(i => i.DistrictID == id);
    districtName.value = d ? d.DistrictName : '';

    wardCode.value = null; wardName.value = '';
    wards.value = [];

    if (id) {
        const res = await axios.get(`/api/ghn/wards/${id}`);
        wards.value = res.data.data;
    }
};

const onWardChange = (code) => {
    const w = wards.value.find(i => i.WardCode == code);
    wardName.value = w ? w.WardName : '';
    wardCode.value = code;
};
</script>

<template>
    <div class="grid grid-cols-1 gap-4">
        <div class="z-[30]">
            <label class="mb-1.5 block text-sm font-medium text-gray-700">Tỉnh / Thành phố</label>
            <CustomerSingleSelect 
                v-model="provinceId" 
                :options="provinces" 
                optionLabel="ProvinceName" 
                optionValue="ProvinceID"
                placeholder="Chọn Tỉnh/Thành..."
                :error="errors.province_id"
                @update:modelValue="onProvinceChange" 
            />
        </div>

        <div class="z-[20]">
            <label class="mb-1.5 block text-sm font-medium text-gray-700">Quận / Huyện</label>
            <CustomerSingleSelect 
                v-model="districtId" 
                :options="districts" 
                optionLabel="DistrictName" 
                optionValue="DistrictID"
                :disabled="!districts.length"
                placeholder="Chọn Quận/Huyện..."
                :error="errors.district_id"
                @update:modelValue="onDistrictChange" 
            />
        </div>

        <div class="z-[10]">
            <label class="mb-1.5 block text-sm font-medium text-gray-700">Phường / Xã</label>
            <CustomerSingleSelect 
                v-model="wardCode" 
                :options="wards" 
                optionLabel="WardName" 
                optionValue="WardCode"
                :disabled="!wards.length"
                placeholder="Chọn Phường/Xã..."
                :error="errors.ward_code"
                @update:modelValue="onWardChange" 
            />
        </div>
    </div>
</template>