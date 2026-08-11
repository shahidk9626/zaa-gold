<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Maintenance - AurOnGold</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <style>
        :root {
            --gold: #d4af37;
            --gold-gradient: linear-gradient(135deg, #f6e27a 0%, #d4af37 50%, #9c7a1a 100%);
            --dark: #121212;
            --dark-card: #1e1e1e;
            --cream: #faf9f6;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--dark);
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow: hidden;
            position: relative;
        }

        /* Radial Glow Backdrop */
        .backdrop-glow {
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.08) 0%, rgba(0,0,0,0) 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1;
            pointer-events: none;
        }

        .maintenance-card {
            background-color: var(--dark-card);
            border: 1px solid rgba(212, 175, 55, 0.15);
            border-radius: 24px;
            padding: 50px 40px;
            max-width: 540px;
            width: 90%;
            text-align: center;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
            z-index: 2;
            position: relative;
        }

        .brand-logo {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            font-weight: 800;
            background: var(--gold-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 25px;
            letter-spacing: 1px;
        }

        .icon-wrapper {
            font-size: 4rem;
            background: var(--gold-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
            display: inline-block;
            animation: pulse 2.5s infinite ease-in-out;
        }

        h2 {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 1.8rem;
            color: #ffffff;
            margin-bottom: 15px;
            letter-spacing: 0.5px;
        }

        p {
            color: #b3b3b3;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .divider {
            height: 2px;
            width: 60px;
            background: var(--gold-gradient);
            margin: 25px auto;
            border-radius: 2px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: rgba(212, 175, 55, 0.1);
            border: 1px solid rgba(212, 175, 55, 0.2);
            color: var(--gold);
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-badge .dot {
            width: 8px;
            height: 8px;
            background-color: var(--gold);
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 8px var(--gold);
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.9; }
            50% { transform: scale(1.05); opacity: 1; }
            100% { transform: scale(1); opacity: 0.9; }
        }
    </style>
</head>
<body>

    <div class="backdrop-glow"></div>

    <div class="maintenance-card">
        <div class="brand-logo">AurOnGold</div>
        <div class="icon-wrapper">
            <i class="bi bi-gear-wide-connected"></i>
        </div>
        <h2>WE'LL BE BACK SOON</h2>
        <p>We are currently performing scheduled maintenance.</p>
        <p>Our website is temporarily unavailable while we make improvements.</p>
        <p>Please check back shortly.</p>
        
        <div class="divider"></div>
        
        <div class="status-badge">
            <span class="dot"></span>
            Scheduled Maintenance Active
        </div>
    </div>

</body>
</html>
