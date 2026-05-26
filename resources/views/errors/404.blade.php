<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | Three Star Medical</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --secondary: #1e293b;
            --accent: #6366f1;
            --bg: #0f172a;
            --text: #f8fafc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            perspective: 1000px;
        }

        /* Animated Background Gradients */
        .bg-glow {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 100vw;
            height: 100vh;
            background: radial-gradient(circle at center, rgba(59, 130, 246, 0.1) 0%, transparent 70%);
            z-index: -1;
            animation: pulse 8s infinite alternate;
        }

        @keyframes pulse {
            0% {
                opacity: 0.5;
                transform: translate(-50%, -50%) scale(1);
            }

            100% {
                opacity: 0.8;
                transform: translate(-50%, -50%) scale(1.2);
            }
        }

        .container {
            text-align: center;
            padding: 2rem;
            z-index: 10;
        }

        /* Glitch Effect for 404 */
        .error-code {
            font-size: clamp(8rem, 20vw, 15rem);
            font-weight: 800;
            line-height: 1;
            position: relative;
            color: var(--text);
            text-shadow: 0 0 10px rgba(59, 130, 246, 0.5);
            margin-bottom: 1rem;
            animation: float 6s ease-in-out infinite;
        }

        .error-code::before,
        .error-code::after {
            content: "404";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.7;
        }

        .error-code::before {
            color: #ff00c1;
            z-index: -1;
            animation: glitch-anim 3s infinite linear alternate-reverse;
        }

        .error-code::after {
            color: #00fff9;
            z-index: -2;
            animation: glitch-anim2 2s infinite linear alternate-reverse;
        }

        @keyframes glitch-anim {
            0% {
                clip-path: inset(50% 0 30% 0);
                transform: translate(-5px, -2px);
            }

            20% {
                clip-path: inset(10% 0 80% 0);
                transform: translate(5px, 2px);
            }

            40% {
                clip-path: inset(70% 0 10% 0);
                transform: translate(-5px, 0px);
            }

            60% {
                clip-path: inset(20% 0 40% 0);
                transform: translate(5px, -2px);
            }

            80% {
                clip-path: inset(40% 0 60% 0);
                transform: translate(-2px, 2px);
            }

            100% {
                clip-path: inset(80% 0 5% 0);
                transform: translate(2px, -2px);
            }
        }

        @keyframes glitch-anim2 {
            0% {
                clip-path: inset(10% 0 85% 0);
                transform: translate(5px, 2px);
            }

            25% {
                clip-path: inset(80% 0 15% 0);
                transform: translate(-5px, -2px);
            }

            50% {
                clip-path: inset(30% 0 60% 0);
                transform: translate(5px, 2px);
            }

            75% {
                clip-path: inset(60% 0 30% 0);
                transform: translate(-5px, -2px);
            }

            100% {
                clip-path: inset(15% 0 80% 0);
                transform: translate(5px, 2px);
            }
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0) rotateX(0);
            }

            50% {
                transform: translateY(-20px) rotateX(10deg);
            }
        }

        .message {
            font-size: 1.5rem;
            color: #94a3b8;
            margin-bottom: 3rem;
            font-weight: 300;
        }

        .btn {
            display: inline-block;
            padding: 1rem 2.5rem;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }

        .btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 30px rgba(59, 130, 246, 0.5);
        }

        .btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }

        .btn:hover::after {
            left: 100%;
        }

        /* Branding Footer */
        .footer {
            position: absolute;
            bottom: 2rem;
            font-size: 0.9rem;
            color: #64748b;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .footer b {
            color: var(--primary);
            font-weight: 600;
            background: linear-gradient(90deg, #3b82f6, #6366f1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Particles effect */
        .particles {
            position: absolute;
            width: 100%;
            height: 100%;
            background-image:
                radial-gradient(circle, #3b82f6 1px, transparent 1px),
                radial-gradient(circle, #6366f1 1px, transparent 1px);
            background-size: 50px 50px, 80px 80px;
            background-position: 0 0, 20px 20px;
            opacity: 0.1;
            z-index: -1;
            animation: move-particles 20s linear infinite;
        }

        @keyframes move-particles {
            from {
                background-position: 0 0, 20px 20px;
            }

            to {
                background-position: 500px 1000px, 400px 800px;
            }
        }
    </style>
</head>

<body>
    <div class="bg-glow"></div>
    <div class="particles"></div>

    <div class="container">
        <div class="error-code">404</div>
        <p class="message">Oops! The page you're looking for has vanished into thin air.</p>
        <a href="/" class="btn">Return to Safety</a>
    </div>

    <div class="footer">
        Powered by <b>Prowave Technologies</b>
    </div>

    <script>
        // Subtle mouse parallax effect
        document.addEventListener('mousemove', (e) => {
            const x = (window.innerWidth / 2 - e.pageX) / 50;
            const y = (window.innerHeight / 2 - e.pageY) / 50;
            document.querySelector('.container').style.transform = `translateX(${x}px) translateY(${y}px)`;
        });
    </script>
</body>

</html>
