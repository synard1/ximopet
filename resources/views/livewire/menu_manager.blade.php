<!--begin:Menu item-->
<div data-kt-menu-trigger="click" class="menu-item menu-accordion show">
	<!--begin:Menu link-->
	<span class="menu-link">
		<span class="menu-title">{{ trans('menu.menu_header',[],'id') }}</span>
		<span class="menu-arrow"></span>
	</span>
	<!--end:Menu link-->
	<!--begin:Menu sub-->
	<div class="menu-sub menu-sub-accordion menu-state-gray-900 menu-fit open">
		<!--begin:Menu item-->
		<div class="menu-item">
			<!--begin:Menu link-->
			<span class="menu-link" onclick="window.location.href='/master-data/farms'">
				<span class="menu-icon">
					<i class="ki-outline ki-home fs-4 text-success"></i>
				</span>
				<span class="menu-title">{{ trans('menu.menu_farm',[],'id') }}</span>
				<span class="menu-badge">
					<button class="btn btn-sm btn-icon btn-action">
						<i class="ki-outline ki-right fs-4"></i>
					</button>
				</span>
			</span>
			<!--end:Menu link-->
		</div>
		<!--end:Menu item-->

		<!--begin:Menu item-->
		<div class="menu-item">
			<!--begin:Menu link-->
			<span class="menu-link" onclick="window.location.href='/master-data/kandangs'">
				<span class="menu-icon">
					<i class="ki-outline ki-home-1 fs-4 text-info"></i>
				</span>
				<span class="menu-title">{{ trans('menu.menu_cage',[],'id') }}</span>
				<span class="menu-badge">
					<button class="btn btn-sm btn-icon btn-action">
						<i class="ki-outline ki-right fs-4"></i>
					</button>
				</span>
			</span>
			<!--end:Menu link-->
		</div>
		<!--end:Menu item-->

		<!--begin:Menu item-->
		<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ str_contains($currentUrl, '/ternak') ? 'show' : 'hide' }}">
			<!--begin:Menu link-->
			<span class="menu-link">
				<span class="menu-icon">
					<i class="ki-outline ki-cube-3 fs-4 text-danger"></i>
				</span>
				<span class="menu-title">{{ trans('menu.menu_ternak',[],'id') }}</span>
				<span class="menu-arrow"></span>
			</span>
			<!--end:Menu link-->
			<!--begin:Menu sub-->
			<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->is('ternak') ? 'active' : '' }}" href="/ternak">
							<span class="menu-bullet">
								<span class="bullet bullet-dot"></span>
							</span>
							<span class="menu-title">Overview</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->is('ternak/mati') ? 'active' : '' }}" href="/ternak/mati">
							<span class="menu-bullet">
								<span class="bullet bullet-dot"></span>
							</span>
							<span class="menu-title">Ternak Mati</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
					<!--begin:Menu item-->
					<div class="menu-item active">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->is('ternak/afkir') ? 'active' : '' }}" href="/ternak/afkir">
							<span class="menu-bullet">
								<span class="bullet bullet-dot"></span>
							</span>
							<span class="menu-title">Ternak Afkir</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->is('ternak/jual') ? 'active' : '' }}" href="/ternak/jual">
							<span class="menu-bullet">
								<span class="bullet bullet-dot"></span>
							</span>
							<span class="menu-title">Ternak Jual</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
			</div>
			<!--end:Menu sub-->
		</div>
		<!--end:Menu item-->

		<!--begin:Menu item-->
		<div class="menu-item">
			<!--begin:Menu link-->
			<span class="menu-link" onclick="window.location.href='/master-data/stoks'">
				<span class="menu-icon">
					<i class="ki-outline ki-cube-3 fs-4 text-warning"></i>
				</span>
				<span class="menu-title">{{ trans('menu.menu_stock',[],'id') }}</span>
				<span class="menu-badge">
					<button class="btn btn-sm btn-icon btn-action">
						<i class="ki-outline ki-right fs-4"></i>
					</button>
				</span>
			</span>
			<!--end:Menu link-->
		</div>
		<!--end:Menu item-->
	</div>
	<!--end:Menu sub-->
</div>
<!--end:Menu item-->