<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>Group Formation · responsive</title>
    <!-- Font Awesome 6 (free) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* reset & base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-dark: #0b2a41;      /* deeper navy for better contrast */
            --primary-soft: #1e3a5f;
            --accent-bg: #fafcff;
            --card-border: #c9dff2;
            --footer-bg: #e9edf2;
            --text-dark: #0b1e2e;
            --shadow-sm: 0 8px 20px rgba(0, 20, 40, 0.08);
            --shadow-hover: 0 16px 28px rgba(0, 30, 60, 0.12);
            --radius-card: 28px;
            --radius-btn: 40px;
            --transition: all 0.25s ease;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(145deg, #d9e9fa 0%, #ecf3fd 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 16px;
            margin: 0;
            line-height: 1.4;
            -webkit-font-smoothing: antialiased;
        }

        /* main card container – fluid width, never exceeds readable size */
        .container {
            width: 100%;
            max-width: 860px;          /* slightly larger for big tablets, but still compact */
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(2px);
            border-radius: 36px;
            overflow: hidden;
            box-shadow: 0 20px 40px -8px rgba(0, 35, 65, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: var(--transition);
        }

        /* header – refined spacing & mobile padding */
        .header {
            background: var(--primary-dark);
            color: white;
            text-align: center;
            padding: clamp(1.8rem, 7vw, 2.8rem) 1.5rem;
            border-bottom: 4px solid #5195ce;
        }

        .header h1 {
            font-size: clamp(1.8rem, 6vw, 2.8rem);
            font-weight: 600;
            letter-spacing: -0.01em;
            margin-bottom: 0.3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
            flex-wrap: wrap;
        }

        .header h1 span {
            white-space: nowrap;
        }

        .header p {
            font-size: clamp(1rem, 3.5vw, 1.25rem);
            opacity: 0.9;
            font-weight: 350;
            background: rgba(255,255,255,0.12);
            display: inline-block;
            padding: 0.3rem 1.2rem;
            border-radius: 50px;
            margin-top: 0.5rem;
            backdrop-filter: blur(2px);
        }

        /* roles section – adaptive padding */
        .roles {
            padding: clamp(1.5rem, 5vw, 2.5rem);
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        /* role card – fully responsive flex behaviour */
        .role-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.2rem 1.5rem;
            background: var(--accent-bg);
            border: 1.5px solid var(--card-border);
            border-radius: var(--radius-card);
            transition: var(--transition);
            gap: 1rem;
            box-shadow: 0 4px 8px rgba(0,0,0,0.02);
        }

        .role-card:hover {
            transform: translateY(-4px);
            background: #ffffff;
            border-color: #9fc5e8;
            box-shadow: var(--shadow-hover);
        }

        /* left part: icon + name */
        .role-left {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;          /* for ultra narrow phones: allow wrap */
        }

        .role-icon {
            font-size: 2.8rem;        /* larger but scales with view */
            line-height: 1;
            min-width: 3rem;
            text-align: center;
            filter: drop-shadow(2px 4px 4px rgba(0,35,70,0.15));
        }

        .role-name {
            font-size: clamp(1.5rem, 4.5vw, 2.1rem);
            font-weight: 700;
            color: var(--text-dark);
            letter-spacing: -0.02em;
        }

        /* button – always touch friendly, fluid width on small */
        .btn {
            text-decoration: none;
            background: var(--primary-dark);
            color: white;
            padding: 0.85rem 2rem;
            border-radius: var(--radius-btn);
            font-weight: 600;
            font-size: 1.1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            white-space: nowrap;
            transition: var(--transition);
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 8px 16px -6px rgba(0, 45, 80, 0.3);
            letter-spacing: 0.3px;
        }

        .btn i {
            font-size: 1rem;
            transition: transform 0.15s;
        }

        .btn:hover {
            background: #1f4b73;
            transform: scale(1.02);
            box-shadow: 0 12px 20px -8px #0b2a4190;
        }

        .btn:hover i {
            transform: translateX(5px);
        }

        /* footer */
        .footer {
            text-align: center;
            padding: 1.3rem 1.5rem;
            background: #dee7f0;
            color: #1d374e;
            font-size: 1rem;
            font-weight: 500;
            border-top: 1px solid #b9cee4;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .footer i {
            color: #0b5e23;
        }

        /* ===== responsive superpowers ===== */

        /* tablet & medium devices (600-900) fine-tune */
        @media (max-width: 700px) {
            .role-card {
                padding: 1.2rem 1.2rem;
            }
            .btn {
                padding: 0.8rem 1.5rem;
                font-size: 1rem;
            }
        }

        /* phones: stack everything for max readability + fat fingers */
        @media (max-width: 550px) {
            .role-card {
                flex-direction: column;
                align-items: stretch;      /* let child fill width */
                text-align: center;
                gap: 1rem;
            }

            .role-left {
                justify-content: center;
                flex-direction: column;    /* icon above name if wanted (optional) */
                gap: 0.25rem;
            }

            .role-icon {
                font-size: 3.2rem;         /* even bigger icon on mobile */
            }

            .role-name {
                font-size: 2rem;
            }

            .btn {
                width: 100%;
                justify-content: center;
                padding: 1rem 1rem;
                font-size: 1.2rem;
                white-space: normal;       /* allow very tiny text wrap (though unlikely) */
            }

            .footer {
                flex-direction: column;
                gap: 0.3rem;
                font-size: 0.95rem;
            }
        }

        /* very small devices (<=360) */
        @media (max-width: 360px) {
            .container {
                border-radius: 28px;
            }
            .header {
                padding: 1.5rem 0.8rem;
            }
            .header h1 {
                font-size: 1.8rem;
            }
            .role-icon {
                font-size: 2.6rem;
            }
            .role-name {
                font-size: 1.7rem;
            }
            .btn {
                font-size: 1rem;
                padding: 0.8rem;
            }
        }

        /* landscape orientation on small phones */
        @media (max-height: 500px) and (orientation: landscape) {
            body {
                align-items: flex-start;
                padding: 12px;
            }
            .container {
                margin: 8px auto;
            }
            .role-card {
                padding: 0.8rem 1rem;
            }
        }

        /* improve tap targets */
        .btn, .role-card {
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }

        /* ensure no overflow */
        img, svg, .fa, i {
            max-width: 100%;
        }

        /* optional: small icon inside footer */
        .footer i {
            margin-right: 4px;
        }
    </style>
</head>
<body>

<div class="container">

    <!-- header with clean responsive title -->
    <div class="header">
        <h1>
            <span>👥</span> 
            <span>GROUP FORMATION</span>
        </h1>
        <p>Select your role to continue</p>
    </div>

    <!-- roles section – three cards -->
    <div class="roles">

        <!-- STUDENT card -->
        <div class="role-card">
            <div class="role-left">
                <div class="role-icon">🎓</div>
                <div class="role-name">Student</div>
            </div>
            <a href="student_registration.php" class="btn">
                <i class="fas fa-arrow-right"></i> Enter
            </a>
        </div>

        <!-- TEACHER card -->
        <div class="role-card">
            <div class="role-left">
                <div class="role-icon">👩‍🏫</div>
                <div class="role-name">Teacher</div>
            </div>
            <a href="teacher.php" class="btn">
                <i class="fas fa-arrow-right"></i> Enter
            </a>
        </div>

        <!-- ADMIN card -->
        <div class="role-card">
            <div class="role-left">
                <div class="role-icon">⚙️</div>
                <div class="role-name">Admin</div>
            </div>
            <a href="admin_login.php" class="btn">
                <i class="fas fa-arrow-right"></i> Enter
            </a>
        </div>

    </div>

    <!-- footer with message & icon -->
    <div class="footer">
        <i class="fas fa-check-circle" style="color:#1f8b4c;"></i>
        <span>Student is public | Teacher & Admin require login</span>
    </div>

</div>

<!-- tiny extra: optional note – but keeps it clean -->
</body>
</html>