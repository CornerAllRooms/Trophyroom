<?php
        session_start();
        if (isset($_SESSION['user'])) {
            header('Location: homepage.php');
            exit;
        }
    
        if (isset($_GET['error'])) {
            echo '<div class="error-message">' . htmlspecialchars($_GET['error']) . '</div>';
        }
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" href="logo.png" type="image/x-icon">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fitness Scroller</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <!-- Login System -->
  <div id="login-screen">
    <h1>Welcome to Fitness Scroller</h1>
    <form id="login-form"  action="register.php">
        <h2>stay motivated by uploading your progress every week </h2>
      <input type="text" id="email" placeholder="email" required>
      <input type="password" id="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>
  </div>

  <!-- Main Content -->
  <div id="main-content" class="hidden">
    <div id="image-scroller">
      <div class="image-container">
        <img src="01.jpg" alt="Fitness Image 1">
        <img src="02.jpg" alt="Fitness Image 2">
        <img src="03.jpg" alt="Fitness Image 3">
        <img src="04.jpg" alt="Fitness Image 4">
      </div>
      <button id="add-image-btn">+ Add Image</button>
    </div>
    <div id="scroll-arrow">➔</div>
  </div>

  <!-- Add to Home Pop-up -->
  <div id="add-to-home-popup" class="hidden">
    <p>Add this website to your home screen for quick access!</p>
    <button id="close-popup">Close</button>
  </div>

  <script src="script.js"></script>
</body>
</html>