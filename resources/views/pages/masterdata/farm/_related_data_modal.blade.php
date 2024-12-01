<div class="modal fade" id="relatedDataModal" tabindex="-1" role="dialog" aria-labelledby="relatedDataModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="relatedDataModalLabel">Related Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Name</th>
                        </tr>
                    </thead>
                    <tbody id="relatedDataTableBody">
                        <!-- Data will be injected here by JavaScript -->
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="closeDetails()">Close</button>
            </div>
        </div>
    </div>
</div>