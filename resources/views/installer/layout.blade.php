<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Installer — @yield('title', 'Setup')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #0f172a; min-height: 100vh; }
        .install-card { max-width: 640px; }
        .step-dot { width: 2rem; height: 2rem; line-height: 2rem; border-radius: 50%; font-size: .85rem; }
    </style>
</head>
<body class="d-flex align-items-center py-5">
    <div class="container">
        <div class="install-card mx-auto">
            <div class="text-center text-white mb-4">
                <h3 class="fw-bold mb-1"><i class="bi bi-box-seam me-2"></i>Restaurant ERP Installer</h3>
                <p class="text-secondary mb-0">@yield('title', 'Setup')</p>
            </div>

            {{-- Step indicator --}}
            @php $current = trim($__env->yieldContent('step', '0')); @endphp
            <div class="d-flex justify-content-center gap-2 mb-4">
                @foreach(['Database','App URL','Restaurant','Admin','Finish'] as $i => $label)
                    @php $n = $i + 1; @endphp
                    <div class="text-center">
                        <div class="step-dot mx-auto {{ $n <= (int) $current ? 'bg-primary text-white' : 'bg-secondary-subtle text-secondary' }}">{{ $n }}</div>
                        <small class="d-none d-sm-block {{ $n <= (int) $current ? 'text-white' : 'text-secondary' }}" style="font-size:.7rem">{{ $label }}</small>
                    </div>
                @endforeach
            </div>

            <div class="card shadow-lg border-0">
                <div class="card-body p-4">
                    @if(session('error'))
                        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}</div>
                    @endif
                    @if(session('success'))
                        <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </div>
        </div>
    </div>
</body>
</html>
