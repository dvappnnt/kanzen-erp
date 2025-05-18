<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import { formatNumber } from "@/utils/global";

const page = usePage();
const journal = computed(() => page.props.journal);
const details = computed(() => page.props.journal.details || []);

const headerActions = [
  {
    text: "Download PDF",
    url: `/journal-entries/${journal.value.id}/export-pdf`,
    inertia: false,
    class: "bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm",
  },
  {
    text: "Back",
    url: `/journal-entries`,
    inertia: true,
    class: "border border-gray-400 hover:bg-gray-100 px-4 py-2 rounded text-gray-600",
  },
];
</script>

<template>
  <AppLayout :title="`Export Journal Entry ${journal.reference_number}`">
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Export Journal Entry
        </h2>
        <HeaderActions :actions="headerActions" />
      </div>
    </template>

    <div class="max-w-6xl mx-auto mt-6 p-6 bg-white shadow rounded">
      <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
        <div>
          <strong>Reference Number:</strong> {{ journal.reference_number }}
        </div>
        <div>
          <strong>Date:</strong> {{ journal.reference_date }}
        </div>
        <div>
          <strong>Company:</strong> {{ journal.company?.name || '—' }}
        </div>
        <div>
          <strong>Remarks:</strong> {{ journal.remarks || '—' }}
        </div>
      </div>

      <table class="min-w-full text-sm border border-gray-200">
        <thead class="bg-gray-100">
          <tr>
            <th class="px-3 py-2 border">Account</th>
            <th class="px-3 py-2 border">Particulars</th>
            <th class="px-3 py-2 border text-right">Debit</th>
            <th class="px-3 py-2 border text-right">Credit</th>
            <th class="px-3 py-2 border">Remarks</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="detail in details"
            :key="detail.id"
            class="border-t hover:bg-gray-50"
          >
            <td class="px-3 py-2 border">
              {{ detail.account?.name || '—' }}
            </td>
            <td class="px-3 py-2 border">
              {{ detail.name || '—' }}
            </td>
            <td class="px-3 py-2 border text-right">
              {{ formatNumber(detail.debit) }}
            </td>
            <td class="px-3 py-2 border text-right">
              {{ formatNumber(detail.credit) }}
            </td>
            <td class="px-3 py-2 border">
              {{ detail.remarks || '—' }}
            </td>
          </tr>
        </tbody>
        <tfoot class="bg-gray-50 font-semibold">
          <tr>
            <td colspan="2" class="px-3 py-2 text-right">Totals:</td>
            <td class="px-3 py-2 text-right">
              {{ formatNumber(journal.total_debit) }}
            </td>
            <td class="px-3 py-2 text-right">
              {{ formatNumber(journal.total_credit) }}
            </td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </AppLayout>
</template>
