{{-- Change password modal (Bootstrap 5). Shown only when a user is logged in. --}}
<div class="modal fade kx-modal" id="userPasswordModal" tabindex="-1" aria-labelledby="userPasswordModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="login-top">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
                <span class="login-logo icon"><i class="bi bi-shield-lock-fill"></i></span>
                <h5 class="modal-title" id="userPasswordModalLabel">Change Password</h5>
                <p>Keep your account secure</p>
            </div>

            <form method="POST" action="{{ url('account/update/password') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="current_password" class="form-label fw-semibold">
                            Current Password <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input class="form-control" type="password" name="current_password"
                                id="current_password" placeholder="Current Password" autocomplete="current-password"
                                required>
                            <button class="btn btn-outline-secondary" type="button"
                                data-toggle-password="#current_password" aria-label="Show password" tabindex="-1">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label for="new_password1" class="form-label fw-semibold">
                            New Password <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input class="form-control" type="password" name="new_password1" id="new_password1"
                                placeholder="New Password" autocomplete="new-password" minlength="6" required>
                            <button class="btn btn-outline-secondary" type="button"
                                data-toggle-password="#new_password1" aria-label="Show password" tabindex="-1">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="new_password2" class="form-label fw-semibold">
                            Retype Password <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input class="form-control" type="password" name="new_password2" id="new_password2"
                                placeholder="Retype Password" autocomplete="new-password" minlength="6" required>
                            <button class="btn btn-outline-secondary" type="button"
                                data-toggle-password="#new_password2" aria-label="Show password" tabindex="-1">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg"></i> Close
                    </button>
                    <button type="submit" class="btn btn-kx px-4">
                        <i class="bi bi-save"></i> Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
