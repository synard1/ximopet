<div class="modal fade" id="standarBobotDetailModal" tabindex="-1" role="dialog" aria-labelledby="standarBobotDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="standarBobotDetailModalLabel">Detail Standar Bobot</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><strong>Strain:</strong> {{ $strain ?? '-' }}</p>
                <p><strong>Keterangan:</strong> {{ $keterangan ?? '-' }}</p>
                <h6>Standards:</h6>
                <ul>
                    @isset($standards)
                        <ul>
                            @foreach($standards as $standard)
                                <li>
                                    Umur: {{ $standard['umur'] }}<br>
                                    Bobot Min: {{ $standard['standar_data']['bobot']['min'] }}<br>
                                    Bobot Max: {{ $standard['standar_data']['bobot']['max'] }}<br>
                                    Target: {{ $standard['standar_data']['bobot']['target'] }}<br>
                                    Feed Intake Min: {{ $standard['standar_data']['feed_intake']['min'] }}<br>
                                    Feed Intake Max: {{ $standard['standar_data']['feed_intake']['max'] }}<br>
                                    FCR Min: {{ $standard['standar_data']['fcr']['min'] }}<br>
                                    FCR Max: {{ $standard['standar_data']['fcr']['max'] }}<br>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p>Tidak ada data standar bobot.</p>
                    @endisset

                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>