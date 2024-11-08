document.addEventListener("DOMContentLoaded", () => {
	const nav = document.querySelector("nav");
    const checkAvailabilityBtn = document.querySelector("#checkAvailabilityBtn");
    const reservationForm = document.querySelector('#reservation-form');
	const menuBtn = document.querySelector("#menu-btn");
	const header = document.querySelector('header');
    const firstSection = document.querySelector('main > section'); 
    let lastScrollY = window.scrollY;
	
	// Menu Button Click Event
	document.querySelector("#menu-btn").addEventListener("click", (ev) => {
        const nav = document.querySelector("nav");
        const currentState = document.querySelector("#menu-btn").getAttribute("data-state");
    
        if (!currentState || currentState === "closed") {
            document.querySelector("#menu-btn").setAttribute("data-state", "opened");
            document.querySelector("#menu-btn").setAttribute("aria-expanded", "true");
    
            // Show the menu and start animation
            nav.classList.add("menu-visible");
            gsap.fromTo(
                ".menu-visible", 
                { y: -500, opacity: 0 }, 
                { duration: 0.75, y: 0, opacity: 1 }
            );
        } else {
            document.querySelector("#menu-btn").setAttribute("data-state", "closed");
            document.querySelector("#menu-btn").setAttribute("aria-expanded", "false");
    
            // Animate hiding the menu
            gsap.to(".menu-visible", {
                duration: 0.75,
                y: -500,
                opacity: 0,
                onComplete: () => {
                    // Remove the class after the animation completes
                    nav.classList.remove("menu-visible");
                }
            });
        }
    });
    
    
	// Nav aublenden bei Klick auf Nav Links
	document.querySelectorAll("nav ul li a").forEach((link) => {
		link.addEventListener("click", (ev) => {
			nav.classList.remove("menu-visible");
		});
	});
	// Sticky Header
    const calculateStickyPoint = () => {
        return firstSection.getBoundingClientRect().bottom + window.scrollY;
    };

    let stickyPoint = calculateStickyPoint();

    window.addEventListener('resize', () => {
        stickyPoint = calculateStickyPoint();
    });

    window.addEventListener('scroll', () => {
        const currentScrollY = window.scrollY;

        if (currentScrollY > stickyPoint) {
            if (currentScrollY < lastScrollY) {
                // Nach oben scrollen
                header.classList.add('small');
                header.style.position = 'fixed';
                header.style.transform = 'translateY(0)';
            }
			else {
                // Nach unten scrollen
                header.style.transform = 'translateY(-100vh)';
            }
        }
		else {
            // Am Anfang der Seite
            header.classList.remove('small');
            header.style.position = 'absolute';
            header.style.transform = 'translateY(0)';
        }

        lastScrollY = currentScrollY;
    });
	

    // Smoothe Sprungmarken
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault();

      const target = document.querySelector(this.getAttribute("href"));
      const targetPosition =
        target.getBoundingClientRect().top + window.pageYOffset;
      const startPosition = window.pageYOffset;
      const distance = targetPosition - startPosition;
      const duration = 750; // Scroll-Dauer in Millisekunden
      let startTime = null;

      function scrollAnimation(currentTime) {
        if (startTime === null) startTime = currentTime;
        const timeElapsed = currentTime - startTime;
        const run = ease(timeElapsed, startPosition, distance, duration);
        window.scrollTo(0, run);
        if (timeElapsed < duration) requestAnimationFrame(scrollAnimation);
      }

      function ease(t, b, c, d) {
        t /= d / 2;
        if (t < 1) return (c / 2) * t * t + b;
        t--;
        return (-c / 2) * (t * (t - 2) - 1) + b;
      }

      requestAnimationFrame(scrollAnimation);
    });
  });
});