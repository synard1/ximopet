<!--begin::Modals-->
{{-- @include('partials/modals/_upgrade-plan')

@include('partials/modals/create-app/_main')

@include('partials/modals/create-campaign/_main')

@include('partials/modals/create-project/_main')

@include('partials/modals/_new-target')

@include('partials/modals/_view-users')

@include('partials/modals/users-search/_main')

@include('partials/modals/_invite-friends') --}}

{{-- @include('partials/master/_supplier') --}}
{{-- <livewire:master-data.supplier /> --}}


<livewire:.supplier-modal />
<livewire:master-data.supplier-modal />
<livewire:master-data.customer-modal />
<livewire:master-data.farm-modal />
<livewire:master-data.kandang-modal />
<livewire:master-data.stok-modal />
<livewire:transaksi.pembelian-d-o-c />
@include('pages.masterdata.farm._no_farm')
{{-- <livewire:master-data.kandang-modal :farms="$farms" /> --}}

<!--end::Modals-->
