{{--
Livestock Data Integrity Check Page

Admin page untuk melakukan pengecekan dan perbaikan integritas data livestock
termasuk relasi dengan CurrentLivestock, LivestockBatch, dan data terkait.

@version 2.0.0
@since 2025-01-19
@author System
--}}

<x-default-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Livestock Data Integrity Check
        </h2>
        <p class="text-sm text-gray-600 mt-1">
            Mengecek dan memperbaiki masalah integritas data pada sistem livestock
        </p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Main integrity check component --}}
            <livewire:data-integrity.livestock-data-integrity />

            {{-- Information panel --}}
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-lg font-semibold text-blue-800 mb-2">Informasi Penting</h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• Selalu backup database sebelum menjalankan perbaikan massal</li>
                    <li>• Preview perubahan terlebih dahulu sebelum apply</li>
                    <li>• Monitor log system untuk memastikan proses berjalan dengan baik</li>
                    <li>• Hubungi administrator jika menemukan error yang tidak dapat diperbaiki</li>
                </ul>
            </div>
        </div>
    </div>
</x-default-layout>