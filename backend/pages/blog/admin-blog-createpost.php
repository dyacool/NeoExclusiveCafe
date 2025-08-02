<?php
    session_start();
    if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
        header("Location: http://localdomain/pages/auth/login-signup.php");
        exit();
    }

    // Add database connection
    require_once $_SERVER['DOCUMENT_ROOT'] . "/NeoExclusiveCafe/php/includes/database.php";
    if (!$conn) {
        die("Database connection failed");
    }

?>

<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" href="http://localdomain/css/admin/admin-blog-createpost.css">
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
        <script src="https://kit.fontawesome.com/b99e675b6e.js"></script>
    </head>

    <body>
        <?php
            include $_SERVER['DOCUMENT_ROOT'] . "/NeoExclusiveCafe/php/includes/navbar.php";
            if ($_SERVER["REQUEST_METHOD"] == "POST") {                
                $title = mysqli_real_escape_string($conn, $_POST['title']);
                $description = mysqli_real_escape_string($conn, $_POST['description']);
                $imagePath = $_FILES['image']["name"];
                $ext = pathinfo($imagePath, PATHINFO_EXTENSION);
                $allowedTypes = array("jpg", "jpeg", "png", "gif", "JPG", "JPEG", "PNG", "GIF");
                $tempName = $_FILES['image']["tmp_name"];
                $targetPath = $_SERVER['DOCUMENT_ROOT'] . "/NeoExclusiveCafe/assets/uploaded-images-admin/" . $imagePath;
                
                // Create directory if it doesn't exist
                $uploadDir = $_SERVER['DOCUMENT_ROOT'] . "/NeoExclusiveCafe/assets/uploaded-images-admin/";
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                if (in_array($ext, $allowedTypes)){
                    if(move_uploaded_file($tempName, $targetPath)){
                        $sql = "INSERT INTO blog_posts (title, description, image_path, author, created_at) 
                        VALUES (?, ?, ?, 'Admin', NOW())";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "sss", $title, $description, $imagePath);
                        
                        if(mysqli_stmt_execute($stmt)){
                            echo"<script>alert('New blog post created successfully'); window.location.href = '/NeoExclusiveCafe/pages/admin/admin-blog.php';</script>";
                        } else {
                            echo"<script>alert('Error creating blog post: " . mysqli_error($conn) . "');</script>";
                        }
                        mysqli_stmt_close($stmt);
                    } else {
                        echo"<script>alert('Error uploading image. Please check file permissions.');</script>";
                    }
                } else {
                    echo"<script>alert('Invalid file type. Only JPG, JPEG, PNG and GIF files are allowed.');</script>";
                }
            }
        ?>
            <div class="mainContainer">
                <!--back to admin-blog button-->
                <div class="container">
                    <button class="cta" onclick="window.location.href='/NeoExclusiveCafe/pages/admin/admin-blog.php'">
                        <svg
                            id="arrow-horizontal"
                            xmlns="http://www.w3.org/2000/svg"
                            width="30"
                            height="10"
                            viewBox="0 0 46 16"
                        >
                            <path
                            id="Path_10"
                            data-name="Path 10"
                            d="M38,0,39.455,1.455,33.949,6.961H76V9.039H33.949l5.506,5.506L38,16l-8-8Z"
                            transform="translate(-25)"
                            ></path>
                        </svg>
                        <span class="hover-underline-animation"> Go Back </span>
                    </button>                
                    <form class="post-cont" action="admin-blog-createpost.php" method="post" enctype="multipart/form-data">
                        <div class="post-container">
                            <div class="dtitle">
                                <label class="title">Title</label>
                                <input type="text" id="title" name="title" required>
                            </div>

                            <div class="dimage">
                                <label class="image">Image</label>
                                <div class="imagecont">
                                    <label class="media" for="image">Upload image</label>
                                </div>
                                <input multiple type="file" class="images" id="image" name="image">

                                <script>
                                    const output = document.querySelector(".fileSelected");
                                    const fileInput = document.querySelector(".images");

                                    fileInput.addEventListener("change", () => {
                                    for (const file of fileInput.files) {
                                        output.innerText += `${file.name}\n`;
                                    }
                                    });
                                </script>
                            </div>

                            <div class="ddescription">
                                <label class="lbl-title">Description</label>
                                <textarea class="description" id="description" name="description"></textarea><br>
                            </div>

                        </div>
                        <div class="buttons">
                            <input type="button" id="discard" name="discard" value="Discard">
                            <button class="submit" type="submit">Upload</button>
                        </div>
                    </form>
                </div>            
                <!--confirm discard modal-->
                <div class="popup" id="popup">
                    <div class="overlay">   
                    </div>
                    <div class="popup-content">
                        <h2>Discard create post</h2>
                        <p>Are you sure you want to discard post creation?</p>
                        <div class="controls">
                            <input type="button" class="cancel-btn" id="confirm-btn" value="Cancel">
                            <input type="button" class="confirm-btn" id="confirm-btn" onclick="location='admin-blog.php'" value="Confirm">
                        </div>
                    </div>
                    </div>
                </div>
                <!--functions for modal-->
                <script>
                function createPopup(id){
                    let popupNode = document.querySelector(id);
                    let overlay = popupNode.querySelector(".overlay");
                    let cancelBtn   = popupNode.querySelector(".cancel-btn");
                    function openPopup(){
                    popupNode.classList.add("active");
                    }
                    function closePopup(){
                        popupNode.classList.remove("active");
                    }
                    overlay.addEventListener("click", closePopup);
                    cancelBtn.addEventListener("click", closePopup);
                    return openPopup;
                }
                    let openPopup = createPopup("#popup");
                    document.querySelector("#discard").addEventListener("click",openPopup);

                document.addEventListener('DOMContentLoaded', function() {
                // Elements
                const fileInput = document.getElementById('image');
                const mediaLabel = document.querySelector('label.media');
                
                // Add content to the upload label
                if (mediaLabel) {
                    mediaLabel.innerHTML = '<div class="upload-text">Click to upload image</div>';
                }
                
                // Create image preview element
                const imagePreview = document.createElement('img');
                imagePreview.className = 'image-preview';
                mediaLabel.appendChild(imagePreview);
                
                // Create remove image button
                const removeButton = document.createElement('button');
                removeButton.className = 'remove-image';
                removeButton.innerHTML = '×';
                removeButton.type = 'button';
                removeButton.setAttribute('aria-label', 'Remove image');
                mediaLabel.appendChild(removeButton);
                
                // Handle file selection
                fileInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    
                    if (file) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        // Show image preview
                        imagePreview.src = e.target.result;
                        imagePreview.classList.add('preview-active');
                        
                        // Show remove button
                        removeButton.classList.add('remove-active');
                        
                        // Hide the upload text
                        const uploadText = mediaLabel.querySelector('.upload-text');
                        if (uploadText) {
                        uploadText.style.display = 'none';
                        }
                    };
                    
                    reader.readAsDataURL(file);
                    }
                });
                
                // Handle remove image button
                removeButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Clear the file input
                    fileInput.value = '';
                    
                    // Hide image preview
                    imagePreview.classList.remove('preview-active');
                    
                    // Hide remove button
                    removeButton.classList.remove('remove-active');
                    
                    // Show the upload text
                    const uploadText = mediaLabel.querySelector('.upload-text');
                    if (uploadText) {
                    uploadText.style.display = 'block';
                    }
                });

                // Add these lines to your existing popup script
                document.querySelector("#discard").addEventListener("click", function() {
                    let popupNode = document.querySelector("#popup");
                    popupNode.classList.add("active");
                });
                
                // Cancel button in popup
                const cancelBtn = document.querySelector(".cancel-btn");
                if (cancelBtn) {
                    cancelBtn.addEventListener("click", function() {
                    let popupNode = document.querySelector("#popup");
                    popupNode.classList.remove("active");
                    });
                }
                
                // Overlay click to close popup
                const overlay = document.querySelector(".popup .overlay");
                if (overlay) {
                    overlay.addEventListener("click", function() {
                    let popupNode = document.querySelector("#popup");
                    popupNode.classList.remove("active");
                    });
                }
                });
                </script>
            </div>
    </body>
</html>