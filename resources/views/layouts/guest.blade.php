<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Zaa Gold - Authentication</title>
    <!-- Mappings for same assets as layout/app -->
    <link rel="stylesheet" href="{{ asset('assets/vendors/mdi/css/materialdesignicons.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendors/flag-icon-css/css/flag-icon.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendors/css/vendor.bundle.base.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendors/font-awesome/css/font-awesome.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" />
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.png') }}" />
    <!-- Bootstrap Icons CDN for social login icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
      body {
        font-family: "Inter", sans-serif;
        background: radial-gradient(circle at 10% 20%, rgb(15, 23, 42) 0%, rgb(30, 41, 59) 90%);
        min-height: 100vh;
        overflow-x: hidden;
      }
      .auth-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem;
        position: relative;
        z-index: 10;
      }
      /* Animated Background Shapes */
      .bg-shape {
        position: absolute;
        border-radius: 50%;
        filter: blur(80px);
        z-index: 1;
        opacity: 0.25;
        animation: floatShape 20s infinite alternate ease-in-out;
      }
      .shape-1 {
        width: 350px;
        height: 350px;
        background: #3f50f6;
        top: -100px;
        left: -100px;
      }
      .shape-2 {
        width: 400px;
        height: 400px;
        background: #ff3ca6;
        bottom: -150px;
        right: -100px;
        animation-delay: -5s;
      }
      .shape-3 {
        width: 300px;
        height: 300px;
        background: #ffab2d;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        animation-delay: -10s;
        opacity: 0.1;
      }
      @keyframes floatShape {
        0% { transform: translate(0, 0) scale(1); }
        50% { transform: translate(60px, -40px) scale(1.1); }
        100% { transform: translate(-30px, 50px) scale(0.9); }
      }
      /* Premium Glassmorphism Card */
      .glass-card {
        background: rgba(255, 255, 255, 0.04);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 1.25rem;
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        overflow: hidden;
        color: #f8f9fa;
        max-width: 950px;
        width: 100%;
      }
      .glass-card .form-control {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.12);
        color: #f8f9fa;
        border-radius: 0.375rem;
        padding: 0.75rem 1rem;
        height: auto;
      }
      .glass-card .form-control:focus {
        background: rgba(255, 255, 255, 0.1);
        border-color: #3f50f6;
        box-shadow: 0 0 0 0.2rem rgba(63, 80, 246, 0.25);
        color: #fff;
      }
      .glass-card label {
        color: #cbd5e1;
        font-weight: 500;
        margin-bottom: 0.5rem;
      }
      /* Split Illustration Section */
      .illustration-section {
        background: linear-gradient(135deg, rgba(63, 80, 246, 0.85) 0%, rgba(255, 60, 166, 0.75) 100%);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 4rem 3rem;
        text-align: center;
        position: relative;
      }
      .illustration-section::before {
        content: '';
        position: absolute;
        inset: 0;
        background: url("{{ asset('assets/images/dashboard/img_3.jpg') }}") no-repeat center center/cover;
        mix-blend-mode: overlay;
        opacity: 0.15;
      }
      .illustration-section h3 {
        font-size: 2.25rem;
        font-weight: 700;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
        color: #fff;
        letter-spacing: -0.5px;
      }
      .illustration-section p {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1rem;
        line-height: 1.6;
        max-width: 320px;
      }
      .form-section {
        padding: 4rem 3.5rem !important;
        background: rgba(15, 23, 42, 0.35);
      }
      @media (max-width: 991.98px) {
        .illustration-section {
          display: none !important;
        }
        .form-section {
          padding: 3rem 2rem !important;
        }
      }
      /* Social Buttons */
      .btn-social {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.12);
        color: #f8f9fa;
        border-radius: 0.375rem;
        padding: 0.65rem 1rem;
        font-weight: 500;
        transition: all 0.2s ease-in-out;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        font-size: 0.875rem;
      }
      .btn-social:hover {
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
        border-color: rgba(255, 255, 255, 0.25);
      }
      .btn-social i {
        font-size: 1.1rem;
      }
      /* Links and dividers */
      .text-link {
        color: #818cf8;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.15s ease-in-out;
      }
      .text-link:hover {
        color: #a5b4fc;
        text-decoration: underline;
      }
      .divider {
        display: flex;
        align-items: center;
        text-align: center;
        color: #94a3b8;
        margin: 2rem 0;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 1.5px;
        text-transform: uppercase;
      }
      .divider::before, .divider::after {
        content: '';
        flex: 1;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      }
      .divider:not(:empty)::before { margin-right: 1em; }
      .divider:not(:empty)::after { margin-left: 1em; }
      
      .invalid-feedback {
        display: block;
        color: #f87171;
        font-size: 0.85rem;
        margin-top: 0.35rem;
      }
    </style>
  </head>
  <body>
    <!-- Background Animated Shapes -->
    <div class="bg-shape shape-1"></div>
    <div class="bg-shape shape-2"></div>
    <div class="bg-shape shape-3"></div>

    <div class="auth-container">
      <div class="glass-card">
        <div class="row no-gutters">
          <!-- Left Illustration Column -->
          <div class="col-lg-5 illustration-section">
            <div class="logo mb-4">
              <img src="{{ asset('assets/images/logo.svg') }}" alt="logo" style="max-height: 48px; filter: brightness(0) invert(1);" />
            </div>
            <h3>Zaa Gold</h3>
            <p>Welcome to Zaa Gold Enterprise Application. Please authenticate to access your dashboard.</p>
          </div>
          <!-- Right Form Column -->
          <div class="col-lg-7 form-section">
            {{ $slot }}
          </div>
        </div>
      </div>
    </div>

    <!-- js scripts -->
    <script src="{{ asset('assets/vendors/js/vendor.bundle.base.js') }}"></script>
  </body>
</html>
