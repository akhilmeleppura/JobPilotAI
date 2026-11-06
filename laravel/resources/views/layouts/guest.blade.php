<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sign In') — JobPilot AI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #0f172a;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        .auth-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 440px;
            padding: 2.25rem;
        }
        .brand-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .brand-header i {
            font-size: 2.5rem;
            color: #2563eb;
        }
        .brand-header h4 {
            font-weight: 700;
            color: #0f172a;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="brand-header">
            <i class="bi bi-send-check-fill"></i>
            <h4>JobPilot AI</h4>
            <p class="text-muted small">Personal Autonomous Career Application Platform</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger py-2 small">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

