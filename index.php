
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f7fbf8">
    <meta name="description" content="PharmaStock : une solution moderne pour organiser et gérer vos stocks pharmaceutiques.">
    <title>PharmaStock | La gestion intelligente de vos stocks</title>

    <style>
        :root {
            --green: #07865e;
            --green-dark: #075b43;
            --green-light: #e7f7ef;
            --navy: #15382f;
            --text: #536b63;
            --muted: #82948c;
            --border: #e5eee8;
            --white: #fff;
            --background: #f7fbf8;
            --shadow: 0 24px 70px rgba(18, 73, 48, .10);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 90px;
        }

        body {
            font-family: Inter, "Segoe UI", Arial, sans-serif;
            color: var(--navy);
            background: var(--background);
            line-height: 1.65;
            -webkit-font-smoothing: antialiased;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        button, a {
            -webkit-tap-highlight-color: transparent;
        }

        .container {
            width: min(1180px, 90%);
            margin: auto;
        }

        /* NAVIGATION */

        .header {
            position: sticky;
            top: 0;
            z-index: 20;
            background: rgba(255,255,255,.91);
            backdrop-filter: blur(18px);
            border-bottom: 1px solid rgba(222,235,227,.9);
        }

        .nav {
            min-height: 82px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            gap: 11px;
            flex-shrink: 0;
        }

        .logo-mark {
            display: grid;
            place-items: center;
            width: 45px;
            height: 45px;
            color: white;
            background: linear-gradient(145deg, #13ad7a, #06734f);
            border-radius: 15px;
            box-shadow: 0 7px 18px rgba(7,134,94,.2);
        }

        .logo-mark svg {
            width: 26px;
            height: 26px;
        }

        .logo-name {
            font-size: 1.38rem;
            font-weight: 850;
            letter-spacing: -1px;
            color: var(--navy);
        }

        .logo-name span {
            color: var(--green);
        }

        .nav-center {
            display: flex;
            align-items: center;
            gap: 32px;
        }

        .nav-center a {
            color: #63766e;
            font-size: .91rem;
            font-weight: 600;
            transition: color .2s;
        }

        .nav-center a:hover {
            color: var(--green);
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 48px;
            padding: 0 21px;
            border-radius: 12px;
            font-size: .93rem;
            font-weight: 750;
            transition: transform .2s, box-shadow .2s, background .2s;
        }

        .button:hover {
            transform: translateY(-2px);
        }

        .button-primary {
            color: white;
            background: var(--green);
            box-shadow: 0 7px 18px rgba(7,134,94,.18);
        }

        .button-primary:hover {
            background: var(--green-dark);
            box-shadow: 0 10px 24px rgba(7,134,94,.25);
        }

        .button svg {
            width: 18px;
            height: 18px;
        }

        .button-outline {
            color: var(--navy);
            background: white;
            border: 1px solid #dce8e0;
        }

        .button-outline:hover {
            border-color: #a9cfba;
        }

        /* HERO */

        .hero {
            position: relative;
            overflow: hidden;
            padding: 84px 0 90px;
            background:
                radial-gradient(ellipse at 85% 35%, rgba(190,239,213,.55), transparent 35%),
                radial-gradient(ellipse at 10% 85%, rgba(220,244,231,.55), transparent 33%),
                linear-gradient(130deg, #fbfdfb 0%, #f1faf4 100%);
        }

        .hero::before,
        .hero::after {
            content: "";
            position: absolute;
            border: 1px solid rgba(8,134,94,.09);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero::before {
            width: 470px;
            height: 470px;
            right: -220px;
            top: -190px;
        }

        .hero::after {
            width: 330px;
            height: 330px;
            left: -210px;
            bottom: -190px;
        }

        .hero-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: 1.04fr .96fr;
            gap: 62px;
            align-items: center;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 8px 13px;
            margin-bottom: 25px;
            color: #08754f;
            background: #e5f7ed;
            border: 1px solid #d1efdd;
            border-radius: 40px;
            font-size: .8rem;
            font-weight: 750;
        }

        .eyebrow svg {
            width: 17px;
            height: 17px;
        }

        .hero h1 {
            max-width: 650px;
            margin-bottom: 23px;
            font-size: clamp(2.8rem, 5.2vw, 4.65rem);
            line-height: 1.07;
            letter-spacing: -3px;
            font-weight: 850;
        }

        .hero h1 .accent {
            color: var(--green);
        }

        .hero-description {
            max-width: 530px;
            margin-bottom: 30px;
            color: var(--text);
            font-size: 1.06rem;
            line-height: 1.9;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 13px;
            margin-bottom: 30px;
        }

        .hero-actions .button {
            min-height: 54px;
            padding: 0 23px;
        }

        .assurance {
            display: flex;
            align-items: center;
            gap: 11px;
            color: #60766c;
            font-size: .86rem;
        }

        .assurance-icon {
            width: 29px;
            height: 29px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            color: var(--green);
            background: #dff4e7;
            border-radius: 50%;
        }

        .assurance-icon svg {
            width: 16px;
            height: 16px;
        }

        /* APERÇU DU TABLEAU DE BORD */

        .preview-wrap {
            min-width: 0;
            position: relative;
            padding: 17px;
        }

        .preview-glow {
            position: absolute;
            inset: 10% 3%;
            background: rgba(30,168,112,.15);
            filter: blur(48px);
            border-radius: 50%;
        }

        .preview {
            position: relative;
            overflow: hidden;
            background: white;
            border: 1px solid rgba(220,235,225,.95);
            border-radius: 23px;
            box-shadow: var(--shadow);
        }

        .preview-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 21px;
            border-bottom: 1px solid #edf2ee;
        }

        .preview-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: .89rem;
            font-weight: 800;
        }

        .mini-logo {
            display: grid;
            place-items: center;
            width: 32px;
            height: 32px;
            color: white;
            background: var(--green);
            border-radius: 10px;
            font-size: 19px;
        }

        .preview-profile {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #72867c;
            font-size: .75rem;
        }

        .profile-dot {
            display: grid;
            place-items: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e1f4e9;
            color: var(--green-dark);
            font-weight: 800;
        }

        .preview-content {
            padding: 23px;
        }

        .preview-heading {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 20px;
        }

        .preview-heading h2 {
            font-size: 1.18rem;
            letter-spacing: -.5px;
        }

        .preview-heading p {
            margin-top: 3px;
            color: var(--muted);
            font-size: .77rem;
        }

        .preview-tag {
            padding: 6px 9px;
            color: var(--green);
            background: #e8f8ee;
            border-radius: 8px;
            font-size: .69rem;
            font-weight: 750;
            white-space: nowrap;
        }

        .metric-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 17px;
        }

        .metric {
            padding: 15px;
            border: 1px solid #eaf0eb;
            border-radius: 15px;
            background: #fff;
        }

        .metric-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 15px;
        }

        .metric-icon {
            display: grid;
            place-items: center;
            width: 37px;
            height: 37px;
            color: #087f5b;
            background: #e5f7ed;
            border-radius: 11px;
        }

        .metric-icon svg {
            width: 19px;
            height: 19px;
        }

        .metric:nth-child(2) .metric-icon {
            color: #2872ad;
            background: #e8f3fd;
        }

        .metric:nth-child(3) .metric-icon {
            color: #a76b14;
            background: #fff3df;
        }

        .metric:nth-child(4) .metric-icon {
            color: #7957b3;
            background: #f0eaff;
        }

        .metric-label {
            display: block;
            color: #71847b;
            font-size: .75rem;
        }

        .metric-value {
            font-size: 1.45rem;
            line-height: 1.3;
            font-weight: 850;
            letter-spacing: -.7px;
        }

        .metric-note {
            display: block;
            margin-top: 5px;
            color: #84948c;
            font-size: .68rem;
        }

        .inventory {
            padding: 16px;
            border: 1px solid #eaf0eb;
            border-radius: 15px;
        }

        .inventory-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 13px;
        }

        .inventory-heading h3 {
            font-size: .86rem;
        }

        .inventory-heading span {
            color: #7c8e85;
            font-size: .7rem;
        }

        .inventory-row {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 11px 0;
            border-bottom: 1px solid #f0f4f1;
        }

        .inventory-row:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .product-icon {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            color: #087f5b;
            background: #e9f7ef;
            border-radius: 11px;
        }

        .product-icon svg {
            width: 21px;
            height: 21px;
        }

        .product-info {
            min-width: 0;
            flex: 1;
        }

        .product-info strong {
            display: block;
            font-size: .77rem;
        }

        .product-info small {
            display: block;
            color: #86958e;
            font-size: .69rem;
        }

        .product-status {
            padding: 5px 8px;
            color: #087f5b;
            background: #e6f7ed;
            border-radius: 7px;
            font-size: .67rem;
            font-weight: 750;
            white-space: nowrap;
        }

        .floating-note {
            position: absolute;
            right: -5px;
            bottom: 4px;
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 13px 16px;
            background: white;
            border: 1px solid #e4eee7;
            border-radius: 15px;
            box-shadow: 0 12px 35px rgba(18,73,48,.12);
        }

        .floating-note-icon {
            display: grid;
            place-items: center;
            width: 40px;
            height: 40px;
            color: var(--green);
            background: #e5f7ed;
            border-radius: 12px;
        }

        .floating-note-icon svg {
            width: 21px;
            height: 21px;
        }

        .floating-note strong {
            display: block;
            font-size: .79rem;
        }

        .floating-note small {
            color: var(--muted);
            font-size: .71rem;
        }

        /* BANDE DE POINTS FORTS */

        .benefit-strip {
            position: relative;
            z-index: 2;
            margin-top: -1px;
            background: white;
            border-top: 1px solid #e8f0ea;
            border-bottom: 1px solid #e8f0ea;
        }

        .benefit-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            padding: 25px 0;
        }

        .benefit {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            padding: 5px 15px;
            border-right: 1px solid #e8f0ea;
        }

        .benefit:last-child {
            border-right: 0;
        }

        .benefit-icon {
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            flex-shrink: 0;
            color: var(--green);
            background: #e8f7ee;
            border-radius: 13px;
        }

        .benefit-icon svg {
            width: 21px;
            height: 21px;
        }

        .benefit strong {
            display: block;
            font-size: .88rem;
        }

        .benefit small {
            display: block;
            margin-top: 2px;
            color: var(--muted);
            font-size: .75rem;
        }

        /* FONCTIONNALITÉS */

        .features {
            padding: 96px 0;
            background: #fff;
        }

        .section-heading {
            max-width: 680px;
            text-align: center;
            margin: 0 auto 48px;
        }

        .section-kicker {
            display: inline-block;
            margin-bottom: 13px;
            color: var(--green);
            font-size: .78rem;
            font-weight: 850;
            letter-spacing: 1.8px;
            text-transform: uppercase;
        }

        .section-heading h2 {
            margin-bottom: 15px;
            font-size: clamp(2rem, 3.8vw, 3rem);
            letter-spacing: -1.5px;
            line-height: 1.17;
        }

        .section-heading p {
            color: var(--text);
            font-size: 1rem;
            line-height: 1.8;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
        }

        .feature-card {
            padding: 30px 27px;
            background: #fbfdfb;
            border: 1px solid #e6efe8;
            border-radius: 20px;
            transition: transform .25s, box-shadow .25s, border-color .25s;
        }

        .feature-card:hover {
            transform: translateY(-6px);
            border-color: #c8e7d3;
            box-shadow: 0 20px 45px rgba(18,73,48,.07);
        }

        .feature-number {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 26px;
        }

        .feature-number .feature-icon {
            display: grid;
            place-items: center;
            width: 55px;
            height: 55px;
            color: var(--green);
            background: #e5f7ed;
            border-radius: 16px;
        }

        .feature-icon svg {
            width: 25px;
            height: 25px;
        }

        .feature-number > span {
            color: #b6c9bd;
            font-size: .85rem;
            font-weight: 800;
        }

        .feature-card h3 {
            margin-bottom: 11px;
            font-size: 1.13rem;
            letter-spacing: -.3px;
        }

        .feature-card p {
            color: var(--text);
            font-size: .91rem;
            line-height: 1.85;
        }

        .feature-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-top: 21px;
            color: var(--green);
            font-size: .86rem;
            font-weight: 750;
        }

        .feature-link svg {
            width: 16px;
            height: 16px;
            transition: transform .2s;
        }

        .feature-card:hover .feature-link svg {
            transform: translateX(4px);
        }

        /* APPEL À L'ACTION */

        .cta-section {
            padding: 0 0 90px;
            background: white;
        }

        .cta-panel {
            position: relative;
            overflow: hidden;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 35px;
            padding: 53px;
            color: white;
            background: linear-gradient(115deg, #074c39, #07865e);
            border-radius: 27px;
        }

        .cta-panel::before,
        .cta-panel::after {
            content: "";
            position: absolute;
            border: 1px solid rgba(255,255,255,.13);
            border-radius: 50%;
            pointer-events: none;
        }

        .cta-panel::before {
            width: 360px;
            height: 360px;
            right: -80px;
            top: -200px;
        }

        .cta-panel::after {
            width: 250px;
            height: 250px;
            right: 90px;
            bottom: -190px;
        }

        .cta-copy {
            position: relative;
            z-index: 1;
        }

        .cta-copy .section-kicker {
            color: #a9ebca;
        }

        .cta-copy h2 {
            max-width: 600px;
            margin-bottom: 12px;
            font-size: clamp(1.8rem, 3.4vw, 2.55rem);
            letter-spacing: -1px;
            line-height: 1.2;
        }

        .cta-copy p {
            max-width: 550px;
            color: #d4eee2;
            font-size: .96rem;
        }

        .cta-panel .button {
            position: relative;
            z-index: 1;
            flex-shrink: 0;
            min-height: 54px;
            color: var(--green-dark);
            background: white;
        }

        .cta-panel .button:hover {
            background: #effaf3;
        }

        /* PIED DE PAGE */

        .footer {
            padding: 28px 0;
            background: #fbfdfb;
            border-top: 1px solid #e5eee8;
        }

        .footer-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 9px;
            font-size: .97rem;
            font-weight: 850;
        }

        .footer-brand .logo-mark {
            width: 31px;
            height: 31px;
            border-radius: 10px;
        }

        .footer-brand .logo-mark svg {
            width: 19px;
            height: 19px;
        }

        .footer-copy {
            color: var(--muted);
            font-size: .82rem;
        }

        .footer-login {
            color: var(--green);
            font-size: .86rem;
            font-weight: 750;
        }

        .footer-login:hover {
            text-decoration: underline;
        }

        /* ADAPTATION AUX ÉCRANS */

        @media (max-width: 1000px) {
            .hero-grid {
                gap: 30px;
            }

            .hero h1 {
                font-size: clamp(2.7rem, 5.3vw, 3.7rem);
            }

            .nav-center {
                gap: 19px;
            }

            .preview-wrap {
                padding: 9px;
            }

            .floating-note {
                right: -2px;
            }

            .benefit {
                padding: 5px 10px;
            }
        }

        @media (max-width: 800px) {
            .nav-center {
                display: none;
            }

            .hero {
                padding: 65px 0 72px;
            }

            .hero-grid {
                grid-template-columns: 1fr;
                gap: 36px;
            }

            .hero-copy {
                text-align: center;
            }

            .hero-description {
                margin-left: auto;
                margin-right: auto;
            }

            .hero-actions,
            .assurance {
                justify-content: center;
            }

            .preview-wrap {
                width: min(100%, 550px);
                margin: 0 auto;
            }

            .benefit-grid {
                grid-template-columns: 1fr;
                gap: 0;
                padding: 10px 0;
            }

            .benefit {
                justify-content: flex-start;
                padding: 15px 8px;
                border-right: 0;
                border-bottom: 1px solid #e8f0ea;
            }

            .benefit:last-child {
                border-bottom: 0;
            }

            .feature-grid {
                grid-template-columns: 1fr;
            }

            .feature-card {
                padding: 25px;
            }

            .features {
                padding: 72px 0;
            }

            .cta-panel {
                flex-direction: column;
                align-items: flex-start;
                padding: 36px 28px;
            }
        }

        @media (max-width: 480px) {
            .container {
                width: 91%;
            }

            .nav {
                min-height: 72px;
            }

            .logo-name {
                font-size: 1.2rem;
            }

            .logo-mark {
                width: 40px;
                height: 40px;
                border-radius: 13px;
            }

            .nav > .button {
                min-height: 43px;
                padding: 0 13px;
                font-size: .82rem;
            }

            .hero {
                padding: 48px 0 56px;
            }

            .hero h1 {
                font-size: clamp(2.45rem, 11vw, 3.3rem);
                letter-spacing: -1.8px;
            }

            .hero-description {
                font-size: .96rem;
            }

            .hero-actions {
                flex-direction: column;
            }

            .hero-actions .button {
                width: 100%;
            }

            .preview-content {
                padding: 15px;
            }

            .preview-topbar {
                padding: 14px;
            }

            .preview-profile > span:not(.profile-dot) {
                display: none;
            }

            .metric {
                padding: 11px;
            }

            .metric-value {
                font-size: 1.28rem;
            }

            .metric-label {
                font-size: .69rem;
            }

            .inventory {
                padding: 12px;
            }

            .product-info strong {
                font-size: .7rem;
            }

            .product-status {
                font-size: .61rem;
                padding: 4px 6px;
            }

            .floating-note {
                position: relative;
                right: auto;
                bottom: auto;
                width: fit-content;
                max-width: 100%;
                margin: 13px auto 0;
            }

            .section-heading h2 {
                font-size: 2rem;
            }

            .cta-panel {
                padding: 30px 22px;
                border-radius: 20px;
            }

            .cta-panel .button {
                width: 100%;
                padding: 0 12px;
                font-size: .85rem;
            }

            .footer-inner {
                flex-direction: column;
                text-align: center;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                scroll-behavior: auto !important;
                transition: none !important;
            }
        }
    </style>
</head>

<body>

<header class="header">
    <div class="container nav">
        <a href="index.php" class="logo" aria-label="PharmaStock, accueil">
            <span class="logo-mark">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M9 3h6v6h6v6h-6v6H9v-6H3V9h6V3Z"
                          fill="currentColor"/>
                </svg>
            </span>
            <span class="logo-name">Pharma<span>Stock</span></span>
        </a>

        <nav class="nav-center" aria-label="Navigation principale">
            <a href="#accueil">Accueil</a>
            <a href="#avantages">Nos avantages</a>
            <a href="#fonctionnalites">Fonctionnalités</a>
            <a href="#apropos">À propos</a>
        </nav>

        <a href="frontend/pages/connexion.html" class="button button-primary">
            Connexion
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" aria-hidden="true">
                <path d="M5 12h14M13 6l6 6-6 6"/>
            </svg>
        </a>
    </div>
</header>

<main>
    <section class="hero" id="accueil">
        <div class="container hero-grid">

            <div class="hero-copy">
                <div class="eyebrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" aria-hidden="true">
                        <path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3l-5.6 2.9 1.1-6.2L3 9.6l6.2-.9L12 3Z"/>
                    </svg>
                    La gestion pharmaceutique, autrement
                </div>

                <h1 id="apropos">
                    Chaque produit<br>
                    à sa place.<br>
                    <span class="accent">Chaque stock</span><br>
                    sous contrôle.
                </h1>

                <p class="hero-description">
                    Découvrez PharmaStock, votre espace de gestion des stocks
                    pharmaceutiques. Centralisez vos produits, organisez vos
                    mouvements et gardez une meilleure visibilité sur votre
                    inventaire grâce à une interface claire et intuitive.
                </p>

                <div class="hero-actions">
                    <a href="frontend/pages/connexion.html" class="button button-primary">
                        Accéder à mon espace
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" aria-hidden="true">
                            <path d="M5 12h14M13 6l6 6-6 6"/>
                        </svg>
                    </a>

                    <a href="#fonctionnalites" class="button button-outline">
                        Découvrir PharmaStock
                    </a>
                </div>

                <div class="assurance">
                    <span class="assurance-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.3" aria-hidden="true">
                            <path d="m5 12 4 4L19 6"/>
                        </svg>
                    </span>
                    Une interface pensée pour simplifier votre quotidien.
                </div>
            </div>

            <div class="preview-wrap" aria-label="Illustration du tableau de bord PharmaStock">
                <div class="preview-glow"></div>

                <div class="preview">
                    <div class="preview-topbar">
                        <div class="preview-brand">
                            <span class="mini-logo">✚</span>
                            <span>PharmaStock</span>
                        </div>

                        <div class="preview-profile">
                            <span>Votre espace</span>
                            <span class="profile-dot">P</span>
                        </div>
                    </div>

                    <div class="preview-content">
                        <div class="preview-heading">
                            <div>
                                <h2>Vue d'ensemble</h2>
                                <p>Un aperçu de votre gestion</p>
                            </div>
                            <span class="preview-tag">APERÇU</span>
                        </div>

                        <div class="metric-grid">
                            <div class="metric">
                                <div class="metric-top">
                                    <span class="metric-icon">
                                        <svg viewBox="0 0 24 24" fill="none"
                                             stroke="currentColor" stroke-width="1.8">
                                            <path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z"/>
                                            <path d="m4.5 7.5 7.5 4 7.5-4M12 12v9"/>
                                        </svg>
                                    </span>
                                </div>
                                <span class="metric-label">Produits</span>
                                <div class="metric-value">Inventaire</div>
                                <span class="metric-note">Vos références</span>
                            </div>

                            <div class="metric">
                                <div class="metric-top">
                                    <span class="metric-icon">
                                        <svg viewBox="0 0 24 24" fill="none"
                                             stroke="currentColor" stroke-width="1.8">
                                            <path d="M12 5v14M5 12h14"/>
                                            <rect x="3" y="3" width="18" height="18" rx="5"/>
                                        </svg>
                                    </span>
                                </div>
                                <span class="metric-label">Approvisionnements</span>
                                <div class="metric-value">Entrées</div>
                                <span class="metric-note">Suivi des arrivages</span>
                            </div>

                            <div class="metric">
                                <div class="metric-top">
                                    <span class="metric-icon">
                                        <svg viewBox="0 0 24 24" fill="none"
                                             stroke="currentColor" stroke-width="1.8">
                                            <path d="M12 19V5M5 12l7-7 7 7"/>
                                        </svg>
                                    </span>
                                </div>
                                <span class="metric-label">Distribution</span>
                                <div class="metric-value">Sorties</div>
                                <span class="metric-note">Suivi des mouvements</span>
                            </div>

                            <div class="metric">
                                <div class="metric-top">
                                    <span class="metric-icon">
                                        <svg viewBox="0 0 24 24" fill="none"
                                             stroke="currentColor" stroke-width="1.8">
                                            <circle cx="11" cy="11" r="7"/>
                                            <path d="m16 16 4 4M11 7v8M7 11h8"/>
                                        </svg>
                                    </span>
                                </div>
                                <span class="metric-label">Contrôle</span>
                                <div class="metric-value">Inventaire</div>
                                <span class="metric-note">Informations utiles</span>
                            </div>
                        </div>

                        <div class="inventory">
                            <div class="inventory-heading">
                                <h3>Un espace tout-en-un</h3>
                                <span>Vue illustrative</span>
                            </div>

                            <div class="inventory-row">
                                <span class="product-icon">
                                    <svg viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="1.8">
                                        <path d="M8 4h8a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3H8a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3Z"/>
                                        <path d="M5 12h14"/>
                                        <path d="M12 8v8"/>
                                    </svg>
                                </span>
                                <div class="product-info">
                                    <strong>Produits pharmaceutiques</strong>
                                    <small>Références et quantités</small>
                                </div>
                                <span class="product-status">Gestion</span>
                            </div>

                            <div class="inventory-row">
                                <span class="product-icon">
                                    <svg viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="1.8">
                                        <path d="M4 7h16v13H4z"/>
                                        <path d="M2 4h20v3H2zM12 10v7m-3-3 3 3 3-3"/>
                                    </svg>
                                </span>
                                <div class="product-info">
                                    <strong>Entrées et sorties</strong>
                                    <small>Mouvements centralisés</small>
                                </div>
                                <span class="product-status">Suivi</span>
                            </div>

                            <div class="inventory-row">
                                <span class="product-icon">
                                    <svg viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="1.8">
                                        <path d="M4 4h16v16H4z"/>
                                        <path d="m8 12 3 3 5-6"/>
                                    </svg>
                                </span>
                                <div class="product-info">
                                    <strong>Inventaire organisé</strong>
                                    <small>Informations à portée de main</small>
                                </div>
                                <span class="product-status">Contrôle</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="floating-note">
                    <span class="floating-note-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" aria-hidden="true">
                            <path d="M12 3 20 6v5c0 5-3.5 8-8 10-4.5-2-8-5-8-10V6l8-3Z"/>
                            <path d="m8 12 2.5 2.5L16 9"/>
                        </svg>
                    </span>
                    <span>
                        <strong>Tout au même endroit</strong>
                        <small>Une gestion plus claire</small>
                    </span>
                </div>
            </div>

        </div>
    </section>

    <section class="benefit-strip" id="avantages">
        <div class="container benefit-grid">
            <div class="benefit">
                <span class="benefit-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" aria-hidden="true">
                        <path d="M4 5h16v15H4zM8 9h8M8 13h5"/>
                    </svg>
                </span>
                <span>
                    <strong>Organisation simplifiée</strong>
                    <small>Des informations centralisées</small>
                </span>
            </div>

            <div class="benefit">
                <span class="benefit-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" aria-hidden="true">
                        <path d="M4 19V5m0 14h17M8 15l4-5 4 3 5-7"/>
                    </svg>
                </span>
                <span>
                    <strong>Meilleure visibilité</strong>
                    <small>Une vue claire sur les stocks</small>
                </span>
            </div>

            <div class="benefit">
                <span class="benefit-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 7v5l3 2"/>
                    </svg>
                </span>
                <span>
                    <strong>Gain de temps</strong>
                    <small>Un accès plus pratique</small>
                </span>
            </div>
        </div>
    </section>

    <section class="features" id="fonctionnalites">
        <div class="container">
            <div class="section-heading">
                <span class="section-kicker">L'essentiel de PharmaStock</span>
                <h2>La simplicité au cœur de votre gestion</h2>
                <p>
                    Les outils essentiels pour organiser vos produits
                    pharmaceutiques et faciliter le suivi de vos opérations.
                </p>
            </div>

            <div class="feature-grid">
                <article class="feature-card">
                    <div class="feature-number">
                        <span class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.8" aria-hidden="true">
                                <path d="M8 4h8a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3H8a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3Z"/>
                                <path d="M5 12h14M12 8v8"/>
                            </svg>
                        </span>
                        <span>01</span>
                    </div>
                    <h3>Gestion des produits</h3>
                    <p>
                        Organisez les informations relatives aux produits
                        pharmaceutiques et retrouvez plus facilement
                        les références utiles à votre activité.
                    </p>
                    <a href="frontend/pages/connexion.html" class="feature-link">
                        Accéder à mon espace
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" aria-hidden="true">
                            <path d="M5 12h14M13 6l6 6-6 6"/>
                        </svg>
                    </a>
                </article>

                <article class="feature-card">
                    <div class="feature-number">
                        <span class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.8" aria-hidden="true">
                                <path d="M4 7h16v13H4z"/>
                                <path d="M2 4h20v3H2zM12 10v7m-3-3 3 3 3-3"/>
                            </svg>
                        </span>
                        <span>02</span>
                    </div>
                    <h3>Suivi des mouvements</h3>
                    <p>
                        Facilitez le suivi des entrées et des sorties afin
                        de mieux comprendre les mouvements des produits
                        dans votre stock.
                    </p>
                    <a href="frontend/pages/connexion.html" class="feature-link">
                        Accéder à mon espace
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" aria-hidden="true">
                            <path d="M5 12h14M13 6l6 6-6 6"/>
                        </svg>
                    </a>
                </article>

                <article class="feature-card">
                    <div class="feature-number">
                        <span class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.8" aria-hidden="true">
                                <path d="M4 4h16v16H4z"/>
                                <path d="m8 12 3 3 5-6"/>
                            </svg>
                        </span>
                        <span>03</span>
                    </div>
                    <h3>Inventaire organisé</h3>
                    <p>
                        Consultez les informations disponibles dans votre
                        application pour garder une vision plus claire
                        de votre inventaire.
                    </p>
                    <a href="frontend/pages/connexion.html" class="feature-link">
                        Accéder à mon espace
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" aria-hidden="true">
                            <path d="M5 12h14M13 6l6 6-6 6"/>
                        </svg>
                    </a>
                </article>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="container">
            <div class="cta-panel">
                <div class="cta-copy">
                    <span class="section-kicker">Votre espace vous attend</span>
                    <h2>Prêt à simplifier la gestion de vos stocks ?</h2>
                    <p>
                        Retrouvez votre espace PharmaStock et accédez
                        aux fonctionnalités de votre application.
                    </p>
                </div>

                <a href="frontend/pages/connexion.html" class="button">
                    Se connecter maintenant
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" aria-hidden="true">
                        <path d="M5 12h14M13 6l6 6-6 6"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>
</main>

<footer class="footer">
    <div class="container footer-inner">
        <a href="index.php" class="footer-brand">
            <span class="logo-mark">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M9 3h6v6h6v6h-6v6H9v-6H3V9h6V3Z"
                          fill="currentColor"/>
                </svg>
            </span>
            <span>Pharma<span style="color: var(--green)">Stock</span></span>
        </a>

        <p class="footer-copy">
            © <span id="year"></span> PharmaStock — Tous droits réservés.
        </p>

        <a href="frontend/pages/connexion.html" class="footer-login">Accéder à mon compte →</a>
    </div>
</footer>

<script>
    document.getElementById("year").textContent =
        new Date().getFullYear();
</script>

</body>
</html>
