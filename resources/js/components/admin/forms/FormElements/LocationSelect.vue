<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import SingleSelect from './SingleSelect.vue';

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
        console.error("Admin Location Error:", err);
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
};
</script>

<template>
    <div class="space-y-4">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700">Tỉnh / Thành phố</label>
            <SingleSelect 
                v-model="provinceId" 
                :options="provinces" 
                optionLabel="ProvinceName" 
                optionValue="ProvinceID"
                placeholder="Chọn Tỉnh/Thành phố"
                :error="errors.province_id || errors.province"
                @update:modelValue="onProvinceChange" 
            />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Quận / Huyện</label>
                <SingleSelect 
                    v-model="districtId" 
                    :options="districts" 
                    optionLabel="DistrictName" 
                    optionValue="DistrictID"
                    :disabled="!districts.length"
                    placeholder="Chọn Quận/Huyện"
                    :error="errors.district_id || errors.district"
                    @update:modelValue="onDistrictChange" 
                />
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Phường / Xã</label>
                <SingleSelect 
                    v-model="wardCode" 
                    :options="wards" 
                    optionLabel="WardName" 
                    optionValue="WardCode"
                    :disabled="!wards.length"
                    placeholder="Chọn Phường/Xã"
                    :error="errors.ward_code || errors.ward"
                    @update:modelValue="onWardChange" 
                />
            </div>
        </div>
    </div>
</template>