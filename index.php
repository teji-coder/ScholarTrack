<?php
$page_title = "Home";
include "header.php";
?>

<section style="padding:70px 8%; background:#FFF78D; min-height:85vh; display:flex; align-items:center;">

    <div style="flex:1; padding-right:40px;">

        <h1 style="font-size:52px; color:#283F24; margin-bottom:20px;">
            Find the Right Scholarship
            <span style="color:#467235;">Faster.</span>
        </h1>

        <p style="font-size:18px; color:#555; line-height:1.8; margin-bottom:30px;">
            ScholarTrack is a Scholarship Eligibility and Application Portal
            that helps students discover scholarships they qualify for,
            submit applications online, and track their application status
            in one convenient platform.
        </p>

        <a href="register.php" class="btn btn-gold">
            Get Started
        </a>

        <a href="login.php" class="btn btn-primary">
            Login
        </a>

    </div>

    <div style="flex:1; text-align:center;">

        <div style="
    	 background:#F2F2F2;
   	 padding:40px;
    	 border-radius:20px;
    	 border:1px solid #DCDCDC;
    	 box-shadow:0 12px 30px rgba(0,0,0,.12);
    	 display:inline-block;
    	 width:500px;
">

            <div style="
    		text-align:center;
    		margin-bottom:25px;
		">

    	    <img
       		src="assets/logo2.png"
        	alt="ScholarTrack"
        	style="
            		width:320px;
            		max-width:100%;
            		height:auto;
            		display:block;
           		 margin:auto;
        	"
    	>

</div>

<hr style="margin:18px 0 25px;">

            <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:18px;">

                <div class="stat-card highlight">
                    <h3>Scholarships</h3>
                    <div class="number">120+</div>
                </div>

                <div class="stat-card">
                    <h3>Students</h3>
                    <div class="number">3,500+</div>
                </div>

                <div class="stat-card">
                    <h3>Approved</h3>
                    <div class="number">1,200</div>
                </div>

                <div class="stat-card highlight">
                    <h3>Success Rate</h3>
                    <div class="number">92%</div>
                </div>

            </div>

        </div>

    </div>

</section>

<section style="padding:70px 8%; background:white;">

    <h2 style="text-align:center; margin-bottom:50px; color:#283F24;">
        Why Choose ScholarTrack?
    </h2>

    <div class="grid grid-3">

        <div class="card">
            <h3 style="margin-bottom:15px;">🎯 Eligibility Checker</h3>

            <p>
                Instantly know which scholarships match your
                academic records and qualifications.
            </p>
        </div>

        <div class="card">
            <h3 style="margin-bottom:15px;">📄 Online Application</h3>

            <p>
                Submit scholarship applications and upload
                requirements without visiting offices.
            </p>
        </div>

        <div class="card">
            <h3 style="margin-bottom:15px;">📊 Track Applications</h3>

            <p>
                Monitor every application in real time
                from your personal dashboard.
            </p>
        </div>

    </div>

</section>

<section style="padding:70px 8%; background:#F5F6F4;">

    <h2 style="text-align:center; margin-bottom:50px; color:#283F24;">
        Available Scholarships
    </h2>

    <div class="grid grid-3">

        <div class="card">
            <h3>Academic Excellence Scholarship</h3>

            <p style="margin:15px 0;">
                Full tuition assistance for outstanding students.
            </p>

            <span class="badge badge-approved">
                GWA 1.75
            </span>

        </div>

        <div class="card">

            <h3>DOST-SEI Scholarship</h3>

            <p style="margin:15px 0;">
                Monthly stipend and allowance for STEM students.
            </p>

            <span class="badge badge-approved">
                DOST
            </span>

        </div>

        <div class="card">

            <h3>CHED Merit Scholarship</h3>

            <p style="margin:15px 0;">
                Government-funded scholarship for deserving students.
            </p>

            <span class="badge badge-approved">
                CHED
            </span>

        </div>

    </div>

</section>

<?php include "footer.php"; ?>n