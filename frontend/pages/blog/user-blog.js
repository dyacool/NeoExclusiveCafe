document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('blogModal');
    const closeBtn = document.querySelector('.close-modal');
    const blogPosts = document.querySelectorAll('.blog-post');

    // Function to open modal with blog content
    function openModal(blogPost) {
        const image = blogPost.querySelector('.blog-image');
        const title = blogPost.querySelector('.post-title').textContent;
        const author = blogPost.querySelector('.post-author').textContent;
        const date = blogPost.querySelector('.post-date').textContent;
        const description = blogPost.querySelector('.post-description').textContent;

        // Set modal content
        document.getElementById('modalBlogImage').src = image ? image.src : '';
        document.getElementById('modalBlogImage').alt = title;
        document.getElementById('modalBlogTitle').textContent = title;
        document.getElementById('modalBlogAuthor').textContent = author;
        document.getElementById('modalBlogDate').textContent = date;
        document.getElementById('modalBlogDescription').textContent = description;

        // Show modal
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    // Function to close modal
    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // Add click event to each blog post
    blogPosts.forEach(post => {
        post.addEventListener('click', function() {
            openModal(this);
        });
    });

    // Close modal when clicking the close button
    closeBtn.addEventListener('click', closeModal);

    // Close modal when clicking outside the modal content
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    // Close modal when pressing Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && modal.style.display === 'block') {
            closeModal();
        }
    });
}); 