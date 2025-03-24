<?php
session_start();
include 'admin/config.php'; // Database connection

// Initialize variables
$query = isset($_GET['query']) ? htmlspecialchars(strip_tags(trim($_GET['query'])), ENT_QUOTES, 'UTF-8') : '';
 // Search query
$programmes = []; // Default empty result

// Prepare and execute search query if search term is provided
if (!empty($query)) {
    $sql = "SELECT * FROM programmes WHERE published = 1 AND (level = 'Undergraduate' OR level = 'Postgraduate') AND name LIKE :query";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':query' => '%' . $query . '%']);
    $programmes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch all programmes for display (to be used when no search query is made)
$stmt = $conn->prepare("SELECT * FROM programmes WHERE published = 1 AND level = 'Undergraduate'");
$stmt->execute();
$undergraduate_programmes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT * FROM programmes WHERE published = 1 AND level = 'Postgraduate'");
$stmt->execute();
$graduate_programmes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Niels Brock Institute</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <!-- Hero Section with Search Bar -->
    <section id="home" class="hero"
        style="background: url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D') no-repeat center; background-size: cover;">
        <div class="hero-content">
            <h1>Niels Brock Institute</h1>
            <p>Where innovation meets excellence in technology education for the digital age</p>
            <form action="index.php" method="GET"
                style="display: flex; align-items: center; gap: 1rem; padding: 0.5rem; background-color: #fff; border-radius: 5px;">
                <input class="input" type="text" name="query" placeholder="Search programs..."
                    value="<?php echo htmlspecialchars($query); ?>" style="flex-grow: 1;">
                <button class="cta-button" type="submit" style="padding: 0.5rem 1rem; font-size: 1rem;">Search</button>
            </form>
        </div>
    </section>

    <!-- Search Results Section -->
    <?php if (!empty($query)): ?>
        <section id="search-results" class="search-results courses">
            <h2>Search Results for "<?php echo htmlspecialchars($query); ?>"</h2>

            <?php if (!empty($programmes)): ?>
                <!-- Display results in two categories: Undergraduate and Postgraduate -->
                <h3 style="margin-bottom: 20px;">Undergraduate Programs</h3>
                <div class="course-grid">
                    <?php foreach ($programmes as $programme): ?>
                        <?php if ($programme['level'] == 'Undergraduate'): ?>
                            <div class="course-card">
                                <img src="admin/<?php echo $programme['image_path']; ?>" alt="Programme Image">
                                <div class="course-info">
                                    <h3><?php echo $programme['name']; ?></h3>
                                    <p><?php echo $programme['description']; ?></p>
                                    <a href="programme_details.php?id=<?php echo $programme['id']; ?>" class="btn">View Details</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <h3 style="margin-top: 40px; margin-bottom: 20px;">Postgraduate Programs</h3>
                <div class="course-grid">
                    <?php foreach ($programmes as $programme): ?>
                        <?php if ($programme['level'] == 'Postgraduate'): ?>
                            <div class="course-card">
                                <img src="admin/<?php echo $programme['image_path']; ?>" alt="Programme Image">
                                <div class="course-info">
                                    <h3><?php echo $programme['name']; ?></h3>
                                    <p><?php echo $programme['description']; ?></p>
                                    <a href="programme_details.php?id=<?php echo $programme['id']; ?>" class="btn">View Details</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No programmes found matching your search query.</p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <!-- All Programs Section (Optional: When no search query is provided) -->
    <?php if (empty($query)): ?>
        <section id="courses" class="courses">
            <h2>Undergraduate Programs</h2>
            <div class="course-grid">
                <?php foreach ($undergraduate_programmes as $programme): ?>
                    <div class="course-card">
                        <img src="admin/<?php echo $programme['image_path']; ?>" alt="Programme Image">
                        <div class="course-info">
                            <h3><?php echo $programme['name']; ?></h3>
                            <p><?php echo $programme['description']; ?></p>
                            <a href="programme_details.php?id=<?php echo $programme['id']; ?>" class="btn">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <h2>Graduate Programs</h2>
            <div class="course-grid">
                <?php foreach ($graduate_programmes as $programme): ?>
                    <div class="course-card">
                        <img src="admin/<?php echo $programme['image_path']; ?>" alt="Programme Image">
                        <div class="course-info">
                            <h3><?php echo $programme['name']; ?></h3>
                            <p><?php echo $programme['description']; ?></p>
                            <a href="programme_details.php?id=<?php echo $programme['id']; ?>" class="btn">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php include 'footer.php'; ?>
</body>
<script>
    function toggleMenu() {
        document.querySelector(".nav-links").classList.toggle("active");
    }
</script>

</html>