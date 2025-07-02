<!--begin::Footer-->
<div id="kt_app_footer" class="app-footer">
    <!--begin::Footer container-->
    <div class="app-container container-fluid d-flex flex-column flex-md-row flex-center flex-md-stack py-3">
        <!--begin::Copyright-->
        <div class="text-gray-900 order-2 order-md-1">
            <span class="text-muted fw-semibold me-1">{{ date('Y') }}&copy;</span>
            <a href="#" target="_blank" class="text-gray-800 text-hover-primary">Ximopet</a>
            <span class="text-muted fw-semibold ms-1">{{ config('xolution.APPS.Version') ?? 'Unknown' }}</span>
            @if(app()->environment('local'))
            @php
            // Attempt to retrieve current Git branch and short commit hash. Fallback to "unknown" if command fails.
            $gitBranch = trim(exec('git rev-parse --abbrev-ref HEAD')) ?: 'unknown';
            $gitCommit = trim(exec('git rev-parse --short HEAD')) ?: 'unknown';
            @endphp
            <span class="text-muted fw-semibold ms-1">({{ $gitBranch }}:{{ $gitCommit }})</span>
            @endif
        </div>
        <!--end::Copyright-->
        <!--begin::Menu-->
        <ul class="menu menu-gray-600 menu-hover-primary fw-semibold order-1">
            {{-- <li class="menu-item">
                <a href="https://keenthemes.com" target="_blank" class="menu-link px-2">About</a>
            </li> --}}
            <li class="menu-item">
                <a href="https://wa.me/+6282243543715" target="_blank" class="menu-link px-2">Support</a>
            </li>
            <li class="menu-item">
                <a href="#" class="menu-link px-2" data-bs-toggle="modal" data-bs-target="#changelogModal">Changelog</a>
            </li>
        </ul>
        <!--end::Menu-->
    </div>
    <!--end::Footer container-->
</div>
<!--end::Footer-->

<!-- Changelog Modal -->
<div class="modal fade" id="changelogModal" tabindex="-1" aria-labelledby="changelogModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changelogModalLabel">Changelog</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @php
                $currentVersion = config('xolution.APPS.Version');
                $allVersions = config('version');
                $versionConfig = $allVersions[$currentVersion] ?? null;
                $changelog = $versionConfig['Changelog'] ?? null;

                // dump('Current Version:', $currentVersion);
                // dump('All Versions:', array_keys($allVersions));
                // dump('Version Config:', $versionConfig);
                // dump('Changelog:', $changelog);
                @endphp

                @if(empty($currentVersion))
                <p>Error: Current version is not set in the configuration.</p>
                @elseif(empty($allVersions))
                <p>Error: No versions found in the version configuration file.</p>
                @elseif(empty($versionConfig))
                <p>Error: Version configuration not found for {{ $currentVersion }}.</p>
                <p>Available versions: {{ implode(', ', array_keys($allVersions)) }}</p>
                @elseif(empty($changelog))
                <p>No changelog available for version {{ $currentVersion }}.</p>
                @else
                <h6>Version {{ $currentVersion }}</h6>
                @foreach(['Added', 'Changed', 'Fixed'] as $category)
                @if(isset($changelog[$category]) && count($changelog[$category]) > 0)
                <strong>{{ $category }}:</strong>
                <ul>
                    @foreach($changelog[$category] as $item)
                    <li>{{ $item }}</li>
                    @endforeach
                </ul>
                @endif
                @endforeach
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>