document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const navLinks = document.querySelector('.nav-links');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            navLinks.classList.toggle('active');
        });
    }
    
    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
        if (navLinks && navLinks.classList.contains('active') && !event.target.closest('.main-nav')) {
            navLinks.classList.remove('active');
        }
    });
    
    // Reply to comment functionality
    const replyLinks = document.querySelectorAll('.reply-link');
    
    replyLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const commentId = this.getAttribute('data-comment-id');
            const replyForm = document.getElementById('replyForm-' + commentId);
            
            if (replyForm) {
                // Toggle reply form visibility
                if (replyForm.style.display === 'none' || replyForm.style.display === '') {
                    replyForm.style.display = 'block';
                    
                    // Focus the textarea
                    const textarea = replyForm.querySelector('textarea');
                    if (textarea) {
                        textarea.focus();
                    }
                } else {
                    replyForm.style.display = 'none';
                }
            }
        });
    });
    
    // Like functionality
    const likeButtons = document.querySelectorAll('.like-button');
    
    likeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const postId = this.getAttribute('data-id');
            const postType = this.getAttribute('data-type');
            const likeCount = this.querySelector('.like-count');
            
            // Send AJAX request to like/unlike
            fetch(`like.php?id=${postId}&type=${postType}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update like count
                    if (likeCount) {
                        likeCount.textContent = data.likes;
                    }
                    
                    // Toggle active class
                    if (data.liked) {
                        this.classList.add('active');
                    } else {
                        this.classList.remove('active');
                    }
                } else {
                    console.error('Error:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    });
    
    // Delete confirmation
    const deleteButtons = document.querySelectorAll('.delete-button');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            if (href !== '#') {
                e.preventDefault();
                
                const target = document.querySelector(href);
                
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            }
        });
    });
    
    // Show/hide password functionality
    const passwordToggles = document.querySelectorAll('.password-toggle');
    
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const passwordField = this.previousElementSibling;
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                this.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                passwordField.type = 'password';
                this.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });
    });
    
    // Auto-resize textarea
    const autoResizeTextareas = document.querySelectorAll('textarea.auto-resize');
    
    autoResizeTextareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
        
        // Initialize height
        textarea.dispatchEvent(new Event('input'));
    });
});
