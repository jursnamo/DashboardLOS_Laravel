<div class="modal fade import-progress-modal" id="importProgressModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body p-4 text-center">
                <div class="spinner-grow text-primary mb-3" role="status" aria-hidden="true"></div>
                <h5 class="fw-700 mb-2">Importing data to database</h5>
                <div id="importProgressText" class="text-muted mb-3">Import 0% dari 0 data...</div>
                <div class="progress">
                    <div id="importProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%">0%</div>
                </div>
                <div id="importProgressHint" class="small text-muted mt-3">Mohon tunggu, proses sedang berjalan.</div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade js-modal-settings modal-backdrop-transparent" id="modal-settings" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-right" role="document">
        <div class="modal-content h-100">
            <div class="dropdown-header bg-trans-gradient d-flex justify-content-center align-items-center rounded-top">
                <h4 class="m-0 text-center color-white">
                    Layout Settings
                    <small class="mb-0 opacity-80 d-block">User Interface Settings</small>
                </h4>
                <button type="button" class="close text-white position-absolute pos-top pos-right p-2 m-1 mr-2" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fal fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div class="custom-scroll" style="max-height: calc(100vh - 90px);">
                    <div class="p-3">
                        <h5 class="mt-0 mb-3">App Layout</h5>
                        <div class="d-flex justify-content-between align-items-center mb-3"><div><span class="onoffswitch-title">Fixed Header</span><div class="onoffswitch-title-desc">header is in a fixed at all times</div></div><a href="#" class="btn btn-switch" data-action="toggle" data-class="header-function-fixed"></a></div>
                        <div class="d-flex justify-content-between align-items-center mb-3"><div><span class="onoffswitch-title">Fixed Navigation</span><div class="onoffswitch-title-desc">left panel is fixed</div></div><a href="#" class="btn btn-switch" data-action="toggle" data-class="nav-function-fixed"></a></div>
                        <div class="d-flex justify-content-between align-items-center mb-3"><div><span class="onoffswitch-title">Minify Navigation</span><div class="onoffswitch-title-desc">Skew nav to maximize space</div></div><a href="#" class="btn btn-switch" data-action="toggle" data-class="nav-function-minify"></a></div>
                        <div class="d-flex justify-content-between align-items-center mb-3"><div><span class="onoffswitch-title">Hide Navigation</span><div class="onoffswitch-title-desc">roll mouse on edge to reveal</div></div><a href="#" class="btn btn-switch" data-action="toggle" data-class="nav-function-hidden"></a></div>
                        <div class="d-flex justify-content-between align-items-center mb-4"><div><span class="onoffswitch-title">Boxed Layout</span><div class="onoffswitch-title-desc">Encapsulates to a container</div></div><a href="#" class="btn btn-switch" data-action="toggle" data-class="mod-main-boxed"></a></div>

                        <h5 class="mt-3 mb-3">Mobile Menu</h5>
                        <div class="d-flex justify-content-between align-items-center mb-3"><div><span class="onoffswitch-title">Push Content</span><div class="onoffswitch-title-desc">Content pushed on menu reveal</div></div><a href="#" class="btn btn-switch" data-action="toggle" data-class="nav-mobile-push"></a></div>
                        <div class="d-flex justify-content-between align-items-center mb-4"><div><span class="onoffswitch-title">No Overlay</span><div class="onoffswitch-title-desc">Removes mesh on menu reveal</div></div><a href="#" class="btn btn-switch" data-action="toggle" data-class="mobile-nav-no-overlay"></a></div>

                        <h5 class="mt-3 mb-3">Accessibility</h5>
                        <div class="d-flex justify-content-between align-items-center mb-3"><div><span class="onoffswitch-title">Bigger Content Font</span><div class="onoffswitch-title-desc">content fonts are bigger for readability</div></div><a href="#" class="btn btn-switch" data-action="toggle" data-class="root-text"></a></div>
                        <div class="d-flex justify-content-between align-items-center mb-4"><div><span class="onoffswitch-title">High Contrast Text</span><div class="onoffswitch-title-desc">4.5:1 text contrast ratio</div></div><a href="#" class="btn btn-switch" data-action="toggle" data-class="mod-high-contrast"></a></div>

                        <h5 class="mt-3 mb-3">Global Modifications</h5>
                        <div class="d-flex justify-content-between align-items-center mb-3"><div><span class="onoffswitch-title">Clean Page Background</span><div class="onoffswitch-title-desc">adds more whitespace</div></div><a href="#" class="btn btn-switch" data-action="toggle" data-class="mod-clean-page-bg"></a></div>
                        <div class="d-flex justify-content-between align-items-center mb-3"><div><span class="onoffswitch-title">Disable CSS Animation</span><div class="onoffswitch-title-desc">Disables CSS based animations</div></div><a href="#" class="btn btn-switch" data-action="toggle" data-class="mod-disable-animation"></a></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-messenger" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-right" role="document">
        <div class="modal-content h-100">
            <div class="dropdown-header bg-trans-gradient d-flex justify-content-center align-items-center rounded-top">
                <h4 class="m-0 text-center color-white">Messenger</h4>
            </div>
            <div class="modal-body p-3">
                <p class="text-muted mb-0">Template messenger panel aktif. Konten bisa ditambah nanti.</p>
            </div>
        </div>
    </div>
</div>
