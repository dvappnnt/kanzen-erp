<script setup>
import { computed, onMounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import VueBarcode from "@chenfengyuan/vue-barcode";

const page = usePage();
const modelData = computed(() => page.props.modelData || {});

onMounted(() => {
    window.print();
});
</script>

<template>
    <div class="p-10 font-sans text-sm text-gray-800">
        <!-- Header omitted for brevity -->

        <!-- Serial Numbers by Variation -->
        <div class="space-y-8">
            <template v-for="detail in modelData.details" :key="detail.id">
                <div v-if="detail.serials?.length">
                    <h3 class="text-base font-semibold">
                        {{
                            detail.purchase_order_detail
                                ?.supplier_product_detail?.product_variation
                                ?.product?.name || "N/A"
                        }}
                        -
                        {{
                            detail.purchase_order_detail
                                ?.supplier_product_detail?.product_variation
                                ?.name || "Unknown"
                        }}
                    </h3>

                    <div class="grid grid-cols-1 gap-4 mt-2">
                        <div
                            v-for="serial in detail.serials"
                            :key="serial.id || serial.serial_number"
                            class="border p-3 rounded text-center shadow-sm"
                        >
                            <vue-barcode
                                :value="serial.serial_number"
                                format="CODE128"
                                :options="{
                                    displayValue: true,
                                    fontSize: 12,
                                    height: 40,
                                    width: 2,
                                }"
                            />
                            <p class="mt-2 text-xs">
                                {{ serial.serial_number }}
                            </p>
                        </div>
                    </div>
                </div>
            </template>
            <div
                v-if="!modelData.details?.some((d) => d.serials?.length)"
                class="text-center text-gray-500 mt-4"
            >
                No serial numbers found.
            </div>
        </div>

        <!-- Footer omitted for brevity -->
    </div>
</template>

<style scoped>
@media print {
    body {
        margin: 0;
        padding: 0;
        font-size: 12px;
    }
    .grid {
        break-inside: avoid;
        page‑break‑inside: avoid;
    }
}
</style>
