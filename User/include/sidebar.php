<head>
    <style>
         :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --sidebar-bg: #2c3e50;
            --sidebar-text: #ecf0f1;
            --text-color: #333;
            --bg-color: #f4f6f7;
        }

        body {
            background-color: var(--bg-color);
            font-family: 'Arial', sans-serif;
            overflow-x: hidden;
        }
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: var(--sidebar-bg);
            color: var(--sidebar-text);
            transition: width 0.3s ease;
            z-index: 1000;
            overflow-x: hidden;
        }

        .sidebar-mini {
            width: 70px;
        }

        .sidebar-full {
            width: 250px;
        }

        .sidebar-content {
            padding-top: 60px;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .sidebar-link {
            color: var(--sidebar-text);
            text-decoration: none;
            padding: 15px;
            display: flex;
            align-items: center;
            transition: background-color 0.3s ease;
        }

        .sidebar-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar-link i {
            margin-right: 15px;
            font-size: 1.2rem;
        }

        .sidebar-link span {
            white-space: nowrap;
        }

        .sidebar-mini .sidebar-link span {
            display: none;
        }

        .toggle-sidebar {
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1050;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
        }
        </style>
</head>
<body>
<div id="sidebar" class="sidebar sidebar-mini">
        <div class="sidebar-content">
            <a href="#" class="sidebar-link active">
                <i class="bi bi-house"></i>
                <span>Dashboard</span>
            </a>
            <a href="courses.php" class="sidebar-link">
                <i class="bi bi-book"></i>
                <span>Courses</span>
            </a>
            <a href="profile.php" class="sidebar-link">
                <i class="bi bi-person"></i>
                <span>Profile</span>
            </a>
            <a href="grades.php" class="sidebar-link">
                <i class="bi bi-clipboard-data"></i>
                <span>Grades</span>
            </a>
            <a href="enrollment.php" class="sidebar-link">
                <i class="bi bi-journal-plus"></i>
                <span>Enrollment</span>
            </a>
            <div class="mt-auto">
                <a href="logout.php" class="sidebar-link">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</body>