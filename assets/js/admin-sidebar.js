document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('tutorSidebar');
    const main = document.getElementById('dashboardMain');
    const toggleBtn = document.getElementById('sidebarToggle');
    const toggleIcon = toggleBtn ? toggleBtn.querySelector('i') : null;
    const body = document.body;

    // Header scroll behavior
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            body.classList.add('header-hidden');
        } else {
            body.classList.remove('header-hidden');
        }
    });

    function updateIcons(isActive) {
        if (toggleIcon) {
            if (isActive) {
                toggleIcon.classList.remove('fa-bars');
                toggleIcon.classList.add('fa-times');
            } else {
                toggleIcon.classList.remove('fa-times');
                toggleIcon.classList.add('fa-bars');
            }
        }
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            const isActive = sidebar.classList.toggle('active'); 
            body.classList.toggle('sidebar-active'); 
            updateIcons(isActive);
        });
    }

    // Handle initial state based on screen size
    if (window.innerWidth > 900) {
        if (sidebar) sidebar.classList.add('active');
        body.classList.add('sidebar-active');
        updateIcons(true);
    } else {
        if (sidebar) sidebar.classList.remove('active');
        body.classList.remove('sidebar-active');
        updateIcons(false);
    }

    // Only force state on resize if crossing the 900px breakpoint
    let isDesktop = window.innerWidth > 900;
    window.addEventListener('resize', function() {
        const currentlyDesktop = window.innerWidth > 900;
        if (currentlyDesktop !== isDesktop) {
            isDesktop = currentlyDesktop;
            if (isDesktop) {
                sidebar.classList.add('active');
                body.classList.add('sidebar-active');
                updateIcons(true);
            } else {
                sidebar.classList.remove('active');
                body.classList.remove('sidebar-active');
                updateIcons(false);
            }
        }
    });
});
