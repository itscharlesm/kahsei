<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <title>Login | KAHSEI</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="KAHSEI">
    <meta name="author" content="Infinit Soloutions">
    <link rel="shortcut icon" href="{{ asset('images/logos/kahsei.png') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="{{ asset('login/css/style.css') }}">
</head>

<body>
    @include('admin.vendor.sweetalert.alert')
    @include('admin.layouts.partials.alerts')
    @include('admin.layouts.partials.onclick')
    <section class="ftco-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-12 col-lg-10">
                    <div class="wrap d-md-flex">
                        <div class="img" style="background-image: url({{ asset('images/logos/kahsei.png') }});">
                        </div>
                        <div class="login-wrap p-4 p-md-4">
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('images/logos/kahsei.png') }}" class="img-circle elevation-2"
                                    style="width:80px;height:80px;" alt="">
                            </div>
                            <div class="d-flex justify-content-center">
                                <h5 class="text-center" style="color:gray;">KAHSEI</h5>
                            </div>
                            <p><small>PLEASE LOGIN YOUR ACCOUNT</small></p>
                            @if(session('errorMessage'))
                                <p style="color:red;"><small>{{ session('errorMessage') }}</small></p>
                            @endif

                            <form method="POST"
                                action="{{ action('App\Http\Controllers\LoginController@validate_user') }}">
                                {{ csrf_field() }}
                                <div class="row">
                                    <div class="col-md-12">
                                        <label for="email">E-Mail</label>
                                        <input class="form-control" type="email" name="email" />
                                    </div>
                                    <div class="col-md-12 password">Password</label>
                                        <input class="form-control" type="password" name="password" />
                                    </div>
                                    <div class="col-md-12 d-flex justify-content-end mt-1">
                                        <a href="#" class="text-secondary" data-toggle="modal" data-target="#forgotPasswordModal">
                                            <small>Forgot Password?</small>
                                        </a>
                                    </div>
                                    <div class="col-md-12 col-sm-12 mb-2 mt-2">
                                        <button class="btn btn-secondary btn-block"
                                            style="background-color:#313131;color:white;" type="submit"><span
                                                class="fa fa-sign-in"></span> Login</button>
                                    </div>
                                </div>
                            </form>

                            <div class="d-flex justify-content-center mt-2">
                                <small class="text-secondary">
                                    Don't have an account?
                                    <a href="{{ url('client/registration') }}" class="text-danger">Sign Up</a>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Forgot Password Modal -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" role="dialog" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border: none; border-radius: 10px; overflow: hidden;">
                <form method="POST" action="{{ action('App\Http\Controllers\LoginController@forgot_password') }}">
                    {{ csrf_field() }}
                    <div class="modal-header" style="background-color:#313131; border-bottom: none;">
                        <h5 class="modal-title text-white" id="forgotPasswordModalLabel">
                            <span class="fa fa-unlock-alt mr-2"></span> Forgot Password
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.8;">
                            <span class="fa fa-times" aria-hidden="true"></span>
                        </button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="text-center mb-3">
                            <span class="fa fa-key fa-2x" style="color:#313131;"></span>
                        </div>
                        <p class="text-center text-muted mb-4">
                            <small>Enter your registered email address or mobile number. We'll reset your password to a temporary one.</small>
                        </p>
                        <div class="form-group">
                            <label for="identifier"><small class="font-weight-bold">Email or Mobile Number</small></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" style="background-color:#f8f9fa;">
                                        <span class="fa fa-user"></span>
                                    </span>
                                </div>
                                <input type="text" class="form-control" name="identifier"
                                    placeholder="e.g. juan@email.com or 9123456789" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid #eee;">
                        <button type="button" class="btn btn-light" data-dismiss="modal">
                            <span class="fa fa-times mr-1"></span> Cancel
                        </button>
                        <button type="submit" class="btn btn-secondary" style="background-color:#313131;border-color:#313131;">
                            <span class="fa fa-refresh mr-1"></span> Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="{{ asset('login/js/jquery.min.js') }}"></script>
    <script src="{{ asset('login/js/popper.js') }}"></script>
    <script src="{{ asset('login/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('login/js/main.js') }}"></script>
</body>

</html>