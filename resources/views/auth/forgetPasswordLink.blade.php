<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Laravel 11 Custom Reset Password Functions</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <style type="text/css">
    body {
      background: #F8F9FA;
    }
  </style>
</head>
<body>
{{-- <pre>{{ print_r(session()->all(), true) }}</pre> --}}
<section class="bg-light py-3 py-md-5">
  <div class="container">
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="row justify-content-center">
      <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5 col-xxl-4">
        <div class="card border border-light-subtle rounded-3 shadow-sm mt-5">
          <div class="card-body p-3 p-md-4 p-xl-5">
            <div class="text-center mb-3">
              <h2 class="text-primary">
                Reside Smart
              </h1>
            </div>
            <h2 class="fs-6 fw-normal text-center text-secondary mb-4">Reset Password</h2>
            <form method="POST" action="{{ route('password.reset.post') }}">
              @csrf
              <input type="hidden" name="token" value="{{ $token }}">
              <input type="hidden" name="email" value="{{ $email }}">

              {{-- {{dd(session()->all())}} --}}
              @if (Session::has('message'))
                  <div class="alert alert-success" role="alert">
                      {{ Session::get('message') }}
                  </div>
              @endif

              <div class="row gy-2 overflow-hidden">

                <div class="col-12">
                  <div class="form-floating mb-3">
                    <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
                    <label for="password">{{ __('Password') }}</label>
                    @error('password')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                  </div>
                </div>

                <div class="col-12">
                  <div class="form-floating mb-3">
                    <input type="password" class="form-control" name="password_confirmation" id="password_confirmation" placeholder="Confirm Password" required>
                    <label for="password_confirmation">{{ __('Confirm Password') }}</label>
                    @error('password_confirmation')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                  </div>
                </div>

                <div class="col-12">
                  <div class="d-grid my-3">
                    <button class="btn btn-primary btn-lg" type="submit">{{ __('Reset Password') }}</button>
                  </div>
                </div>

              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

</body>
</html>
