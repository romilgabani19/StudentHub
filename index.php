<?php
require_once __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div>
    <p class="eyebrow">STUDENTHUB PORTAL</p>
    <h1>Your campus life, <span>connected.</span></h1>
    <p class="lead">A simple place to discover campus events, manage your profile, and stay updated.</p>
    <div class="button-row">
      <a class="button" href="register.php">Create account</a>
      <a class="button button-secondary" href="events.html">Explore events</a>
    </div>
  </div>
  <div class="hero-card" aria-label="StudentHub highlights">
    <div class="hero-card-top"><span class="dot"></span> Student activity</div>
    <div class="stat-number">15+</div>
    <p>sample event records</p>
    <div class="mini-bars" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
    <hr>
    <p><strong>One portal.</strong> Events, registration, and student services.</p>
  </div>
</section>
<section class="section">
  <div class="section-heading">
    <div>
      <p class="eyebrow">EXPLORE</p>
      <h2>Everything in one place</h2>
    </div><a href="about.html">About StudentHub →</a>
  </div>
  <div class="card-grid">
    <article class="feature-card"><span class="icon-box">◎</span>
      <h3>Campus events</h3>
      <p>Find workshops, clubs, competitions, and campus activities.</p><a href="events.html">Browse events →</a>
    </article>
    <article class="feature-card"><span class="icon-box">▤</span>
      <h3>Student account</h3>
      <p>Register securely and access your personal dashboard.</p><a href="register.php">Register →</a>
    </article>
    <article class="feature-card"><span class="icon-box">✦</span>
      <h3>Help and feedback</h3>
      <p>Get answers to common questions or send us feedback.</p><a href="faq.html">Visit help centre →</a>
    </article>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>