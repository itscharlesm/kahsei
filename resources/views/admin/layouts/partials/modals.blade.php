<div class="modal fade" id="userPasswordModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Change Password</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ action('App\Http\Controllers\UserController@account_update_password') }}">
                {{ csrf_field() }}
                <div class="modal-body">
                    <div class="form-group">
                        <label for="current_password">Current Password <span style="color:red;">*</span></label>
                        <input class="form-control" type="password" name="current_password" id="current_password"
                            placeholder="Current Password" required />
                    </div>
                    <hr />
                    <div class="form-group">
                        <label for="new_password1">New Password <span style="color:red;">*</span></label>
                        <input class="form-control" type="password" name="new_password1" id="new_password1"
                            placeholder="New Password" required />
                    </div>
                    <div class="form-group">
                        <label for="new_password2">Retype Password <span style="color:red;">*</span></label>
                        <input class="form-control" type="password" name="new_password2" id="new_password2"
                            placeholder="Retytpe Password" required />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><span class="fa fa-close"></span> Close</button>
                    <button type="submit" class="btn btn-primary"><span class="fa fa-save"></span> Update password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="userClientPasswordModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Change Password</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ action('App\Http\Controllers\ClientController@account_update_password') }}">
                {{ csrf_field() }}
                <div class="modal-body">
                    <div class="form-group">
                        <label for="current_password">Current Password <span style="color:red;">*</span></label>
                        <input class="form-control" type="password" name="current_password" id="current_password"
                            placeholder="Current Password" required />
                    </div>
                    <hr />
                    <div class="form-group">
                        <label for="new_password1">New Password <span style="color:red;">*</span></label>
                        <input class="form-control" type="password" name="new_password1" id="new_password1"
                            placeholder="New Password" required />
                    </div>
                    <div class="form-group">
                        <label for="new_password2">Retype Password <span style="color:red;">*</span></label>
                        <input class="form-control" type="password" name="new_password2" id="new_password2"
                            placeholder="Retytpe Password" required />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><span class="fa fa-close"></span> Close</button>
                    <button type="submit" class="btn btn-primary"><span class="fa fa-save"></span> Update password</button>
                </div>
            </form>
        </div>
    </div>
</div>