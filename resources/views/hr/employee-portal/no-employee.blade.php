<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Portal | Three Star Medical</title>
    <style>
        body { min-height: 100vh; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
        .card { background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 40px; text-align: center; color: #fff; max-width: 380px; }
        .icon { font-size: 60px; margin-bottom: 20px; }
        h2 { margin-bottom: 10px; }
        p { opacity: 0.7; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">👤</div>
        <h2>No Employee Profile</h2>
        <p>Your account is not linked to an employee record. Please contact HR to set up your profile.</p>
        <div style="display:flex;gap:12px;justify-content:center;margin-top:24px;flex-wrap:wrap;">
            <a href="{{ url('/home') }}" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:#6c63ff;color:#fff;border-radius:10px;text-decoration:none;font-weight:600;font-size:14px;">
                Dashboard
            </a>
            <form method="POST" action="{{ route('employee.logout') }}" style="display:inline;">
                @csrf
                <button type="submit" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.4);color:#ef4444;border-radius:10px;cursor:pointer;font-weight:600;font-size:14px;">
                    Log Out
                </button>
            </form>
        </div>
    </div>
</body>
</html>
