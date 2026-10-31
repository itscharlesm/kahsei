<div class="modal fade" id="userPasswordModal" tabindex="-1" aria-labelledby="userPasswordModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="userPasswordModalLabel">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ url('account/update/password') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="current_password">Current Password <span class="text-danger">*</span></label>
                        <input class="form-control" type="password" name="current_password" id="current_password"
                            placeholder="Current Password" required />
                    </div>
                    <hr />
                    <div class="mb-3">
                        <label for="new_password1">New Password <span class="text-danger">*</span></label>
                        <input class="form-control" type="password" name="new_password1" id="new_password1"
                            placeholder="New Password" required />
                    </div>
                    <div class="mb-3">
                        <label for="new_password2">Retype Password <span class="text-danger">*</span></label>
                        <input class="form-control" type="password" name="new_password2" id="new_password2"
                            placeholder="Retype Password" required />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <span class="fa fa-close"></span> Close
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <span class="fa fa-save"></span> Update password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>