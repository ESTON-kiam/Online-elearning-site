document.addEventListener('DOMContentLoaded', function() {
    // Dropdown toggle functionality for sidebar
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            // Toggle the visibility of the dropdown menu
            const dropdownMenu = this.nextElementSibling;
            dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
            
            // Prevent default link behavior
            e.preventDefault();
        });
    });

    // Profile dropdown functionality 
    const profileIcon = document.querySelector('.profile-icon');
    const dropdownContent = document.querySelector('.dropdown-content');

    if (profileIcon && dropdownContent) {
        profileIcon.addEventListener('click', function(e) {
            dropdownContent.style.display = 
                dropdownContent.style.display === 'block' ? 'none' : 'block';
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!profileIcon.contains(e.target) && !dropdownContent.contains(e.target)) {
                dropdownContent.style.display = 'none';
            }
        });
    }

    // Optional: Responsive sidebar toggle for mobile
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        
        if (window.innerWidth <= 768) {
            sidebar.classList.toggle('sidebar-mobile');
            mainContent.classList.toggle('content-full-width');
        }
    }

    // Add window resize event listener
    window.addEventListener('resize', toggleSidebar);
});