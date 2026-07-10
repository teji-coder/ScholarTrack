<?php
$is_logged_in = isset($_SESSION["user"]);
?>

<?php if ($is_logged_in): ?>

        </main>
    </div>

<?php else: ?>

    <footer class="site-footer">

        <div class="footer-container">

            <div class="footer-about">

                <img
                    src="assets/scholartrack-logo.png"
                    alt="ScholarTrack Logo"
                    class="footer-logo"
                >

                <p>
                    ScholarTrack is a scholarship eligibility,
                    application, and tracking portal designed to help
                    students discover opportunities and manage their
                    scholarship applications in one place.
                </p>

                <p class="footer-disclaimer">
                    Scholarship information should always be verified
                    through the official provider before submission.
                </p>

            </div>

            <div class="footer-column">

                <h3>
                    Quick Links
                </h3>

                <a href="index.php">
                    Home
                </a>

                <a href="login.php">
                    Login
                </a>

                <a href="register.php">
                    Register
                </a>

            </div>

            <div class="footer-column">

                <h3>
                    Information
                </h3>

                <a href="#about">
                    About ScholarTrack
                </a>

                <a href="#privacy">
                    Privacy Policy
                </a>

                <a href="#terms">
                    Terms and Conditions
                </a>

                <a href="#contact">
                    Contact
                </a>

            </div>

            <div class="footer-column">

                <h3>
                    Contact
                </h3>

                <p>
                    Email:
                    <br>
                    scholartrack@gmail.com
                </p>

                <p>
                    Phone:
                    <br>
                    091234568212
                </p>

                <p>
                    Facebook:
                    <br>
                    https://www.facebook.com/ScholarTrackPH
                </p>

            </div>

        </div>

        <div class="footer-bottom">

            <p>
                © <?= date("Y"); ?> ScholarTrack.
                All rights reserved.
            </p>

            <p>
                Scholarship Eligibility &amp; Application Portal
            </p>

        </div>

    </footer>

<?php endif; ?>

<style>
    .site-footer {
        background:
            linear-gradient(
                135deg,
                #1F351C,
                #283F24
            );
        color: #FFFFFF;
        padding: 50px 7% 24px;
        border-top: 4px solid #FFBF00;
    }

    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns:
            2fr 1fr 1fr 1fr;
        gap: 35px;
    }

    .footer-logo {
        width: 230px;
        height: 85px;
        object-fit: contain;
        object-position: left center;
        filter: brightness(0) invert(1);
        margin-bottom: 14px;
    }

    .footer-about p {
        color:
            rgba(255, 255, 255, 0.78);
        line-height: 1.7;
        max-width: 430px;
        margin-bottom: 12px;
    }

    .footer-disclaimer {
        font-size: 12px;
        color:
            rgba(255, 255, 255, 0.58) !important;
    }

    .footer-column h3 {
        color: #FFBF00;
        margin-bottom: 15px;
        font-size: 16px;
    }

    .footer-column a {
        display: block;
        color:
            rgba(255, 255, 255, 0.82);
        margin-bottom: 10px;
        font-size: 14px;
        transition: 0.2s;
    }

    .footer-column a:hover {
        color: #FFF78D;
        padding-left: 3px;
    }

    .footer-column p {
        color:
            rgba(255, 255, 255, 0.78);
        line-height: 1.6;
        font-size: 14px;
        margin-bottom: 14px;
    }

    .footer-bottom {
        max-width: 1200px;
        margin: 35px auto 0;
        padding-top: 18px;
        border-top:
            1px solid
            rgba(255, 255, 255, 0.16);
        display: flex;
        justify-content: space-between;
        gap: 20px;
        color:
            rgba(255, 255, 255, 0.62);
        font-size: 12px;
    }

    @media (max-width: 950px) {
        .footer-container {
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 650px) {
        .footer-container {
            grid-template-columns: 1fr;
        }

        .footer-bottom {
            flex-direction: column;
        }

        .footer-logo {
            width: 190px;
        }
    }
</style>

</body>
</html>