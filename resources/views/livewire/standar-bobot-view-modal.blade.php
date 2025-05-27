<div class="modal fade" id="standarBobotDetailModal" tabindex="-1" role="dialog"
    aria-labelledby="standarBobotDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="standarBobotDetailModalLabel">Detail Standar Bobot</h5>
            </div>
            <div class="modal-body">
                <p><strong>Strain:</strong> {{ $strain_name ?? '-' }}</p>
                <p><strong>Keterangan:</strong> {{ $description ?? '-' }}</p>
                <h6>Standards:</h6>
                @if (session()->has("message-data"))
                <div class="alert alert-success">
                    {{ session("message-data") }}
                </div>
                @endif
                <table id="standardsTable" class="display">
                    <thead>
                        <tr>
                            <th rowspan="2">Umur</th>
                            <th colspan="3">Bobot</th>
                            <th colspan="3">Feed Intake</th>
                            <th colspan="3">FCR</th>
                            {{-- <th rowspan="2">Action</th> <!-- Add Action column --> --}}
                        </tr>
                        <tr>
                            <th>Min</th>
                            <th>Max</th>
                            <th>Target</th>
                            <th>Min</th>
                            <th>Max</th>
                            <th>Target</th>
                            <th>Min</th>
                            <th>Max</th>
                            <th>Target</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($standards as $standard)
                        <tr>
                            <td>{{ $standard['umur'] }}</td>
                            <td>{{ $standard['standar_data']['bobot']['min'] }}</td>
                            <td>{{ $standard['standar_data']['bobot']['max'] }}</td>
                            <td>{{ $standard['standar_data']['bobot']['target'] }}</td>
                            <td>{{ $standard['standar_data']['feed_intake']['min'] }}</td>
                            <td>{{ $standard['standar_data']['feed_intake']['max'] }}</td>
                            <td>{{ $standard['standar_data']['feed_intake']['target'] }}</td>
                            <td>{{ $standard['standar_data']['fcr']['min'] }}</td>
                            <td>{{ $standard['standar_data']['fcr']['max'] }}</td>
                            <td>{{ $standard['standar_data']['fcr']['target'] }}</td>
                            {{-- <td>
                                <button type="button" class="btn btn-danger btn-sm" data-id="{{ $standard['umur'] }}"
                                    data-kt-action="delete_row_data">Delete</button>
                            </td> --}}
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" wire:click="$dispatch('closeModal')">Close</button>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        /* Center text in table cells */
        #standardsTable {
            width: 100%;
            /* Make the table full width */
            border-collapse: collapse;
            /* Collapse borders */
        }

        #standardsTable th,
        #standardsTable td {
            text-align: center;
            /* Center text */
            padding: 10px;
            /* Add padding */
            border: 1px solid #ddd;
            /* Add border */
        }

        #standardsTable th {
            background-color: #f2f2f2;
            /* Light gray background for headers */
            color: #333;
            /* Dark text color */
        }

        #standardsTable tr:nth-child(even) {
            background-color: #f9f9f9;
            /* Zebra striping for even rows */
        }

        #standardsTable tr:hover {
            background-color: #f1f1f1;
            /* Highlight row on hover */
        }

        /* Style for grouped headers */
        #standardsTable th[colspan] {
            background-color: #e0e0e0;
            /* Slightly darker for grouped headers */
        }
    </style>
    @endpush
    @push('scripts')
    <script>
        $(document).ready(function() {
        // Initialize DataTable when the modal is shown
        $('#standarBobotDetailModal').on('shown.bs.modal', function () {
            // Set focus to the first focusable element in the modal
            // $(this).find('button, [href], [tabindex]:not([tabindex="-1"]), input, select, textarea').first().focus();

            initializeDataTable();

            // Use event delegation to handle delete button clicks
            $('#standardsTable').on('click', '[data-kt-action="delete_row_data"]', function() {
                const standardId = $(this).data('id'); // Get the standard ID from the button
                const row = $(this).closest('tr'); // Get the closest row

                Swal.fire({
                    text: 'Are you sure you want to remove this standard?',
                    icon: 'warning',
                    buttonsStyling: false,
                    showCancelButton: true,
                    confirmButtonText: 'Yes, remove it!',
                    cancelButtonText: 'No, keep it',
                    customClass: {
                        confirmButton: 'btn btn-danger',
                        cancelButton: 'btn btn-secondary',
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Emit the delete event to Livewire
                        // Livewire.dispatch('deleteStandard', [standardId]);
                        initializeDataTable();
                    }
                });
            });
        });

        // Function to initialize DataTable
        function initializeDataTable() {
            $('#standardsTable').DataTable({
                destroy: true, // Allow reinitialization
                paging: true,
                searching: true,
                ordering: true,
                info: true,
                lengthChange: true,
                pageLength: 10, // Set default number of rows per page
                language: {
                    search: "Search:", // Customize search label
                    lengthMenu: "Show _MENU_ entries", // Customize length menu
                    info: "Showing _START_ to _END_ of _TOTAL_ entries", // Customize info text
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });
        }

        // Listen for the refreshDataTable event
        window.addEventListener('refreshDataTable', event => {
            initializeDataTable(); // Re-initialize the DataTable
            // Optionally, show a success message
            // Swal.fire({
            //     text: 'Standard deleted successfully.',
            //     icon: 'success',
            //     buttonsStyling: false,
            //     confirmButtonText: 'OK',
            //     customClass: {
            //         confirmButton: 'btn btn-primary',
            //     }
            // });
        });

        

    });
    </script>

    @endpush
</div>