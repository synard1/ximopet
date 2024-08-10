<div wire:ignore.self class="modal fade" id="noFarm_modal" tabindex="-1" aria-labelledby="noFarm_modalLabel" aria-hidden="true">
	<div class="modal-dialog">
		  <div class="modal-content">
			<div class="modal-header">
			  <h1 class="modal-title fs-5" id="noFarm_modalLabel">Data Tidak Ditemuka</h1>
			  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
                <div class="alert alert-warning">{{ $noFarmMessage }}</div>
			</div>
			<div class="modal-footer">
			  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
			</div>
		  </div>
		</div>
	  </div>