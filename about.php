<?php
  if (session_status() === PHP_SESSION_NONE) {
      session_start();
  }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>About This Website</title>
    <!-- Tetap panggil file CSS About di sini -->
    <link
      rel="stylesheet"
      href="https://indgamehub.rf.gd/css/about_style.css"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
  </head>
  <body>
    <?php
      include('includes/navbar.php');
    ?>

    <div id="about-page">
      <!-- ===== Home Section ===== -->
      <section id="home-box">
        <img src="img/game2.jpg" alt="Banner Home GameHub" />
        <div class="about-us-text">About This Project</div>
      </section>

      <!-- ===== Gallery Section (Our Crew) ===== -->
      <section id="gallery">
        <h2>Our Crew</h2>

        <div class="gallery-pair">
          <figure>
            <img src="img/m.jpg" alt="Profil Name 1" />
            <figcaption>
              <h3>Vergino Maindoka</h3>
              <p>230211060074</p>
            </figcaption>

            <img src="img/Assassins2.jpg" alt="Game Favorit Name 1" />
            <figcaption>
              <h3>Game Favorit</h3>
              <p>Assassin's Creed II</p>
            </figcaption>
          </figure>
        </div>

        <div class="gallery-pair">
          <figure>
            <img src="img/f.jpg" alt="Profil Name 2" />
            <figcaption>
              <h3>Meylivia Liuw</h3>
              <p>230211060048</p>
            </figcaption>

            <img src="img/House Flipper.jpg" alt="Game Favorit Name 2" />
            <figcaption>
              <h3>Game Favorit</h3>
              <p>House Flipper</p>
            </figcaption>
          </figure>
        </div>

        <div class="gallery-pair">
          <figure>
            <img src="img/m.jpg" alt="Profil Name 3" />
            <figcaption>
              <h3>Miracle Lumowa</h3>
              <p>230211060086</p>
            </figcaption>

            <img src="img/Song OfSyx.jpg" alt="Game Favorit Name 3" />
            <figcaption>
              <h3>Game Favorit</h3>
              <p>Songs Of Syx</p>
            </figcaption>
          </figure>
        </div>

        <div class="gallery-pair">
          <figure>
            <img src="img/m.jpg" alt="Profil Name 4" />
            <figcaption>
              <h3>Varel Sumampouw</h3>
              <p>230211060106</p>
            </figcaption>

            <img src="img/Apex Legends.jpg" alt="Game Favorit Name 4" />
            <figcaption>
              <h3>Game Favorit</h3>
              <p>Apex Legends</p>
            </figcaption>
          </figure>
        </div>
      </section>

      <!-- ===== About This Web Section ===== -->
      <section id="about">
        <h2>Information</h2>
        <article>
          <p>
            GameHub adalah platform web interaktif yang dirancang untuk membantu
            para gamer, khususnya yang memiliki keterbatasan ekonomi, dalam
            menemukan game gratis dengan mudah. Meskipun f\okus utamanya adalah
            menyediakan akses ke informasi game gratis, GameHub tetap menyediakan informasi game berbayar dengan akses membeli game secara resmi
            jika memiliki kemampuan finansial.
          </p>
          <p>
            Aplikasi ini menampilkan data lengkap setiap game, termasuk nama,
            developer, genre, spesifikasi minimum, harga, tautan resmi, dan
            screenshot permainan. Dengan antarmuka yang sederhana dan ramah
            pengguna, pengguna dapat menelusuri dan mencari game sesuai minat
            mereka secara efisien.
          </p>
          <p>
            GameHub mendukung sistem multi-user yang terdiri dari dua peran: admin
            dan user. Admin memiliki kendali penuh atas data game, mulai dari
            menambahkan, mengedit, hingga menghapus entri. Sementara itu, user
            dapat melihat daftar game, mengakses detail lengkap.
          </p>
          <p>
            Proyek ini dibangun tanpa framework eksternal, hanya menggunakan HTML,
            CSS, JavaScript, PHP, dan MySQL, sesuai dengan ketentuan proyek
            akhir. GameHub bertujuan menjadi referensi praktis bagi pecinta game
            serta pengembang pemula yang ingin mempelajari cara membuat sistem
            informasi web multi-user sederhana.
          </p>
        </article>
      </section>

      <!-- ===== Contact Section ===== -->
      <section id="contact">
        <h2>Contact Us</h2>
        <p><b>IT Unsrat</b></p>
        <p>Bahu, Malalayang, Manado City, North Sulawesi</p>
      </section>
    </div>
    <!-- /#about-page -->
  </body>
</html>
